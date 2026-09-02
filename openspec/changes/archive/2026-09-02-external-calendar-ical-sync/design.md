## Context

La Caracola is a Laravel monolith with a single rental property. Local availability is represented by active `Booking` records, pending `BookingRequest` records, and manual `AvailabilityBlock` records. The public quote and request flow already share the availability domain, while the admin calendar renders bookings and manual blocks. The application scheduler is defined in `routes/console.php` and is invoked by the production cron every minute.

The change adds bidirectional iCalendar exchange. The local feed must be consumable by external portals, while external feeds must become current, explainable availability blocks without creating local bookings or exposing guest data.

## Goals / Non-Goals

**Goals:**

- Publish a standards-compliant iCalendar v2.0 feed through a public token-protected route.
- Export every active local unavailable interval: active bookings, pending requests, and manual owner/maintenance blocks.
- Manage four fixed external providers (Airbnb, Booking.com, HomeToGo, Google Calendar) from admin Settings.
- Synchronize providers independently on demand and every 15 minutes.
- Parse all-day and date-time `VEVENT` entries, including UTC and timezone-aware values.
- Normalize external events to Europe/Rome calendar days and apply the existing day-range availability semantics.
- Preserve a provider's last valid events when its download or parsing fails.
- Make enabled, successfully synchronized external events affect public availability and appear distinctly in the admin calendar.
- Keep synchronization idempotent and observable through status, timestamps, counts, and error messages.

**Non-Goals:**

- No support for a fifth or user-created provider in this change.
- No full synchronization history, audit log, or notifications.
- No import of external guest names, descriptions, references, or other personal data.
- No conversion of external events into `Booking` or `AvailabilityBlock` rows used for local booking management.
- No time-slot availability: the booking engine continues to decide availability by calendar day.
- No recurrence expansion unless required by the selected parser's safe default; recurring-event support is not a business requirement for the first version.

## Decisions

### Separate provider configuration and current events

Add a provider configuration model/table with a stable key for each of the four providers, URL, enabled flag, synchronization status, last-attempt and last-success timestamps, imported-event count, and latest error. Add a separate current-events table keyed by provider and external UID, containing only normalized start/end dates and the UID needed for idempotent replacement and diagnostics. Store no source summary or description.

Disabling a provider leaves its records available for later re-enabling but excludes them from availability and the admin calendar. A provider that has never completed a successful sync contributes no blocks.

### One synchronization service

Use one application service for provider synchronization. The Artisan command and the admin synchronous action both call the same service, optionally for one provider or all providers. Each provider runs independently. The service marks a provider as syncing, downloads through Laravel's HTTP client with bounded timeout and accepted-success validation, parses the body with `sabre/vobject`, and replaces that provider's current events only after the complete feed is valid.

A valid empty calendar must contain both `BEGIN:VCALENDAR` and `END:VCALENDAR`; it is a successful result with zero events and clears that provider's previous events. HTTP failures, malformed calendars, malformed events, or unsupported required date fields leave prior events untouched and store the error state.

### iCalendar export

Use a dedicated export service and `sabre/vobject` to generate VCALENDAR version 2.0. The route checks the configured token using a constant-time comparison and returns `text/calendar; charset=UTF-8` with `Content-Disposition: attachment; filename=calendar.ics`.

Export only generic blocked events. Use stable UIDs based on the local record type and ID, plus a domain identifier. Include `DTSTAMP`, `DTSTART`, `DTEND`, `SUMMARY:Blocked`, `STATUS:CONFIRMED`, and `TRANSP:OPAQUE` for broad portal compatibility. Do not include names, notes, email addresses, prices, source labels, or request details.

Local configured check-in and check-out times are combined with the relevant dates in Europe/Rome and converted to UTC for output. Date ranges use an inclusive start and exclusive end: the checkout date is not blocked. Same-day records are represented so consumers still receive one blocked calendar day.

### External date normalization

For all-day external events, interpret `DTSTART` as included and `DTEND` as excluded. For date-time events, resolve UTC or `TZID` values into Europe/Rome before deriving dates. If local start and end dates are equal, store a one-day block. Otherwise store the half-open range `[local start date, local end date)`. This makes a same-day Google event block that day while preserving checkout-day availability for multi-day reservations.

Ignore events with `STATUS:CANCELLED` and transparent events. Require a usable UID; malformed individual events make the feed invalid rather than partially replacing trusted data. The parser must support standard line folding and both date-only and date-time forms.

### Availability integration

Extend the existing availability abstraction used by both quote and availability-request validation to include enabled external events with at least one successful sync. Keep the overlap rule consistent with local bookings: a requested `[checkin, checkout)` interval conflicts when it overlaps an external `[start, end)` interval. Recheck availability during the request flow on the server.

### Admin authorization and presentation

Protect provider configuration and manual synchronization with the existing permission pattern, allowing `host_owner` and `super_admin` and excluding `host_keeper`. Use the existing Settings page and backend conventions. Add a provider panel with URL, enabled toggle, manual sync action, status badge, last attempt/success, event count, and latest error.

Render external events in the admin calendar using the existing date-grid conventions with a translucent/differentiated treatment. Expose provider identity and normalized range in a tooltip or detail surface. External records remain read-only and cannot be edited as local bookings.

### Scheduling and documentation

Register the all-provider sync command with `everyFifteenMinutes()` in `routes/console.php`. The existing one-minute server cron is sufficient. Update the data model, backend, frontend, business, and deployment documentation, including the export token, provider URL setup, scheduler requirement, and failure-retention behavior.

## Risks / Trade-offs

- External providers may publish subtly different iCal variants. Strict feed-level validation protects against accidentally deleting trusted blocks, but one malformed event can delay valid updates until the provider feed is corrected.
- A stale but previously valid feed can continue blocking dates after a provider outage. This is safer for preventing double bookings and is visible through the error status.
- A public export URL is intentionally readable by anyone who obtains its token. The feed therefore contains only generic dates and the token must be treated as a secret in deployment.
- Synchronous manual synchronization can take as long as the HTTP timeout for an unavailable provider. Limiting the action to one provider and showing explicit status keeps the behavior understandable; background jobs can be introduced later if needed.
- Date-time external events do not map perfectly to a day-only booking engine. The explicit same-day rule and Europe/Rome normalization make the behavior deterministic and testable.
