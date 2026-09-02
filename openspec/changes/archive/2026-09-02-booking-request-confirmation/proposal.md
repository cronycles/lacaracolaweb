## Why

Today a public availability request (`BookingRequest`) is invisible in the admin panel — the owner only learns about it via the `BookingRequestMail` notification email. To confirm a stay, the owner manually creates a brand-new `Booking` from scratch in `admin/bookings/create`, re-typing the guest's name, dates, and guest counts that were already submitted in the request form. The `booking_request_id` column on `bookings` exists (added in `prenotazione-privata-legal-checkbox`) but is never populated anywhere in the codebase, so there is no way to trace a confirmed booking back to the request it originated from, and no way to see which requests are still pending action.

## What Changes

- Add an admin queue of pending `BookingRequest` records (not yet linked to a `Booking`, not declined) with a dedicated "Richieste" menu entry.
- Each row previews which `Person` will be used if confirmed — an existing profile (matched by email, then phone, then exact first+last name, reusing the exact matching strategy already implemented in `App\Services\BookingCreationService` for Interhome PDF imports) or a newly created one — computed read-only, without persisting anything.
- Add a "Conferma" action: finds-or-creates the `Person` (enriching missing email/phone on an existing match, same as `BookingCreationService`), creates the `Booking` (person, dates, guests, `locale`, `booking_request_id` link, source `direct`) with all financial fields left empty/default (the owner fills pricing/cleaning/linen/parking manually afterward, as today), creates the matching `AvailabilityBlock`, then redirects straight to the booking's edit page for the owner to review/complete before optionally sending the confirmation email.
- Add a "Rifiuta" action: marks the request as declined (new `declined_at` column) so it drops out of the pending queue without creating a booking, for requests handled outside the system (phone/WhatsApp) or not accepted.
- The pending queue and confirm/decline actions require `manage_bookings` permission, consistent with existing booking write actions.

## Capabilities

### New Capabilities
- `booking-request-review`: Admin-facing queue of pending booking requests, with a read-only preview of the `Person` that would be matched/created, and a "Rifiuta" action to remove a request from the queue without creating a booking.
- `booking-request-conversion`: The "Conferma" action that finds-or-creates the guest `Person`, creates the linked `Booking` + `AvailabilityBlock` with empty financials, and redirects to the booking for manual completion.

### Modified Capabilities
(none — no existing `openspec/specs/` capabilities to modify)

## Impact

- **Backend**: new `App\Http\Controllers\Admin\BookingRequestController` (or similar); reuse/extend `App\Services\BookingCreationService`'s person-matching logic (currently private to `createFromParsed`, needs to be reusable for `BookingRequest` data too, which has no `pets`/`babies`/`source`/`external_ref`); new migration adding `declined_at` (nullable datetime) to `booking_requests`; new admin routes gated by `permission:manage_bookings`.
- **Frontend**: new admin view (queue list) + a new sidebar menu entry "Richieste" (with a pending-count badge).
- **Data model**: `booking_requests.declined_at` (nullable); `bookings.booking_request_id` starts being populated (column already exists, unused until now).
- **Docs**: `docs/specific-data-model.md`, `docs/specific-tech-backend-doc.mdc`, `docs/specific-tech-frontend-doc.mdc`, `docs/business-doc.mdc`.
