# booking-legal-consent Specification

## Purpose
TBD - created by archiving change prenotazione-privata-legal-checkbox. Update Purpose after archive.
## Requirements
### Requirement: Mandatory consent checkbox on the availability request form
The public availability request form (`booking-form` component) SHALL display a mandatory checkbox, placed immediately above the submit button, whose label links to the House Rules page and to the General Terms / Short-Term Tourist Lease Agreement page for the current locale, generated via `route_locale()`.

#### Scenario: Checkbox unchecked disables submission
- **WHEN** a visitor loads the availability request form and has not checked the consent checkbox
- **THEN** the submit button is disabled and the form cannot be submitted

#### Scenario: Checking the box enables submission
- **WHEN** a visitor checks the consent checkbox
- **THEN** the submit button becomes enabled

#### Scenario: Server rejects request without consent
- **WHEN** a `POST` request to the availability request endpoint is made without the consent field marked as accepted (e.g. direct API call bypassing the UI)
- **THEN** the server responds with a validation error and does not send any email or create any record

### Requirement: Availability requests are persisted with proof of consent
Every valid availability request SHALL be persisted in a `booking_requests` table, capturing the requester's contact and stay details, the timestamp of terms acceptance (`terms_accepted_at`), the requester's IP address, and user agent.

#### Scenario: Successful request creates a booking_requests record
- **WHEN** a visitor submits the availability request form with the consent checkbox checked and valid data
- **THEN** a new `booking_requests` row is created with `terms_accepted_at` set to the submission time, along with the request's IP address and user agent

### Requirement: Admin bookings can be linked back to the originating request
The `bookings` table SHALL support an optional link to the `booking_requests` record that originated it, via a nullable `booking_request_id` foreign key.

#### Scenario: Owner links a manually created booking to its request
- **WHEN** an owner creates a `Booking` in the admin panel for a guest who previously submitted an availability request
- **THEN** the owner can associate that `Booking` with the corresponding `booking_requests` record, and the original consent data (checkbox acceptance, timestamp, IP) remains viewable from the booking

