## Why

Guests staying at direct/private bookings must complete AlloggiatiWeb-mandated ID/document check-in before arrival (see the Short-Term Tourist Lease Agreement from the `prenotazione-privata-legal-checkbox` change: "prior to arrival, online check-in with ID details is mandatory"). Today this data can only be entered by the owner manually in `admin/guest-reporting`. There is no self-service way for the guest to submit their own and their travel companions' data, no link to reach such a form, and no reminder if it's left incomplete close to arrival.

## What Changes

- Add a public, token-based "online check-in" flow that reuses the same data model, validation rules, and guest-classification logic (`tipo_alloggiato` auto-defaulting: single guest → 16, first of several → 18 "Capo gruppo", rest → 20 "Membro gruppo") as the existing `admin/guest-reporting` form, extracted into a shared service/logic so both admin and public forms stay in sync.
- Add a persistent, unguessable check-in token + expiry to `bookings` (reusable across multiple visits until checkout date), used to build the public check-in URL.
- Guest can fill/edit personal + document data for themselves (the booking's primary guest) and add travel companions (new `Person` records, attached the same way `BookingGuestController` does today) up to the booking's total guest count, until an explicit "Confirm & submit" step marks the check-in as completed (`checkin_completed_at`). Editable again anytime before check-in as long as the token hasn't expired.
- Data submitted by the guest is only **saved** (same as admin editing a guest's profile) — it is never auto-submitted to the AlloggiatiWeb SOAP service. The owner still reviews and sends it from `admin/guest-reporting` exactly as today.
- Add the check-in link to the existing `BookingConfirmedMail` (from `prenotazione-privata-legal-checkbox`).
- Add a scheduled reminder email (default: 7 days before check-in, configurable), sent only if `checkin_completed_at` is still null, following the same `Schedule::command(...)->dailyAt(...)` pattern as `SendTelegramBookingReminders`.
- The check-in page and its emails are shown in the locale saved from the original booking/request (extend `booking_requests`/`bookings` with a `locale` column), with a language switcher available on the check-in page itself so the guest can change it there.

## Capabilities

### New Capabilities
- `online-checkin-access`: Token-based public access to a booking's check-in page (generation, expiry, locale handling).
- `online-checkin-form`: Public self-service form for guest + companion data, reusing the admin guest-reporting validation/classification logic, with an explicit completion step and post-completion editability.
- `online-checkin-reminders`: Scheduled reminder email sent before check-in if not yet completed, and the check-in link surfaced in the booking-confirmed email.

### Modified Capabilities
- (none — no existing `openspec/specs/` capabilities to modify; `prenotazione-privata-legal-checkbox` specs exist only as a sibling change, not yet archived into `openspec/specs/`)

## Impact

- **Backend**: new `App\Services\GuestReporting\GuestClassifier` (or similar) extracted from `admin/guest-reporting`'s inline defaulting logic and reused by both controllers; new `Public\CheckinController`; new `SendCheckinReminders` console command; migrations adding `checkin_token`, `checkin_token_expires_at`, `checkin_completed_at`, `locale` to `bookings` (and `locale` to `booking_requests`).
- **Frontend**: new public check-in page/view, reusing `resources/ts/people-reporting-fields.ts` logic (needs i18n of its hardcoded Italian strings), new i18n keys for all form labels/help text across it/en/fr/de.
- **Email**: `BookingConfirmedMail`/view updated to include the check-in link; new `CheckinReminderMail`.
- **Docs**: `docs/specific-data-model.md`, `docs/specific-tech-backend-doc.mdc`, `docs/specific-tech-frontend-doc.mdc`, `docs/business-doc.mdc`.
