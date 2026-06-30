## ADDED Requirements

### Requirement: Every SOAP operation is recorded
The system SHALL create one `GuestReport` record after every `testDraft()` or `sendGuests()` call, regardless of outcome (success or error).

#### Scenario: Successful send
- **GIVEN** `sendGuests()` returns `SubmissionResult::success = true`
- **THEN** a record is stored with `mode=send`, `status=success`, `guests_count` matching the array length, `guests_payload` containing the full `GuestRecord[]` data as JSON, `soap_response` containing the raw SOAP response, `error_message = null`

#### Scenario: SOAP error during test
- **GIVEN** `testDraft()` throws or returns `SubmissionResult::success = false`
- **THEN** a record is stored with `mode=test`, `status=error`, `error_message` containing the driver error string, `soap_response` containing whatever partial response was received (or null if a transport error occurred)

---

### Requirement: GuestReport model schema
Table `guest_reports`:

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT PK | Auto-increment |
| `booking_id` | BIGINT FK, nullable | References `bookings.id` RESTRICT; null for connection-test calls |
| `driver` | VARCHAR(60) | e.g. `polizia_stato_soap` |
| `mode` | ENUM(`test`, `send`) | |
| `status` | ENUM(`success`, `error`) | |
| `guests_count` | TINYINT UNSIGNED | |
| `guests_payload` | JSON | Array of guest data objects sent to the driver |
| `soap_response` | JSON, nullable | Parsed SOAP response (EsitoOperazioneServizio + per-row details) |
| `error_message` | TEXT, nullable | Human-readable error (transport or SOAP-level) |
| `submitted_at` | TIMESTAMP | Set at creation time |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**No soft deletes** — audit records are immutable.

---

### Requirement: Submission linked to booking detail
The booking detail page (`bookings/show.blade.php`) SHALL display the most recent `GuestReport` for the booking if one exists (eager-loaded via `$booking->alloggiatiSubmissions()->latest('submitted_at')->first()`).

#### Scenario: Link from history to booking
- **GIVEN** an `GuestReport` with `booking_id = 42`
- **WHEN** the admin views the history page
- **THEN** the "Prenotazione" column shows a link to `/admin/prenotazioni/42`
