## Why

Italian law requires short-term rental hosts to register every guest with the Polizia di Stato within 24 hours of arrival by submitting a "schedina di pubblica sicurezza" to the **Alloggiati Web** portal. Currently the admin has no automated way to do this from within the app — it must be done manually on the external portal. Integrating the SOAP service eliminates that manual step, reduces compliance risk, and ensures that guest data entered in the app is always reused for this purpose.

## What Changes

- **New admin section** `/admin/guest-reporting` for viewing submission history, plus a **per-booking send page** accessible from the booking detail.
- **New fields on `people`** table: `gender`, `birth_municipality`, `birth_province`, `birth_country_code`, `nationality_code`, `document_issue_place`, `document_issue_country_code` — all editable in the standard guest form (create/edit) so data is always persistent and reusable.
- **New table `guest_reports`** for full audit history of every test/send operation.
- **Driver architecture** (`GuestReportingDriverInterface` + `PoliziaStatoAlloggiatiDriver` + `GuestReportingManager`) so the application never talks directly to SOAP — swapping to a different service tomorrow requires only a new driver class and a config key change.
- **Config file** `config/guest-reporting.php` with per-driver credential blocks, selected via `.env`.

## Capabilities

### New Capabilities

- `guest-reporting-send-flow`: From a booking detail page, the admin clicks "Invia schedine Alloggiati", is brought to a pre-filled form showing all guests linked to that booking. The admin fills any missing Alloggiati-specific fields (which are then saved permanently to the guest records), selects the *tipo alloggiato* for each guest, and chooses Test or Send. Results are displayed inline and stored in `guest_reports`.
- `guest-reporting-history`: Admin page `/admin/guest-reporting` listing all past submissions (booking, date, driver, mode, status, guest count).
- `polizia-stato-alloggiati-driver`: SOAP implementation for `alloggiatiweb.poliziadistato.it` with automatic token management (60-min cache), positional record formatting (Tabella 1 = 170 chars, Tabella 2 = 176 chars with apartment ID), and internal mappings for country codes (ISO → ISTAT Alloggiati), document types, and municipality codes.
- `people-reporting-fields`: Seven new fields on the guest record (gender, birth municipality, birth province, birth country, nationality, document issue place, document issue country) visible and editable in the standard guest create/edit form.

### Modified Capabilities

- `people-form`: Guest create/edit form gains a new "Dati per Alloggiati" fieldset with the seven new fields (gender dropdown, birth municipality with conditional dropdown for Italian comuni, province input, country selects, etc.).
- `booking-show`: Booking detail page gains a compact "Segnalazione Ospiti" action block with a link to the send page and, if submissions exist, a badge showing the last submission status.

## Impact

- **DB migrations**: add 7 columns to `people`; create `guest_reports` table.
- **`config/guest-reporting.php`**: new config file — driver key + per-driver credential blocks.
- **`.env`**: new variables `GUEST_REPORTING_DRIVER`, `GUEST_REPORTING_UTENTE`, `GUEST_REPORTING_PASSWORD`, `GUEST_REPORTING_WS_KEY`, `GUEST_REPORTING_ID_APPARTAMENTO`.
- **`app/Contracts/GuestReportingDriverInterface.php`**: new interface.
- **`app/Services/GuestReporting/GuestRecord.php`**: immutable DTO (input to driver).
- **`app/Services/GuestReporting/SubmissionResult.php`**: DTO (output from driver).
- **`app/Services/GuestReporting/AlloggiatiManager.php`**: factory, bound in `AppServiceProvider`.
- **`app/Services/GuestReporting/PoliziaStatoSoapDriver.php`**: full SOAP driver.
- **`app/Models/GuestReport.php`**: new Eloquent model.
- **`app/Models/Person.php`**: updated `$fillable` and `$casts`.
- **`app/Http/Controllers/Admin/PersonController.php`**: updated validation (store + update).
- **`app/Http/Controllers/Admin/GuestReportingController.php`**: new controller (index, show, test, send).
- **`routes/admin.php`**: new route group under `manage_bookings` permission.
- **`resources/views/admin/guest-reporting/index.blade.php`**: submission history.
- **`resources/views/admin/guest-reporting/show.blade.php`**: per-booking send form.
- **`resources/views/admin/people/create.blade.php`** and **`edit.blade.php`**: new fieldset.
- **`resources/views/admin/bookings/show.blade.php`**: new Alloggiati action block.
- **`resources/views/layouts/admin.blade.php`**: new nav item.
- **`docs/specific-data-model.md`**: new table + new columns documented.
- **`docs/specific-tech-backend-doc.mdc`**: new service and controller documented.
- **Permission guard**: `manage_bookings` (existing) — no new permission needed.
