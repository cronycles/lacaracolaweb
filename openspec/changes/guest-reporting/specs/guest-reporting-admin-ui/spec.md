## ADDED Requirements

### Requirement: Send form is accessible from booking detail
The system SHALL add an "Alloggiati" action block to the booking detail page (`/admin/prenotazioni/{id}`). The block SHALL contain:
- A link button "Invia schedine Alloggiati" pointing to `admin.guest-reporting.show` for that booking.
- If one or more `GuestReport` records exist for the booking, a compact status row showing the last submission: date, mode (Test/Invia), status badge (Successo/Errore).
- The block is visible only to users with `manage_bookings`.

#### Scenario: Booking with no prior submission
- **GIVEN** a booking that has never had schedine sent
- **WHEN** the admin views the booking detail
- **THEN** only the "Invia schedine" button is shown (no history row)

#### Scenario: Booking with a prior successful send
- **GIVEN** a booking with one `GuestReport` (mode=send, status=success)
- **WHEN** the admin views the booking detail
- **THEN** the block shows the submission date and a green "Successo" badge alongside the "Invia schedine" button

---

### Requirement: Send form pre-fills all guest data
`GET /admin/prenotazioni/{id}/alloggiati` SHALL display the booking summary (guest name, checkin, checkout) and one collapsible panel per guest. For the primary booking guest (`booking.person`), all fields from the `Person` record SHALL be pre-filled. Each panel SHALL contain:

**Read-only (shown for context):**
- Full name, email

**Editable (saved permanently on submit):**
- `gender` (select M/F)
- `birth_municipality` (conditional: dropdown for IT, text for other)
- `birth_province` (text, shown only if birth_country_code == IT)
- `birth_country_code` (select)
- `nationality_code` (select)
- `document_type` (select)
- `document_number` (text)
- `document_issue_place` (text)
- `document_issue_country_code` (select)

**Alloggiati-specific (not saved to Person):**
- `tipo_alloggiato` (select: 16/17/18/19 with full labels) — defaulted to 16 for first guest if nationality is IT, 18 if non-IT; 17/19 for subsequent guests.

> Note on "multiple guests": The `bookings` table links to a single `person_id` (primary guest). The send form supports only the primary guest in V1. If the admin needs to register additional family members, they must be added as separate `Person` records and selected manually. The form SHALL include an "Aggiungi ospite" button that shows a guest-picker (autocomplete from existing `people` records) to add extra rows.

#### Scenario: Opening form for booking with complete guest data
- **GIVEN** a booking whose `Person` has all 7 Alloggiati fields filled
- **WHEN** the admin opens the Alloggiati send form
- **THEN** all fields are pre-filled; `tipo_alloggiato` is pre-selected (16 for Italian primary guest)

#### Scenario: Opening form for booking with incomplete guest data
- **GIVEN** a booking whose `Person` is missing `gender` and `birth_municipality`
- **WHEN** the admin opens the form
- **THEN** those fields are empty and highlighted; the submit buttons are active but validation will fail at save time if still empty

---

### Requirement: Save and test/send flow
Both "Testa bozza" (`POST .../alloggiati/test`) and "Invia definitivamente" (`POST .../alloggiati/send`) SHALL:
1. Validate all required fields (all 7 Alloggiati fields + tipo_alloggiato are required for submit; document_type + document_number required).
2. Persist updated `Person` fields for each guest (permanent save).
3. Build `GuestRecord[]` from validated data.
4. Call `$manager->testDraft()` or `$manager->sendGuests()` respectively.
5. Store a new `GuestReport` record with full payload and response.
6. Redirect back to the send form with a flash message (success or error).

#### Scenario: Successful test
- **GIVEN** all guest data is complete and the SOAP service is reachable
- **WHEN** "Testa bozza" is clicked
- **THEN** a green flash shows "Bozza validata con successo", an `GuestReport` with `mode=test, status=success` is stored, and the form is redisplayed with the submission result details

#### Scenario: Failed send due to SOAP validation error
- **GIVEN** a document number rejected by the SOAP service
- **WHEN** "Invia definitivamente" is clicked
- **THEN** a red flash shows the error message, an `GuestReport` with `mode=send, status=error` is stored, and the per-row error details are shown below the relevant guest panel

#### Scenario: Required field missing at submit time
- **GIVEN** `gender` is empty when the form is submitted
- **THEN** the form returns with a validation error on `gender`; no SOAP call is made; no submission is stored

---

### Requirement: Submission history page
`GET /admin/guest-reporting` SHALL display a paginated table of all `GuestReport` records with columns:
- Data invio
- Prenotazione (link to booking detail, or "—" if `booking_id` is null)
- Ospite principale (from booking → person)
- Driver
- Modalità (Test / Invio badge)
- Stato (Successo / Errore badge)
- N° ospiti
- Azione (link to the booking's Alloggiati form)

No delete or retry actions. Read-only audit log.

#### Scenario: Empty history
- **GIVEN** no submissions have been made yet
- **WHEN** the admin visits `/admin/guest-reporting`
- **THEN** an empty state message is shown: "Nessuna schedina inviata finora."

---

### Requirement: Navigation entry
The admin sidebar SHALL include an "Alloggiati" entry after "Prenotazioni", visible only to users with `manage_bookings`, linking to `admin.guest-reporting.index`.
