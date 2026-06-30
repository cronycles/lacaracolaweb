## 1. DB Migrations

- [x] 1.1 Create migration: add 7 columns to `people` table — `gender` (char 1, nullable), `birth_municipality` (varchar 100, nullable), `birth_province` (char 2, nullable), `birth_country_code` (char 3, nullable), `nationality_code` (char 3, nullable), `document_issue_place` (varchar 100, nullable), `document_issue_country_code` (char 3, nullable)
- [x] 1.2 Create migration: create `guest_reports` table with columns: `id`, `booking_id` (unsignedBigInteger, nullable, FK people RESTRICT), `driver` (varchar 60), `mode` (enum: `test`, `send`), `status` (enum: `success`, `error`), `guests_count` (tinyint unsigned), `guests_payload` (json), `soap_response` (json, nullable), `error_message` (text, nullable), `submitted_at` (timestamp), `created_at`, `updated_at`

## 2. Configuration

- [x] 2.1 Create `config/guest-reporting.php` with `default` key (from env `GUEST_REPORTING_DRIVER`, default `polizia_stato_soap`) and `drivers` array containing a `polizia_stato_soap` block with keys: `utente`, `password`, `ws_key`, `id_appartamento` (all from `.env`)
- [x] 2.2 Add new `.env` variables to `.env.example`: `GUEST_REPORTING_DRIVER`, `GUEST_REPORTING_UTENTE`, `GUEST_REPORTING_PASSWORD`, `GUEST_REPORTING_WS_KEY`, `GUEST_REPORTING_ID_APPARTAMENTO`

## 3. Contracts and DTOs

- [x] 3.1 Create `app/Contracts/GuestReportingDriverInterface.php` with methods: `checkConnection(): bool`, `testDraft(array $guests): SubmissionResult`, `sendGuests(array $guests): SubmissionResult` (where `$guests` is `GuestRecord[]`)
- [x] 3.2 Create `app/Services/GuestReporting/GuestRecord.php` as a `readonly` class with all fields documented in Design D2
- [x] 3.3 Create `app/Services/GuestReporting/SubmissionResult.php` as a `readonly` class with: `success: bool`, `message: string`, `rowDetails: array`, `rawResponse: ?string`

## 4. GuestReportingManager

- [x] 4.1 Create `app/Services/GuestReporting/GuestReportingManager.php` that reads `config('alloggiati.default')`, instantiates the corresponding driver passing its config block, and implements `GuestReportingDriverInterface` by delegation
- [x] 4.2 Bind `GuestReportingDriverInterface` to `GuestReportingManager` in `AppServiceProvider` as a singleton

## 5. PoliziaStatoAlloggiatiDriver

- [x] 5.1 Create `app/Services/GuestReporting/PoliziaStatoAlloggiatiDriver.php` implementing `GuestReportingDriverInterface`
- [x] 5.2 Implement `getToken()` private method: check Cache for key `guest_reporting_token_polizia_stato_soap`, call `GenerateToken` SOAP method if missing, store result with 55-minute TTL, return token string
- [x] 5.3 Implement `checkConnection()`: call `Authentication_Test` SOAP method with stored token; return bool based on esito
- [x] 5.4 Implement `buildRecord(GuestRecord $guest): string` private method: formats the positional string (Tabella 1 or Tabella 2 depending on `id_appartamento` config), using named constants for each field's start position and length
- [x] 5.5 Implement `testDraft(array $guests): SubmissionResult`: build record strings, call `Test` or `GestioneAppartamenti_Test`, parse per-row response, return `SubmissionResult`
- [x] 5.6 Implement `sendGuests(array $guests): SubmissionResult`: build record strings, call `Send` or `GestioneAppartamenti_Send`, parse response, return `SubmissionResult`
- [x] 5.7 Add private `countryIsoToAlloggiati(string $iso): string` with full static map covering at least: IT, DE, FR, ES, GB, IE, NL, CH, AT, BE, PT, SE, NO, DK, FI, PL, CZ, SK, HU, RO, BG, HR, SI, US, CA, AU, NZ, JP, CN, BR, AR + throw on unknown with fallback
- [x] 5.8 Add private `documentTypeToAlloggiati(string $type): string` mapping internal codes to APAT/CRIT/PATO/PPAI/ALTR
- [x] 5.9 Create `app/Services/GuestReporting/Data/ItalianMunicipalities.php` returning a static array of normalized municipality name → Codice Belfiore (include the ~50 most common Italian municipalities; document that the full list can replace this file)

## 6. GuestReport Model

- [x] 6.1 Create `app/Models/GuestReport.php` with `$fillable`, `$casts` (guests_payload and soap_response as `array`), and a `booking()` belongsTo relation

## 7. Person Model and Controller Update

- [x] 7.1 Add the 7 new fields to `$fillable` in `app/Models/Person.php`
- [x] 7.2 Add `gender` to `$casts` (keep as string; no special cast needed)
- [x] 7.3 Update `PersonController::store()` validation rules: add nullable rules for all 7 new fields; `gender` in `[M, F]`; country codes as `nullable|string|size:2`; municipality/place as `nullable|string|max:100`
- [x] 7.4 Update `PersonController::update()` with same validation rules

## 8. People Views Update

- [x] 8.1 Add "Dati per segnalazione" fieldset to `resources/views/admin/people/create.blade.php` with all 7 new fields: gender (select M/F), birth_municipality (text with JS conditional: if birth_country_code == IT show searchable dropdown from ItalianMunicipalities list, else plain text), birth_province (text 2 chars), birth_country_code (select from config country list), nationality_code (select), document_issue_place (text), document_issue_country_code (select)
- [x] 8.2 Same fieldset added to `resources/views/admin/people/edit.blade.php`
- [x] 8.3 Add TypeScript module `resources/ts/people-reporting-fields.ts`: watches `birth_country_code` change, toggles municipality input between searchable select (Italian comuni) and plain text field

## 9. GuestReportingController

- [x] 9.1 Create `app/Http/Controllers/Admin/GuestReportingController.php`
- [x] 9.2 Implement `index()`: list all `GuestReport` records with booking eager-loaded, paginated, ordered by `submitted_at DESC`
- [x] 9.3 Implement `show(Booking $booking)`: load booking with all related `people` (primary guest + any additional guests from booking data), pass to view
- [x] 9.4 Implement `saveAndTest(Request $request, Booking $booking)`: validate + persist updated person fields, build `GuestRecord[]`, call `$manager->testDraft()`, store result in `guest_reports`, return redirect with result
- [x] 9.5 Implement `saveAndSend(Request $request, Booking $booking)`: same as above but call `$manager->sendGuests()`, set `mode = 'send'`

## 10. Routes

- [x] 10.1 Add to `routes/admin.php` inside `permission:manage_bookings` group:
  - `GET  /admin/guest-reporting` → `GuestReportingController@index` named `admin.guest-reporting.index`
  - `GET  /admin/prenotazioni/{prenotazioni}/alloggiati` → `GuestReportingController@show` named `admin.guest-reporting.show`
  - `POST /admin/prenotazioni/{prenotazioni}/alloggiati/test` → `GuestReportingController@saveAndTest` named `admin.guest-reporting.test`
  - `POST /admin/prenotazioni/{prenotazioni}/alloggiati/send` → `GuestReportingController@saveAndSend` named `admin.guest-reporting.send`

## 11. Alloggiati Views

- [x] 11.1 Create `resources/views/admin/guest-reporting/index.blade.php`: table with columns Booking (link), Date, Driver, Mode (badge test/send), Status (badge success/error), Guests, Action (details); paginated
- [x] 11.2 Create `resources/views/admin/guest-reporting/show.blade.php`: shows booking summary at top; one panel per guest with all Alloggiati fields pre-filled and editable; tipo_alloggiato select per guest with smart default (16/18 for first guest based on nationality, 17/19 for subsequent); "Testa bozza" and "Invia definitivamente" action buttons; if prior submissions exist for this booking, show a history table at the bottom

## 12. Booking Detail View Update

- [x] 12.1 Add an "Alloggiati" action block to `resources/views/admin/bookings/show.blade.php`: link button "Invia schedine"; if `guest_reports` exist for this booking, show last submission date, mode, and status badge

## 13. Navigation

- [x] 13.1 Add "Alloggiati" nav item to `resources/views/layouts/admin.blade.php` sidebar, visible only to users with `manage_bookings`, linking to `admin.guest-reporting.index`

## 14. Documentation Update

- [x] 14.1 Update `docs/specific-data-model.md`: add 7 new columns to the `people` entity; add the full `guest_reports` entity description
- [x] 14.2 Update `docs/specific-tech-backend-doc.mdc`: add `GuestReportingManager` and `PoliziaStatoAlloggiatiDriver` to the service map; add `GuestReportingController` endpoints to the route documentation; document the Driver/Strategy pattern
