## Why

La Caracola needs a reliable calendar exchange with its external booking portals. The site must publish every date that is unavailable and consume the four external iCal feeds so the public booking flow cannot accept dates already occupied elsewhere.

## What Changes

- Add a public token-protected iCal v2.0 export endpoint at `/api/calendar/export?t=TOKEN`.
- Export active bookings, pending booking requests, and manual owner/maintenance blocks without guest personal data.
- Add four configurable external providers: Airbnb, Booking.com, HomeToGo, and Google Calendar.
- Add provider URL and enabled/disabled controls to the admin Settings page, available to `host_owner` and `super_admin`.
- Add synchronous per-provider manual synchronization and an automatic synchronization every 15 minutes.
- Parse valid external iCal feeds, normalize events to Europe/Rome calendar days, and persist current provider events idempotently.
- Preserve the previous provider events after HTTP, download, or parsing failures; treat a valid empty calendar as a successful clear.
- Include enabled, successfully synchronized external blocks in public availability checks and the admin calendar.
- Show external blocks with a distinct translucent treatment and provider information, without turning them into local bookings.
- Add synchronization status, timestamps, imported-event counts, and the latest error to the Settings page.
- Add focused automated tests and update backend, data-model, deployment, business, and frontend documentation.

## Capabilities

### New Capabilities

- `ical-calendar-export`: Public iCalendar v2.0 export of all local unavailable periods using a configured token and UTC date-times.
- `external-calendar-providers`: Admin-managed configuration and synchronization status for the four external calendar providers.
- `external-calendar-sync`: Scheduled and manual downloading, validation, parsing, and idempotent replacement of external events.
- `external-calendar-availability`: Day-level availability blocking and admin-calendar visualization for enabled external events.

### Modified Capabilities

None.

## Impact

- Laravel routes, controllers, services, models, migrations, configuration, scheduler, and admin Settings/calendar views.
- The existing public quote and booking-request availability path must consume external blocks.
- A new PHP iCalendar parsing dependency, preferably `sabre/vobject`, will be added through Composer.
- The database will gain provider configuration/state and current external-event records; no full event history is required.
- Tests will cover iCal output, token handling, provider isolation, feed validation, timezone normalization, idempotent synchronization, availability conflicts, and authorization.
