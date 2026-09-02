## Why

Direct/private bookings (outside Booking.com/Airbnb) under 30 nights need a documented legal basis: a short-term tourist lease agreement and house rules that the guest explicitly accepts before submitting a booking request, plus clear email communication about booking status (pending → confirmed → payment deadline). Today the public availability request form has no consent mechanism, the owner/guest emails do not mention any legal acceptance, there is no "booking confirmed, pay within 48h" email, and transactional emails do not use the official brand (logo/colors).

## What Changes

- Add a mandatory legal-consent checkbox to the public availability request form (`booking-form` component), placed right above the submit button. The submit button is disabled until the checkbox is checked (client-side), and the server also rejects the request if consent is missing.
- Checkbox label links to the existing "House Rules" page and to a **new** "General Terms / Short-Term Tourist Lease Agreement" page, using locale-aware routes (`route_locale()`), for it/en/fr/de only (no Spanish in this change).
- Create the new public page `condizioni-generali-prenotazione` (and localized equivalents) rendering the short-term tourist lease agreement text (translated to en/fr/de).
- Persist every availability request in a new `booking_requests` table (name, contact info, stay dates, `terms_accepted_at`, IP address, user agent) to serve as legal proof of consent, replacing the current session-only flash storage.
- Add a nullable `booking_request_id` foreign key on `bookings` so an admin, when turning a public request into a real admin `Booking`, can link back to the original consent record.
- Update the existing owner notification email (`BookingRequestMail`) to state that the guest accepted the house rules and lease terms.
- Add a new guest-facing email sent right after the request is submitted, clarifying the booking is **not confirmed** and is pending the owner's written confirmation.
- Add a new "booking confirmed — pay within 48h" email, manually triggered by the owner from the admin `Booking` show page, including: IBAN/payment instructions (from `config/apartment.php`), payment deadline (48h from send time), the free-cancellation deadline (checkin − 14 days, computed dynamically), and a mention of accepted terms. Owner is BCC'd. No automatic cancellation job (owner cancels manually if unpaid).
- Introduce a shared branded email layout (logo + brand colors from `resources/css/tokens.css`) and apply it to all existing and new transactional emails (`BookingRequestMail`, `ContactMail`, and the new emails).
- Document the brand palette/logo usage in `docs/specific-tech-frontend-doc.mdc`.
- Out of scope (Phase 2, separate change): reusable online check-in form for guests (based on `admin/guest-reporting`) with a tokenized/expiring link tied to the booking.

## Capabilities

### New Capabilities
- `booking-legal-consent`: Mandatory checkbox on the availability request form, server-side enforcement, and persistence of consent (booking_requests table) as legal proof.
- `booking-terms-page`: New public page publishing the short-term tourist lease agreement text, localized for it/en/fr/de.
- `booking-status-emails`: Guest-facing emails for "request received / pending confirmation" and owner-triggered "booking confirmed — pay within 48h", including dynamic cancellation deadline and payment instructions.
- `transactional-email-branding`: Shared branded layout (logo + brand colors) reused by all transactional emails.

### Modified Capabilities
- (none — no existing `openspec/specs/` capabilities exist yet in this repository)

## Impact

- **Frontend**: `resources/views/components/booking-form.blade.php`, `resources/css/components/booking-form.css`, related JS validation (likely under `resources/js/`), new page view + route for terms page.
- **Backend**: `app/Http/Controllers/Public/BookingController.php` (validation + persistence), new `BookingRequest` model + migration, `Booking` model (new nullable FK + migration), new `App\Mail\*` classes, `app/Http/Controllers/Admin/BookingController.php` (new manual "send confirmation" action + route), `config/routes.php` (new terms page slugs), `config/apartment.php` (IBAN/payment config).
- **i18n**: `lang/it`, `lang/en`, `lang/fr`, `lang/de` — new keys for checkbox text, terms page content, and new email copy.
- **Docs**: `docs/specific-tech-frontend-doc.mdc` (branding), `docs/specific-tech-backend-doc.mdc` (new mail flow), `docs/specific-data-model.md` (new table/column).
