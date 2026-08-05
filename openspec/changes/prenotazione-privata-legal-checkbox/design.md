## Context

The public "availability request" flow (`booking-form` component → `POST booking.request` → `BookingController::requestAvailability`) currently only sends an email to the owner and flashes data to session for the thank-you page. Nothing is persisted. There is no concept of "pending" status: the real `Booking` model (admin-only, with financials) is created manually by the owner after negotiating with the guest outside the system (WhatsApp/phone/email).

Direct/private bookings under `apartment.booking.max_nights` (28) need a signed-off legal basis (house rules + short-term tourist lease agreement) and a clear paper trail of consent (timestamp + IP), because these bookings fall outside portal-mediated protections (Booking.com/Airbnb T&Cs).

Locales: only `it`, `en`, `fr`, `de` are supported today (`config/routes.php`, `lang/*`). Route slugs are locale-specific and generated via `route_locale()`.

## Goals / Non-Goals

**Goals:**
- Make legal consent (house rules + lease terms) a hard requirement to submit the availability request form, both client-side (disabled button) and server-side (validation).
- Persist every request with proof of consent (`booking_requests` table: contact/stay data, `terms_accepted_at`, `ip_address`, `user_agent`).
- Publish the lease agreement text as a public, linkable page per locale.
- Send guests a clear "pending confirmation" email immediately, and let the owner manually trigger a "confirmed — pay within 48h" email later, once a real `Booking` exists in admin.
- Apply consistent brand identity (logo + colors) to all transactional emails.

**Non-Goals:**
- No Spanish (`es`) locale support in this change.
- No online check-in / ID document collection for guests (Phase 2, separate change, will reuse `admin/guest-reporting` UI).
- No automatic cancellation job if payment isn't received within 48h — the owner cancels manually in admin.
- No payment gateway integration; payment instructions are informational text (bank transfer/IBAN) only.
- No changes to the generic contact form (`contact.blade.php`) — consent checkbox only applies to the availability/booking request form.

## Decisions

1. **Persist requests in a new `booking_requests` table** rather than only keeping the session flash.
   - *Why*: Legal proof of consent must survive beyond the session and be queryable/auditable. Also gives a natural anchor for `Booking.booking_request_id`.
   - *Alternative considered*: Store consent flag only in the outgoing emails (no DB record) — rejected because it provides no queryable proof if a dispute arises.

2. **Add nullable `booking_request_id` FK on `bookings`** (not the reverse) so an existing `BookingRequest` can optionally be linked when the owner manually creates the admin `Booking` for that stay.
   - *Why*: `Booking` is the source of truth for a confirmed reservation; `BookingRequest` is just an inbound lead, and multiple requests could theoretically arrive without ever becoming a `Booking`. One `Booking` links to at most one originating request.

3. **New public page/route `condizioni-generali-prenotazione`** (with per-locale slugs chosen by the agent: `terms-and-conditions` (en), `conditions-generales` (fr), `allgemeine-geschaeftsbedingungen` (de)), following the existing `RulesController`/`config/routes.php` slug pattern.
   - *Why*: Reuses existing locale-routing conventions; keeps legal text out of the checkbox label itself.

4. **Client + server-side consent enforcement**: JS disables the submit button until the checkbox is checked; Laravel validation additionally requires `accepted_terms => ['required', 'accepted']` server-side (defense against JS bypass / direct POST).

5. **Manual trigger for the "confirmed, pay within 48h" email** from the admin `Booking` show page (new button + route + controller action in `Admin\BookingController`), not automated — the owner decides when a stay is truly confirmed.
   - Payment deadline = send time + 48h (computed and displayed, not stored/enforced by a scheduler).
   - Free-cancellation deadline = `checkin` − 14 days (computed dynamically from the booking's checkin date, only shown if that date is still in the future).
   - IBAN/beneficiary/payment instructions come from new `config/apartment.php` entries (static config, not per-email input).

6. **Shared branded email layout**: a new Blade layout/partial (e.g. `resources/views/emails/layout.blade.php`) with the logo (`logo-wordmark-blue`) and brand colors from `resources/css/tokens.css` (`--color-primary: #30596C`, `--color-accent: #c7b772`), included by all mailables (existing `BookingRequestMail`, `ContactMail`, and the new ones). Email CSS must be inlined/simple (no external stylesheet — email clients don't load `tokens.css`), so brand hex values are duplicated as literal constants in the email layout.

7. **i18n**: all new user-facing strings (checkbox label with embedded links, terms page body, new email bodies) added as translation keys to `lang/{it,en,fr,de}` following the existing key naming (`app.*` namespace, consistent with `booking_*` keys already used in `booking-form.blade.php`).

## Risks / Trade-offs

- [Risk] Locale-specific route slugs for the new terms page must stay in sync across `config/routes.php`, the checkbox label link generation, and the lease text's self-reference to the house-rules page → Mitigation: always generate links via `route_locale()`, never hardcode `/{locale}/slug` strings (the proposal doc's literal `/{locale}/regole-casa` examples must NOT be used as-is in code).
- [Risk] Storing IP address for consent proof has GDPR implications → Mitigation: document retention/purpose in `docs/specific-data-model.md`, keep it consistent with existing GDPR handling elsewhere in the app (e.g. `GuestReport`).
- [Risk] Manually triggered confirmation email could be sent twice by mistake → Mitigation: track `confirmation_sent_at` on `Booking` and warn/confirm in the UI if already sent.
- [Risk] Email clients strip `<style>`/CSS variables → Mitigation: use inline styles with literal hex values in the shared email layout instead of referencing `tokens.css`.
- [Trade-off] No automated cancellation after 48h means stale "pending payment" bookings could linger → acceptable per explicit product decision (manual process for now).

## Migration Plan

- New migration: `booking_requests` table (id, name, email, phone, checkin, checkout, adults, children, message, newsletter, terms_accepted_at, ip_address, user_agent, timestamps).
- New migration: add nullable `booking_request_id` FK (`nullOnDelete`) to `bookings`.
- New migration: add `confirmation_sent_at` (nullable datetime) to `bookings` to guard against duplicate confirmation emails.
- No data backfill needed (new tables/columns only, no existing data affected).
- Rollback: standard `down()` migrations dropping the new table/columns.

## Open Questions

- None outstanding — all key decisions confirmed with the product owner (see proposal/tasks).
