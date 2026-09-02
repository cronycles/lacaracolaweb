# ical-calendar-export Specification

## Purpose
TBD - created by archiving change external-calendar-ical-sync. Update Purpose after archive.
## Requirements
### Requirement: Public token-protected calendar export
The system SHALL expose `GET /api/calendar/export?t=TOKEN` as a public endpoint that returns an iCalendar version 2.0 document when the configured export token matches.

#### Scenario: Valid token returns an iCalendar attachment
- **WHEN** a client requests the export route with the configured token
- **THEN** the response has status 200, `Content-Type: text/calendar; charset=UTF-8`, and `Content-Disposition: attachment; filename=calendar.ics`
- **AND** the body contains a valid `VCALENDAR` with `VERSION:2.0`

#### Scenario: Invalid or missing token is rejected
- **WHEN** a client requests the export route without the configured token or with a different token
- **THEN** the endpoint returns an authorization failure
- **AND** it does not return calendar data

### Requirement: Export all local unavailable periods without personal data
The exported calendar SHALL contain active bookings, pending booking requests, and manual owner/maintenance blocks. Canceled bookings, declined requests, and requests already converted into bookings SHALL not create an additional event.

#### Scenario: Active local records are exported
- **WHEN** the calendar is generated with active bookings, pending requests, and manual blocks
- **THEN** each unavailable interval is represented by a blocked event
- **AND** a converted request appears only through its linked active booking

#### Scenario: Canceled and resolved records are excluded
- **WHEN** a booking is canceled, a request is declined, or a request is converted into a booking
- **THEN** the canceled/declined/request representation is absent from the export
- **AND** an active linked booking remains exportable where applicable

#### Scenario: Feed contains no guest details
- **WHEN** any local record has guest names, contact data, notes, prices, or source information
- **THEN** none of those values appear in the calendar body
- **AND** event summaries remain generic, such as `Blocked`

### Requirement: Export dates in UTC with exclusive checkout
The system SHALL combine configured local check-in/check-out times with local dates in `Europe/Rome`, convert the resulting date-times to UTC, and represent multi-day intervals with an included start and excluded checkout date.

#### Scenario: Multi-day booking is converted to UTC
- **WHEN** an active booking has local check-in and checkout dates and configured times
- **THEN** `DTSTART` and `DTEND` represent those Europe/Rome date-times in UTC
- **AND** the checkout date is not represented as an occupied night

#### Scenario: Same-day block remains one blocked day
- **WHEN** a manual block starts and ends on the same date
- **THEN** the exported event is represented so an importing portal still blocks that calendar day

