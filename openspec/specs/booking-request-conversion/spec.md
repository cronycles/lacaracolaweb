# booking-request-conversion Specification

## Purpose
TBD - created by archiving change booking-request-confirmation. Update Purpose after archive.
## Requirements
### Requirement: Confirm action creates a linked booking with request estimates when available
The system SHALL let an authorized user confirm a pending `BookingRequest`, creating a `Booking` linked to it via `booking_request_id`, with `checkin`, `checkout`, `adults`, `children`, and `locale` copied from the request and `source` set to `direct`. When available, the request's estimated house total, cleaning, linen, and parking amounts SHALL be copied into the booking; missing estimates SHALL remain empty for manual completion.

#### Scenario: Confirming creates a linked booking
- **WHEN** an authorized user confirms a pending request
- **THEN** a `Booking` is created with `booking_request_id` pointing to that request, matching dates/guests/locale, and financial amounts copied from the request estimates when available

#### Scenario: Confirming creates the matching availability block
- **WHEN** an authorized user confirms a pending request
- **THEN** an `AvailabilityBlock` covering the booking's dates is created, consistent with manually-created bookings

#### Scenario: Unauthorized user cannot confirm
- **WHEN** a user without `manage_bookings` permission attempts to confirm a request
- **THEN** the system denies the action

### Requirement: Website request conversion applies request-specific booking flags
When an authorized user confirms a pending website availability request, the system SHALL persist cleaning and linen inclusion flags as true and income, cleaning, and linen tax-declaration flags as true on the created booking. Parking inclusion SHALL retain its existing false default, and the parking tax flag SHALL retain its configured default. These values SHALL be visible as selected in the booking edit form where the corresponding fields are available and SHALL remain editable. This override SHALL NOT change existing bookings or defaults for bookings created through other paths.

#### Scenario: Confirmed request receives the requested defaults
- **WHEN** an authorized user confirms a pending website availability request
- **THEN** the created booking has cleaning and linen included, income/cleaning/linen tax flags enabled, parking not included, and the configured parking tax flag

#### Scenario: Request defaults appear and remain editable
- **WHEN** an authorized user opens the edit form for the newly converted booking
- **THEN** cleaning and linen inclusion are selected, the income/cleaning/linen tax flags are selected for a user with accounting permission, and the owner can change and save the available flags

#### Scenario: Other creation paths and existing bookings are unaffected
- **WHEN** a booking is created through a path other than website-request conversion, or an already existing booking is opened
- **THEN** its flags retain their existing values and creation-path defaults

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

