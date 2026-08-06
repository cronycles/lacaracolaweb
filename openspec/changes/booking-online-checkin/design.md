## Context

`admin/guest-reporting` (`GuestReportingController` + `resources/views/admin/guest-reporting/show.blade.php` + `resources/ts/people-reporting-fields.ts`) already implements: per-guest AlloggiatiWeb data entry, Italian-municipality/country autocomplete, and `tipo_alloggiato` auto-defaulting (single guest → `16`, first of several → `18` "Capo gruppo", rest → `20` "Membro gruppo", read-only/non-editable by the admin). Only guest types `16/17/18` require document data; `19/20` don't. Submission to the AlloggiatiWeb SOAP service is a separate, explicit "test"/"send" action, never automatic.

`Booking` has no concept of a public-facing token today. The `prenotazione-privata-legal-checkbox` change added `BookingConfirmedMail` (sent manually by the owner once a stay is confirmed) — this is the natural place to surface the check-in link, since by then a real `Booking` (with `person_id`, dates, guest counts) exists.

## Goals / Non-Goals

**Goals:**
- Let the guest (primary booker) submit ID/document data for themselves and their companions through an unguessable, expiring link, without needing an account.
- Reuse the exact same guest-classification and validation rules as the admin form, avoiding duplicated business logic.
- Never auto-submit guest data to the AlloggiatiWeb SOAP service from the public flow — the owner stays in control of the actual authority submission.
- Remind the guest if check-in isn't completed close to arrival.
- Support it/en/fr/de on the check-in page, defaulting to the booking's original locale with an on-page switcher.

**Non-Goals:**
- No automated SOAP submission triggered by guest completion.
- No guest authentication/account system — security relies solely on the token (random + expiring + HTTPS).
- No payment or legal-consent changes here (already handled in `prenotazione-privata-legal-checkbox`).
- No changes to how the admin form works, beyond extracting shared logic into a reusable service.

## Decisions

1. **Extract `tipo_alloggiato` auto-defaulting into a shared helper** (e.g. `App\Services\GuestReporting\GuestClassifier::defaultTipoFor(int $index, int $totalGuests): string`), used by both `admin/guest-reporting/show.blade.php` (replacing the inline `@php $defaultTipo = ...` block) and the new public check-in form.
   - *Why*: the user explicitly asked to reuse code instead of duplicating the classification logic; a single source of truth prevents the two forms from drifting apart.

2. **Persistent token stored on `Booking`** (`checkin_token` — random 48+ char string, unique; `checkin_token_expires_at` — set to the booking's `checkout` date/time) rather than a Laravel signed URL.
   - *Why*: the guest may need multiple sessions over days/weeks before arrival; a signed URL would need regenerating and re-emailing each time it expires, while a stored token lets every email (confirmation + reminder) reuse the same stable link. Token becomes useless once checkout has passed (naturally "closes" the flow).
   - *Alternative considered*: Laravel `URL::temporarySignedRoute` — rejected because its expiry is fixed at generation time and can't be "reset" without minting a new link (harder for reminders reusing the same URL).

3. **No extra authentication factor beyond the token.** A high-entropy random token (e.g. 48 chars, `Str::random(48)`) transmitted only over HTTPS via email is treated as sufficient (same trust model as password-reset links). Confirmed with the product owner.

4. **Guest data is only ever saved, never auto-reported.** The `Public\CheckinController` reuses the same persistence logic as `GuestReportingController::validateAndPersistGuests` (extracted into a shared trait/service) but never calls the `GuestReportingDriverInterface` driver. The owner still reviews/tests/sends from `admin/guest-reporting` exactly as today.

5. **Explicit completion step**: a "Confirm & submit" action sets `Booking.checkin_completed_at = now()`. This is the single source of truth for "is check-in done" (used by the reminder query and any admin-side status badge), rather than inferring completeness from guest data (which would silently change status if a guest's admin-edited data becomes incomplete later).
   - Guests can still return and edit data after confirming, up until the token expires (checkout date) — re-editing does not require re-confirming, but the confirmed flag stays set (it only reflects "the guest went through the flow once", not a strict data-freeze).

6. **Locale**: add `locale` (nullable string, e.g. `it`/`en`/`fr`/`de`) to both `booking_requests` (captured at submission time, mirroring the existing `app()->getLocale()`) and `bookings` (copied over when the owner creates/links a `Booking` from a request, or set manually otherwise). The check-in page defaults to `Booking.locale` (falling back to `config('routes.fallback')`) and offers a simple language switcher that just reloads the same token URL with a `?lang=` override (not persisted), reusing the existing `lang-switcher.ts` component pattern where possible.

7. **Reminder scheduling**: new `App\Console\Commands\SendCheckinReminders` command, registered in `routes/console.php` via `Schedule::command(...)->dailyAt(...)`, mirroring `SendTelegramBookingReminders`. Lead time configurable via `config('apartment.checkin.reminder_lead_days', 7)`. Sends `CheckinReminderMail` only to bookings where `checkin_completed_at IS NULL`, `canceled_at IS NULL`, and `checkin` matches today + lead days.

## Risks / Trade-offs

- [Risk] A leaked check-in link (e.g. forwarded email) exposes the guest's personal data entry form to a third party until the token expires → Mitigation: token is high-entropy and time-bounded to the stay; document this trust model; consider (future) rate-limiting repeated failed token lookups.
- [Risk] Extracting the classification logic touches the existing, working admin form → Mitigation: cover with a regression test asserting the admin form's rendered defaults are unchanged after extraction.
- [Risk] Guests entering data for companions means new `Person` records with unavoidably lower data quality (no phone/email needed) → acceptable, consistent with how admin adds companions today.
- [Trade-off] No SOAP auto-submission means the owner must still remember to review and send — acceptable per explicit product decision; the reminder email only concerns guest-side completion, not owner-side submission (out of scope here).

## Migration Plan

- New migration: add `checkin_token` (string, nullable, unique), `checkin_token_expires_at` (nullable datetime), `checkin_completed_at` (nullable datetime), `locale` (nullable string, 5) to `bookings`.
- New migration: add `locale` (nullable string, 5) to `booking_requests`.
- Token is generated lazily (on first need — e.g. when the owner sends `BookingConfirmedMail`), not backfilled for existing bookings.
- Rollback: standard `down()` migrations dropping the new columns.

## Open Questions

- None outstanding — all key decisions confirmed with the product owner (see proposal/tasks).
