## 1. Data model

- [x] 1.1 Create migration for `booking_requests` table (name, email, phone, checkin, checkout, adults, children, message, newsletter, terms_accepted_at, ip_address, user_agent, timestamps)
- [x] 1.2 Create `App\Models\BookingRequest` model
- [x] 1.3 Create migration adding nullable `booking_request_id` (FK, nullOnDelete) to `bookings`
- [x] 1.4 Create migration adding nullable `confirmation_sent_at` (datetime) to `bookings`
- [x] 1.5 Update `App\Models\Booking` fillable/casts/relations for the new columns and `bookingRequest()` relation
- [x] 1.6 Update `docs/specific-data-model.md` with the new table/columns

## 2. Config

- [x] 2.1 Add IBAN/beneficiary/payment instructions fields to `config/apartment.php`
- [x] 2.2 Add new locale slugs for the terms page to `config/routes.php` (it: `condizioni-generali-prenotazione`, en: `terms-and-conditions`, fr: `conditions-generales`, de: `allgemeine-geschaeftsbedingungen`)

## 3. Terms page

- [x] 3.1 Create `App\Http\Controllers\Public\TermsController` (mirroring `RulesController`)
- [x] 3.2 Register localized routes for the terms page in `routes/web.php`
- [x] 3.3 Create `resources/views/public/terms.blade.php` rendering the lease agreement text
- [x] 3.4 Translate the lease agreement text into it/en/fr/de and add to `lang/{locale}/*.php`

## 4. Consent checkbox on the availability request form

- [x] 4.1 Add checkbox markup to `resources/views/components/booking-form.blade.php`, right above the submit button, with label linking to `route_locale('rules')` and `route_locale('terms')` (or equivalent route names)
- [x] 4.2 Add translation keys for the checkbox label (with embedded links) to `lang/{it,en,fr,de}`
- [x] 4.3 Add/update JS to disable the submit button until the checkbox is checked
- [x] 4.4 Add styling for the checkbox in `resources/css/components/booking-form.css`
- [x] 4.5 Add server-side validation (`accepted_terms => ['required', 'accepted']`) in `BookingController::requestAvailability`

## 5. Persisting requests with consent proof

- [x] 5.1 Update `BookingController::requestAvailability` to create a `BookingRequest` record (including `terms_accepted_at`, `ip_address` from `$request->ip()`, `user_agent` from `$request->userAgent()`)
- [x] 5.2 Ensure existing session-flash behavior for the thank-you page still works unchanged

## 6. Owner + guest notification emails

- [x] 6.1 Update `App\Mail\BookingRequestMail` / `resources/views/emails/booking-request.blade.php` to state the guest accepted the terms (with timestamp)
- [x] 6.2 Create `App\Mail\BookingRequestPendingMail` (guest-facing, "pending owner confirmation") + view, sent in the guest's locale
- [x] 6.3 Send `BookingRequestPendingMail` to the guest from `BookingController::requestAvailability`, non-blocking on failure (same pattern as the existing owner email)
- [x] 6.4 Add translation keys for the new guest email content

## 7. Booking confirmation email (manual, admin-triggered)

- [x] 7.1 Create `App\Mail\BookingConfirmedMail` + view: terms acknowledgement, payment instructions/IBAN, payment reference (stay dates), 48h payment deadline, free-cancellation deadline (checkin − 14 days, omitted if already passed)
- [x] 7.2 Add a "Send confirmation email" action/route in `Admin\BookingController` (e.g. `POST admin/bookings/{booking}/send-confirmation`)
- [x] 7.3 Guard against duplicate sends: if `confirmation_sent_at` is already set, require explicit confirmation before resending
- [x] 7.4 On send, set `confirmation_sent_at`, BCC the owner (`config('apartment.email')`)
- [x] 7.5 Add the "Send confirmation email" button to the admin `Booking` show view
- [x] 7.6 Add translation keys for the confirmation email content

## 8. Branding for transactional emails

- [x] 8.1 Create shared email layout `resources/views/emails/layout.blade.php` with inlined brand colors (`#30596C`, `#c7b772`) and the logo (`logo-wordmark-blue`), using a publicly reachable/embedded image reference suitable for email clients
- [x] 8.2 Refactor `resources/views/emails/booking-request.blade.php` and `resources/views/emails/contact.blade.php` to use the shared layout
- [x] 8.3 Use the shared layout for the new `BookingRequestPendingMail` and `BookingConfirmedMail` views
- [x] 8.4 Document brand palette/logo usage in `docs/specific-tech-frontend-doc.mdc`

## 9. Tests

- [x] 9.1 Feature test: submitting the availability request without `accepted_terms` returns a validation error and creates no `BookingRequest`
- [x] 9.2 Feature test: submitting with consent creates a `BookingRequest` with `terms_accepted_at`, IP, and user agent populated
- [x] 9.3 Feature test: submitting sends both the owner email and the guest pending-confirmation email
- [x] 9.4 Feature test: terms page renders successfully for each supported locale
- [x] 9.5 Feature test: admin "send confirmation" action sends `BookingConfirmedMail`, sets `confirmation_sent_at`, and BCCs the owner
- [x] 9.6 Feature test: free-cancellation deadline is omitted when check-in is less than 14 days away

## 10. Documentation

- [x] 10.1 Update `docs/specific-tech-backend-doc.mdc` with the new request/consent/email flow
- [x] 10.2 Note in `docs/business-doc.mdc` that direct/private bookings under `max_nights` require the legal consent flow
