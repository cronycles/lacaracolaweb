## ADDED Requirements

### Requirement: Confirm action creates a linked booking with empty financials
The system SHALL let an authorized user confirm a pending `BookingRequest`, creating a `Booking` linked to it via `booking_request_id`, with `checkin`, `checkout`, `adults`, `children`, and `locale` copied from the request, `source` set to `direct`, and every financial field (pricing, cleaning, linen, parking) left empty for manual completion.

#### Scenario: Confirming creates a linked booking
- **WHEN** an authorized user confirms a pending request
- **THEN** a `Booking` is created with `booking_request_id` pointing to that request, matching dates/guests/locale, and no financial fields pre-filled

#### Scenario: Confirming creates the matching availability block
- **WHEN** an authorized user confirms a pending request
- **THEN** an `AvailabilityBlock` covering the booking's dates is created, consistent with manually-created bookings

#### Scenario: Unauthorized user cannot confirm
- **WHEN** a user without `manage_bookings` permission attempts to confirm a request
- **THEN** the system denies the action

### Requirement: Guest matching reuses the existing find-or-create strategy
Confirming a request SHALL identify the guest `Person` using the same strategy already used for Interhome imports: match by email, then by phone, then by exact first+last name; if none match, create a new `Person`; if a match is found, enrich any missing email/phone from the request data.

#### Scenario: Matches an existing guest by email
- **WHEN** the request's email matches an existing `Person`'s email
- **THEN** confirming links the booking to that existing person instead of creating a duplicate

#### Scenario: Creates a new guest when no match is found
- **WHEN** no existing `Person` matches the request's email, phone, or exact full name
- **THEN** confirming creates a new `Person` from the request's name/email/phone

#### Scenario: Enriches an existing guest's missing contact info
- **WHEN** an existing `Person` is matched but is missing an email or phone that the request provides
- **THEN** confirming fills in the missing email/phone on that `Person`

### Requirement: Confirming redirects to the booking edit page
After a successful confirmation, the system SHALL redirect the user to the created booking's edit page, so pricing and services can be completed immediately if desired.

#### Scenario: Redirect after confirmation
- **WHEN** an authorized user confirms a pending request
- **THEN** the system redirects to that booking's edit page
