## ADDED Requirements

### Requirement: Pending booking-request queue in admin
The system SHALL show, in a dedicated admin area, every `BookingRequest` that has not been declined and is not yet linked to a `Booking`, ordered oldest-first.

#### Scenario: Pending request appears in the queue
- **WHEN** a public availability request is submitted and neither declined nor confirmed yet
- **THEN** it appears in the admin "Richieste" queue

#### Scenario: Confirmed request no longer appears
- **WHEN** a request has been confirmed into a `Booking`
- **THEN** it no longer appears in the pending queue

#### Scenario: Declined request no longer appears
- **WHEN** a request has been declined
- **THEN** it no longer appears in the pending queue

### Requirement: Person-match preview per pending request
For each pending request, the system SHALL show, without persisting anything, whether confirming it would reuse an existing `Person` (matched by email, then phone, then exact first+last name) or create a new one.

#### Scenario: Preview shows an existing match
- **WHEN** a pending request's email matches an existing `Person`'s email
- **THEN** the queue row shows that existing person's name as the match

#### Scenario: Preview shows a new profile will be created
- **WHEN** a pending request's email, phone, and full name all fail to match any existing `Person`
- **THEN** the queue row indicates a new profile will be created

### Requirement: Decline a pending request
The system SHALL let an authorized user mark a pending request as declined, removing it from the queue without creating a `Booking`.

#### Scenario: Declining removes it from the queue
- **WHEN** an authorized user declines a pending request
- **THEN** the request's `declined_at` is set and it no longer appears in the pending queue

#### Scenario: Unauthorized user cannot decline
- **WHEN** a user without `manage_bookings` permission attempts to decline a request
- **THEN** the system denies the action
