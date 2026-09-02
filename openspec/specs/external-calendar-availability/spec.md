# external-calendar-availability Specification

## Purpose
TBD - created by archiving change external-calendar-ical-sync. Update Purpose after archive.
## Requirements
### Requirement: External events block public availability by day
The public availability domain SHALL consider enabled providers with at least one successful synchronization. An external event range SHALL use the same half-open day interval as local bookings: start included and checkout/end excluded, except a same-day event blocks that one day.

#### Scenario: Quote rejects an external conflict
- **WHEN** a requested `[checkin, checkout)` interval overlaps an enabled external event
- **THEN** the quote endpoint reports the stay as unavailable

#### Scenario: Availability request rechecks external conflicts
- **WHEN** an external event overlaps a submitted availability request
- **THEN** the server rejects the request before creating a booking request or pending availability block

#### Scenario: Checkout day remains available
- **WHEN** an external multi-day event ends on a requested check-in date
- **THEN** the requested stay beginning on that date does not conflict solely because of the external event

### Requirement: Admin calendar displays external blocks distinctly
The admin calendar SHALL display enabled external events using a visually distinct translucent treatment and identify the provider and normalized date range in the event detail or tooltip.

#### Scenario: External block is visible with provider identity
- **WHEN** an enabled provider has successfully imported an event within the calendar view
- **THEN** the event is visible in the corresponding blocked days
- **AND** its provider identity and date range are available to an admin user

#### Scenario: Disabled or never-synchronized provider is absent
- **WHEN** a provider is disabled or has never completed a successful sync
- **THEN** its events are not rendered in the admin calendar
- **AND** they do not affect public availability

### Requirement: External events remain separate from local bookings
The system SHALL not create or mutate local `Booking` records when importing external events.

#### Scenario: Imported event does not become a booking
- **WHEN** an external event is synchronized successfully
- **THEN** only external provider/event records are updated
- **AND** an owner can create a local booking independently after reviewing the external block

