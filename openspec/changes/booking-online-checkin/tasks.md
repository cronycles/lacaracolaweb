## 1. Data model

- [x] 1.1 Migration: add `checkin_token` (string, nullable, unique), `checkin_token_expires_at`, `checkin_completed_at` (nullable datetime), `locale` (nullable string, 5) to `bookings`
- [x] 1.2 Migration: add `locale` (nullable string, 5) to `booking_requests`
- [x] 1.3 Update `App\Models\Booking` fillable/casts for the new columns
- [x] 1.4 Update `App\Models\BookingRequest` fillable/casts for `locale`
- [x] 1.5 Update `BookingController::requestAvailability` (public) to store `app()->getLocale()` into the new `BookingRequest.locale` field
- [x] 1.6 Update `docs/specific-data-model.md` with the new columns

## 2. Shared guest-classification logic

- [x] 2.1 Extract `tipo_alloggiato` auto-defaulting from `resources/views/admin/guest-reporting/show.blade.php` into `App\Services\GuestReporting\GuestClassifier::defaultTipoFor(int $index, int $totalGuests): string`
- [x] 2.2 Update `admin/guest-reporting/show.blade.php` to call the shared classifier instead of the inline `@php` block
- [x] 2.3 Extract the guest validation + persistence logic from `GuestReportingController::validateAndPersistGuests` into a shared trait/service reusable by the public controller (without the SOAP driver call)
- [x] 2.4 Add/adjust a regression test asserting the admin form's rendered `tipo_alloggiato` defaults are unchanged after extraction

## 3. Token generation + access

- [x] 3.1 Add `Booking::generateCheckinToken(): string` (random 48+ chars, unique, sets `checkin_token` + `checkin_token_expires_at = checkout`)
- [x] 3.2 Create `App\Http\Controllers\Public\CheckinController` with `show(string $token)` — validates token + expiry, 404/expired page otherwise
- [x] 3.3 Register public route(s) for the check-in page (token-based, not locale-slug-based, e.g. `GET /check-in/{token}`)
- [x] 3.4 Check-in page defaults to `Booking.locale` (fallback to `config('routes.fallback')`); on-page language switcher reloads the page with a `?lang=` override without persisting it

## 4. Public check-in form

- [x] 4.1 Create `resources/views/public/checkin.blade.php` (guest list, add-companion UI, per-guest fields matching the admin form's fields)
- [x] 4.2 Adapt `resources/ts/people-reporting-fields.ts` (or a new public variant) to be i18n-aware (replace hardcoded Italian strings with translation keys passed from Blade)
- [x] 4.3 `CheckinController::store` — validate + persist guest data via the shared service from 2.3, create/attach new `Person` records for companions (reusing the same relation as `BookingGuestController::store`), enforce the total-guest-count cap
- [x] 4.4 `CheckinController::confirm` — explicit "Confirm & submit" action; validates all required fields are complete, then sets `checkin_completed_at`
- [x] 4.5 Allow editing after confirmation (re-open the same form, re-save without needing to reconfirm)
- [x] 4.6 Add translation keys for all check-in form labels/help text/errors to `lang/{it,en,fr,de}`

## 5. Booking-confirmed email link + reminders

- [x] 5.1 Update `BookingConfirmedMail`/`booking-confirmed.blade.php` to generate the token if missing and include the check-in link
- [x] 5.2 Add `config('apartment.checkin.reminder_lead_days', 7)`
- [x] 5.3 Create `App\Mail\CheckinReminderMail` + view (shared branded layout), with translation keys
- [x] 5.4 Create `App\Console\Commands\SendCheckinReminders` (mirrors `SendTelegramBookingReminders` pattern): finds non-canceled bookings with `checkin_completed_at IS NULL` and `checkin` matching today + lead days, sends `CheckinReminderMail`
- [x] 5.5 Register the command in `routes/console.php` via `Schedule::command(...)->dailyAt(...)`

## 6. Tests

- [x] 6.1 Feature test: valid token shows the check-in form; expired/invalid token shows an error page without leaking data
- [x] 6.2 Feature test: single-guest booking defaults to type 16; multi-guest booking defaults to 18 + 20 for companions
- [x] 6.3 Feature test: adding a companion beyond the booking's total guest count is rejected
- [x] 6.4 Feature test: submitting the check-in form persists Person data and never calls the guest-reporting driver
- [x] 6.5 Feature test: confirming sets `checkin_completed_at`; confirmation is rejected when required fields are missing
- [x] 6.6 Feature test: editing after confirmation is still possible while the token is valid
- [x] 6.7 Feature test: `SendCheckinReminders` sends the reminder only for non-canceled, incomplete bookings matching the lead-time date
- [x] 6.8 Feature test: `BookingConfirmedMail` includes a working check-in link and generates a token if missing
- [x] 6.9 Regression test: admin guest-reporting form's `tipo_alloggiato` defaults unchanged after the extraction in group 2

## 7. Documentation

- [x] 7.1 Update `docs/specific-tech-backend-doc.mdc` with the check-in token/endpoints/reminder flow
- [x] 7.2 Update `docs/specific-tech-frontend-doc.mdc` with the public check-in page + reused TS module notes
- [x] 7.3 Update `docs/business-doc.mdc` describing the guest-facing online check-in flow and reminder policy
