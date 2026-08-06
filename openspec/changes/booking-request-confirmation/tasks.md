## 1. Data model

- [x] 1.1 Migration: add `declined_at` (nullable datetime) to `booking_requests`
- [x] 1.2 Update `App\Models\BookingRequest` fillable/casts for `declined_at`, add `booking()` usage helpers if needed (e.g. `scopePending`)
- [x] 1.3 Update `docs/specific-data-model.md` with the new column and the now-populated `booking_request_id` usage

## 2. Shared guest-matching logic

- [x] 2.1 Extract the email → phone → exact-name matching + enrichment logic from `BookingCreationService::createFromParsed()` into a new public method, e.g. `BookingCreationService::findOrCreatePerson(array $data): Person` (accepting `first_name`, `last_name`, `email`, `phone`)
- [x] 2.2 Update `createFromParsed()` to call the extracted method instead of the inline logic (no behavior change)
- [x] 2.3 Add/adjust a regression test asserting the Interhome import flow's guest matching is unchanged after extraction

## 3. Admin queue + confirm/decline actions

- [x] 3.1 Add `BookingRequest::scopePending(Builder $query): Builder` (whereNull `declined_at`, whereDoesntHave `booking`)
- [x] 3.2 Create `App\Http\Controllers\Admin\BookingRequestController` with `index()` (pending queue, oldest-first, with a read-only match preview per row via `findOrCreatePerson`-style lookup that never persists), `confirm(BookingRequest $bookingRequest)`, `decline(BookingRequest $bookingRequest)`
- [x] 3.3 `confirm()`: find-or-create the `Person` (via 2.1's method), create the `Booking` (`checkin`, `checkout`, `adults`, `children`, `locale`, `source = 'direct'`, `booking_request_id`), create the matching `AvailabilityBlock`, redirect to `admin.bookings.edit`
- [x] 3.4 `decline()`: set `declined_at = now()`, redirect back to the queue
- [x] 3.5 Register routes under `permission:manage_bookings`: `GET /admin/richieste` (index), `POST /admin/richieste/{bookingRequest}/conferma` (confirm), `POST /admin/richieste/{bookingRequest}/rifiuta` (decline)

## 4. Admin UI

- [x] 4.1 Create `resources/views/admin/booking-requests/index.blade.php` (queue table: name, contact, dates, guests, message, match preview, Conferma/Rifiuta buttons)
- [x] 4.2 Add "Richieste" entry to the admin sidebar nav with a pending-count badge (`BookingRequest::pending()->count()`)
- [x] 4.3 On the booking show/edit page, surface the linked `BookingRequest` (if any) for traceability (e.g. original message, submission date)

## 5. Tests

- [x] 5.1 Feature test: pending queue lists undeclined, unconfirmed requests only, oldest-first
- [x] 5.2 Feature test: confirming creates a `Booking` linked via `booking_request_id`, an `AvailabilityBlock`, empty financial fields, and redirects to the edit page
- [x] 5.3 Feature test: confirming matches an existing `Person` by email/phone/exact name instead of duplicating
- [x] 5.4 Feature test: confirming enriches a matched `Person`'s missing email/phone
- [x] 5.5 Feature test: declining sets `declined_at` and removes the request from the queue without creating a booking
- [x] 5.6 Feature test: users without `manage_bookings` cannot access the queue or confirm/decline
- [x] 5.7 Regression test: Interhome PDF import guest-matching behavior unchanged after the extraction in group 2

## 6. Documentation

- [x] 6.1 Update `docs/specific-tech-backend-doc.mdc` with the new controller/routes/matching-reuse notes
- [x] 6.2 Update `docs/specific-tech-frontend-doc.mdc` with the new admin queue view/menu entry
- [x] 6.3 Update `docs/business-doc.mdc` describing the pending-request queue and confirm/decline flow
