## 1. Foundation and dependencies

- [x] 1.1 Add `sabre/vobject` as the PHP iCalendar parsing/generation dependency and refresh Composer metadata.
- [x] 1.2 Add configuration for the export token, calendar timezone, configured check-in/check-out times, HTTP timeout, and the four fixed provider keys.
- [x] 1.3 Add migrations for external calendar provider configuration/state and current provider events, including unique provider/UID and availability indexes.
- [x] 1.4 Add `ExternalCalendarProvider` and `ExternalCalendarEvent` models with casts, relationships, fillable fields, and enabled/successful-sync query helpers.
- [x] 1.5 Add model factories or test helpers needed to create provider configurations and external events in feature tests.

## 2. Local iCalendar export

- [x] 2.1 Implement a dedicated service that collects active bookings, pending requests, and manual owner/maintenance blocks while excluding canceled, declined, and converted duplicate records.
- [x] 2.2 Implement generic blocked-event generation with stable UIDs, UTC `DTSTART`/`DTEND`, `DTSTAMP`, `SUMMARY`, `STATUS`, and `TRANSP`, without personal data.
- [x] 2.3 Handle exclusive checkout dates and same-day blocks in the export representation so importing portals preserve the intended blocked days.
- [x] 2.4 Add the public export controller and `/api/calendar/export?t=TOKEN` route with constant-time token validation and the required calendar attachment headers.
- [x] 2.5 Add feature tests for valid/invalid tokens, headers, iCalendar validity, exported record types, exclusions, privacy, UTC conversion, and same-day behavior.

## 3. External feed parsing and synchronization

- [x] 3.1 Implement an iCalendar parser adapter for folded lines, all-day events, UTC events, timezone-aware events, UIDs, cancellation, and transparent transport.
- [x] 3.2 Normalize external event values to Europe/Rome day ranges, including the same-day timed-event rule and exclusive multi-day end date.
- [x] 3.3 Implement an HTTP feed client with bounded timeout, successful-response validation, and useful provider-scoped errors.
- [x] 3.4 Implement the provider synchronization service with syncing/success/error state transitions and independent provider execution.
- [x] 3.5 Replace a provider's current events atomically only after complete feed validation; preserve previous events on failures and clear them for a valid empty calendar.
- [x] 3.6 Add the Artisan synchronization command with optional single-provider selection and clear console results.
- [x] 3.7 Register the all-provider command in `routes/console.php` with a 15-minute schedule.
- [x] 3.8 Add unit and feature tests using representative Booking.com and Google Calendar fixtures for parsing, timezone conversion, filtering, idempotency, empty feeds, failures, and provider isolation.

## 4. Admin provider settings

- [x] 4.1 Add provider configuration fields and validation to the existing admin Settings flow, including URL and enabled state for all four providers.
- [x] 4.2 Enforce `host_owner` and `super_admin` authorization for provider configuration and manual synchronization, excluding `host_keeper`.
- [x] 4.3 Add synchronous per-provider manual synchronization actions that reuse the scheduled synchronization service.
- [x] 4.4 Add Settings UI panels with URL, enabled toggle, sync action, status badge, timestamps, imported count, and latest error.
- [x] 4.5 Add tests for authorized/unauthorized access, persistence, validation, manual sync results, and status rendering data.

## 5. Availability integration

- [x] 5.1 Extend the shared public availability query/service to include enabled providers with at least one successful synchronization.
- [x] 5.2 Apply half-open day-range overlap rules and same-day external blocks consistently with existing bookings and manual blocks.
- [x] 5.3 Recheck external conflicts in the availability-request submission path before persisting a request or pending block.
- [x] 5.4 Add tests proving quote rejection, request rejection, checkout-day availability, disabled-provider exclusion, and never-synchronized-provider exclusion.

## 6. Admin calendar presentation

- [x] 6.1 Load enabled external events for the visible calendar window without changing local booking/block records.
- [x] 6.2 Render external blocked days with a distinct translucent visual treatment and provider identity.
- [x] 6.3 Expose normalized date range and provider details through the calendar tooltip/detail surface.
- [x] 6.4 Add frontend/admin tests or focused regression coverage for visibility, disabled providers, and coexistence with local bookings and manual blocks.

## 7. Documentation and deployment

- [ ] 7.1 Update `docs/specific-data-model.md` with provider configuration/state and external event tables and relationships.
- [ ] 7.2 Update `docs/specific-tech-backend-doc.mdc` with routes, services, authorization, availability behavior, parser rules, and scheduler registration.
- [ ] 7.3 Update `docs/specific-tech-frontend-doc.mdc` with Settings controls and calendar visualization behavior.
- [ ] 7.4 Update `docs/business-doc.mdc` with external calendar exchange and blocking rules.
- [ ] 7.5 Update `docs/DEPLOY.md` with `CALENDAR_EXPORT_TOKEN`, the existing scheduler cron requirement, provider setup, and operational error handling.
- [ ] 7.6 Run the focused test suite, static checks, and the full application test suite; record any pre-existing unrelated failures separately.
