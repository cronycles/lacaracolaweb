## ADDED Requirements

### Requirement: Providers synchronize independently
The system SHALL download and process each enabled provider independently through both a scheduled Artisan command and a synchronous admin action.

#### Scenario: Scheduled synchronization runs every fifteen minutes
- **WHEN** Laravel's scheduler evaluates the calendar synchronization schedule
- **THEN** the all-provider synchronization command is due every 15 minutes
- **AND** one provider failure does not prevent other providers from being attempted

#### Scenario: Authorized user synchronizes one provider manually
- **WHEN** a `host_owner` or `super_admin` presses the provider's manual synchronization action
- **THEN** the request synchronously runs the same synchronization service used by the scheduler
- **AND** the result is shown in that provider's Settings panel

### Requirement: Valid iCalendar feeds replace current provider events atomically
The system SHALL accept standard iCalendar feeds containing `VEVENT` entries with usable UIDs and supported date fields. A successful non-empty or empty feed SHALL replace all current events for that provider only after the complete feed is validated.

#### Scenario: Booking-style all-day feed is imported
- **WHEN** a valid feed contains `DTSTART;VALUE=DATE` and exclusive `DTEND;VALUE=DATE`
- **THEN** the event is stored as a normalized day range with the start included and end excluded
- **AND** the event UID is retained for idempotent replacement

#### Scenario: Google-style UTC event is imported
- **WHEN** a valid feed contains UTC `DTSTART` and `DTEND` values
- **THEN** the values are converted through `Europe/Rome`
- **AND** the resulting event blocks the normalized local day range

#### Scenario: Same-day timed event blocks one day
- **WHEN** a valid timed event starts and ends on the same local Europe/Rome date
- **THEN** the provider event stores that date as a one-day block

#### Scenario: Valid empty calendar clears a provider
- **WHEN** a feed contains `BEGIN:VCALENDAR` and `END:VCALENDAR` and no events
- **THEN** synchronization succeeds
- **AND** all previous current events for that provider are removed

### Requirement: Invalid or failed feeds preserve trusted data
The system SHALL treat download failures, non-success HTTP responses, malformed calendars, malformed required events, missing UIDs, and unsupported required date fields as synchronization errors.

#### Scenario: Failed provider retains previous events
- **WHEN** a provider download or parse fails after it has previously synchronized successfully
- **THEN** its previous current events remain unchanged
- **AND** its status records the failure and error message
- **AND** other providers continue independently

### Requirement: Canceled and transparent events are ignored
The parser SHALL ignore events with `STATUS:CANCELLED` or transparent transport and SHALL not import their date ranges.

#### Scenario: Canceled event is excluded
- **WHEN** a valid feed contains a canceled or transparent event
- **THEN** that event is absent from the provider's current events
- **AND** other valid events may still be imported
