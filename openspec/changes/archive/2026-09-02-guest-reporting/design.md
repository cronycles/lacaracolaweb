## Context

The application is a single-property vacation rental manager (La Caracola, Marina di Andora). Italian law (Art. 109 T.U.L.P.S.) requires the host to report every arriving guest to the Polizia di Stato within 24 hours via the **Alloggiati Web** SOAP service at `https://alloggiatiweb.poliziadistato.it/service/service.asmx`.

Current state:
- `people` table stores guest data but lacks fields required by the Alloggiati tracciato record: gender, birth municipality/province/country, nationality, document issue place/country.
- No integration with any external police/registration service exists.
- The admin must manually enter guest data on the external web portal.

The SOAP service requires a short-lived token (60 min) obtained via `GenerateToken`. It supports two operational modes depending on the account type:
- **Single structure**: uses `Test` / `Send` methods and the 170-char positional record format (Tabella 1).
- **Multi-apartment** (`id_appartamento` configured): uses `GestioneAppartamenti_Test` / `GestioneAppartamenti_Send` and the 176-char format (Tabella 2) with the apartment ID embedded at positions 168–173.

---

## Goals / Non-Goals

**Goals:**
- Full Driver/Strategy pattern so the application layer (controllers, views) never references SOAP or positional strings.
- Transparent token management: driver auto-fetches and caches the token; callers never deal with authentication.
- Persistent guest data: editing Alloggiati fields in the send form saves them back to `people` — data is never single-use.
- Submission audit log: every test and send is recorded in `guest_reports` with full payload and response.
- Per-booking send flow: accessible from the booking detail page.
- History page listing all past submissions.
- New fields in the standard guest create/edit form (not buried in a hidden Alloggiati-only form).

**Non-Goals:**
- No scheduled/automatic sending — always manual.
- No PDF generation of the schedina.
- No per-locale/multilingual admin UI for the new section (Italian only, as the rest of the admin).
- No retry mechanism for failed sends.
- No new RBAC permission — reuses `manage_bookings`.

---

## Decisions

### D1 — Driver/Strategy with Manager factory
**Decision:** `GuestReportingDriverInterface` with three methods (`checkConnection`, `testDraft`, `sendGuests`). `GuestReportingManager` resolves the driver from `config('guest-reporting.default')`. `PoliziaStatoAlloggiatiDriver` is the only concrete driver for now.  
**Rationale:** The admin explicitly requested full decoupling. Swapping to a different municipal system or a new police portal in the future requires only a new driver class and updating the config key. The controller never imports any SOAP class.  
**Alternative considered:** Direct SOAP calls in the controller — rejected because it couples UI routing to a specific external API contract.

### D2 — GuestRecord DTO as driver input
**Decision:** A plain PHP `readonly` class `GuestRecord` carrying all fields in **internal app codes** (ISO-2 country codes, `passport`/`id_card` document types, etc.). The driver is responsible for all mappings to external codes.  
**Rationale:** The controller builds `GuestRecord` objects from validated form data using the app's own vocabulary. If tomorrow a different driver uses different code tables, only that driver's mapping layer changes.  
**Fields:**
```
tipo_alloggiato: string  // '16'|'17'|'18'|'19'
last_name: string
first_name: string
gender: string           // 'M'|'F'
birth_date: string       // 'Y-m-d'
birth_municipality: string   // municipality name (Italian) or free text (foreign)
birth_province: ?string      // 2-char abbreviation or null
birth_country_code: string   // ISO 3166-1 alpha-2
nationality_code: string     // ISO 3166-1 alpha-2
document_type: string        // 'passport'|'id_card'|'driving_license'|'residence_permit'|'other'
document_number: string
document_issue_place: string     // municipality name or country name
document_issue_country_code: string  // ISO 3166-1 alpha-2
```

### D3 — Persistent guest data
**Decision:** The send form pre-fills fields from `people`. Any edit in the form saves back to the `Person` record (via a dedicated update call before invoking the driver). Submission is blocked if required Alloggiati fields are missing.  
**Rationale:** Guest data must be maintained correctly for repeat submissions (e.g., same guest arrives next year). Single-use edits would create stale data and require re-entry.  
**Alternative considered:** Edit only for this send, without persisting — rejected by the user.

### D4 — Token cached per driver instance / config key
**Decision:** The token is cached under the key `guest_reporting_token_{driver_key}` using Laravel's Cache facade with a TTL of 55 minutes (5-minute safety margin before the 60-minute expiry).  
**Rationale:** 55-minute TTL avoids edge cases where a token expires mid-request after passing the cache check.

### D5 — Country code mapping hardcoded in the driver
**Decision:** `PoliziaStatoAlloggiatiDriver` contains a private `countryIsoToAlloggiati()` method with a static map of ISO 3166-1 alpha-2 codes to the Alloggiati ISTAT numeric codes (e.g. `'IT' => '100'`, `'DE' => '201'`). Missing entries fall back to a configurable default or throw a driver-level exception.  
**Rationale:** The mapping is stable government data. Hardcoding it in the driver respects the Driver/Strategy boundary — the manager and controller stay ISO-only.  
**Alternative considered:** DB table managed via UI — rejected because the codes are defined by the Ministry and do not change dynamically.

### D6 — Document type mapping hardcoded in the driver
**Decision:** Same pattern as country codes. `PoliziaStatoAlloggiatiDriver` maps internal app codes to Alloggiati 3-char codes:
- `passport` → `APAT` (Passaporto)
- `id_card` → `CRIT` (Carta d'identità)
- `driving_license` → `PATO` (Patente di guida)
- `residence_permit` → `PPAI` (Permesso di soggiorno)
- `other` → `ALTR`

Other drivers can define their own mapping independently.

### D7 — Municipality code for birth place
**Decision:** `birth_municipality` in `people` stores the **text name** of the municipality (e.g. "Genova"). For the Italian ISTAT municipality code required by the tracciato, `PoliziaStatoAlloggiatiDriver` uses a lookup table (static PHP array in a separate file `app/Services/GuestReporting/Data/ItalianMunicipalities.php`) mapping normalized municipality names to their 4-char Codice Belfiore (e.g. `'Genova' => 'D969'`). For foreign citizens, the birth municipality field in the record is filled with spaces and the state code handles location.

> **Note:** The Alloggiati Web tracciato uses the **Codice Belfiore** (4 alphanumeric chars) for Italian municipality of birth, not the 3-digit ISTAT code. Verify against the official WSDL documentation before going live.

### D8 — Birth place for foreign guests
**Decision:** For guests whose `birth_country_code` is not `IT`, the `birth_municipality` field in the positional record is set to spaces, `birth_province` is set to spaces, and `birth_country_code` is mapped to the Alloggiati country code in the "Stato nascita" field.  
**Rationale:** The Alloggiati manual specifies that for non-Italian citizens the municipality and province of birth are not required — only the birth country.

### D9 — Tipo alloggiato: manual selection
**Decision:** In the send form, for each guest the admin manually selects the *tipo alloggiato*:
- `16` — Italiano capofamiglia / capo gruppo
- `17` — Italiano familiare / membro del gruppo
- `18` — Straniero capofamiglia / capo gruppo
- `19` — Straniero familiare / membro del gruppo

The UI shows the full label alongside the code. The selection is not persisted (it depends on the specific arrival group composition) but is pre-suggested based on guest nationality and position.  
**Rationale:** The admin explicitly chose manual selection. Automatic inference (e.g. first guest = capogruppo) would fail for mixed-nationality groups.

### D10 — Submission audit table
**Decision:** `guest_reports` stores: `booking_id` (FK, nullable for connection-test calls), `driver` (string), `mode` (`test`|`send`), `status` (`success`|`error`), `guests_count`, `guests_payload` (JSON), `soap_response` (JSON), `error_message` (nullable text), `submitted_at` (timestamp).  
**Rationale:** Full audit trail is mandatory for compliance. `booking_id` is nullable to allow the `checkConnection()` test which has no associated booking.

### D11 — No new RBAC permission
**Decision:** The new routes are protected by the existing `manage_bookings` permission.  
**Rationale:** Alloggiati submission is a direct consequence of managing a booking. No user with `manage_bookings` should be prevented from sending schedine. Adding a new permission was considered but rejected as unnecessary overhead for a single-apartment app.

### D12 — Tracciato record Tabella 1 positional layout (170 chars)
| Pos | Length | Field | Padding |
|-----|--------|-------|---------|
| 1–2 | 2 | Tipo Alloggiato | numeric string, right-aligned (e.g. `16`) |
| 3–52 | 50 | Cognome | right-padded spaces |
| 53–82 | 30 | Nome | right-padded spaces |
| 83 | 1 | Sesso | `M` or `F` |
| 84–91 | 8 | Data Nascita | `DDMMYYYY` |
| 92–95 | 4 | Comune Nascita | Codice Belfiore (italians) or spaces (foreigners) |
| 96–97 | 2 | Provincia Nascita | 2-char abbrev (italians) or spaces |
| 98–100 | 3 | Stato Nascita | Alloggiati country code |
| 101–103 | 3 | Cittadinanza | Alloggiati country code |
| 104–106 | 3 | Tipo Documento | Alloggiati doc code |
| 107–121 | 15 | Numero Documento | right-padded spaces |
| 122–124 | 3 | Luogo Rilascio | Codice Belfiore (if Italian) or Alloggiati country code |
| 125–168 | 44 | Filler | spaces |
| 169–170 | 2 | Record terminator | `\r\n` |

> **IMPORTANT:** These positions are derived from the prompt specification (170 total = 168 data + 2 CRLF). **Verify the exact field widths and Codice Belfiore usage against the official Alloggiati Web technical manual (WSDL documentation PDF) before sending real data.** The driver uses named constants for all positions to make corrections straightforward.

### D13 — Tracciato record Tabella 2 positional layout (176 chars)
Same as Tabella 1 through position 167, then:
| Pos | Length | Field | Padding |
|-----|--------|-------|---------|
| 168–173 | 6 | ID Appartamento | left-padded zeros |
| 174–175 | 2 | Record terminator | `\r\n` |
| ... | 1 | (TBD — verify to reach 176) | space |

> **IMPORTANT:** The prompt states 176 total chars with apartment ID at positions 168–173. Verify the exact byte layout against the official manual. The driver constant `RECORD_LENGTH_APPARTAMENTI = 176` will cause an assertion error if the built string has a different length, catching format errors at test time.

---

## Architecture Diagram

```
GuestReportingController
     │
     │ builds GuestRecord[]
     ▼
GuestReportingManager ──(resolves from config)──► PoliziaStatoAlloggiatiDriver
                                                     │
                                          ┌──────────┼──────────────┐
                                          ▼          ▼              ▼
                                     Cache       SoapClient    Formatters
                                   (token)     (PHP native)   (positional)
```

---

## How to Add a New Driver

This section is the only thing an agent (or developer) needs to read to swap or add a reporting driver. No controller, view, or model code needs to change.

### Step-by-step

**1. Create the driver class**

```php
// app/Services/GuestReporting/MyNewDriver.php
namespace App\Services\GuestReporting;

use App\Contracts\GuestReportingDriverInterface;

class MyNewDriver implements GuestReportingDriverInterface
{
    public function __construct(private array $config) {}

    public function checkConnection(): bool { /* ... */ }

    public function testDraft(array $guests): SubmissionResult { /* ... */ }

    public function sendGuests(array $guests): SubmissionResult { /* ... */ }
}
```

The driver receives `$config` (the config block for this driver from `config/guest-reporting.php`). It uses `GuestRecord` objects as input — all fields use **internal app codes** (ISO-2 country codes, `passport`/`id_card` doc types, etc.). The driver is solely responsible for mapping them to whatever external format is needed.

**2. Register the driver in config**

```php
// config/guest-reporting.php
'drivers' => [
    'polizia_stato' => [ /* existing */ ],
    'my_new_driver' => [
        'api_key' => env('MY_NEW_DRIVER_API_KEY'),
        // ... driver-specific credentials
    ],
],
```

**3. Register the driver class in GuestReportingManager**

```php
// app/Services/GuestReporting/GuestReportingManager.php
private function resolve(string $driverKey): GuestReportingDriverInterface
{
    return match ($driverKey) {
        'polizia_stato' => new PoliziaStatoAlloggiatiDriver($config),
        'my_new_driver' => new MyNewDriver($config),
        default         => throw new \InvalidArgumentException("Unknown driver: {$driverKey}"),
    };
}
```

**4. Switch the active driver via .env**

```
GUEST_REPORTING_DRIVER=my_new_driver
```

That is all. The `GuestReportingController`, all views, the `GuestReport` model, and the `Person` model are completely unaffected.

### What the driver is responsible for
- Authenticating with the external service (tokens, API keys, sessions).
- Mapping `GuestRecord` internal codes to the service's own codes (country codes, document types, record format).
- Returning a `SubmissionResult` with `success`, `message`, `rowDetails`, and `rawResponse`.

### What the driver must NOT do
- Read from `config/guest-reporting.php` directly — it receives its config block as a constructor argument.
- Access the database.
- Throw unhandled exceptions to the controller — catch transport/API errors internally and return `SubmissionResult::success = false` with a descriptive message.

---

## D14 — Multi-Guest Support (booking_person pivot)

> Decision added 2026-06-30 after initial implementation.

### Problem
A booking can have multiple adults, but the original implementation only sent the primary guest (`booking->person`). The guest-reporting form must allow the admin to select all guests in a booking.

### Decision: pivot table (Option A)

A `booking_person` pivot table links a booking to zero or more additional guests. The primary guest (`bookings.person_id`) is always the capogruppo and is never stored in the pivot.

```
bookings
  id ─────────────────┐
  person_id ──▶ people│  (capogruppo, immutable FK)
                      │
booking_person        │
  booking_id ─────────┘
  person_id ──▶ people  (ospiti aggiuntivi)
  created_at / updated_at
```

**Rejected alternatives:**
- *Option B — `booking_guests` join model*: unnecessary complexity for a simple N:M.
- *Option C — JSON column on bookings*: loses relational integrity, can't be queried.

### New methods on `Booking`

```php
additionalGuests(): BelongsToMany   // pivot only
allGuests(): Collection             // capogruppo + pivot, unique by id
```

### Invariant
`allGuests()` always has the capogruppo as the first element (index 0). The view uses this to apply the correct default `tipo_alloggiato` (16 for Italian capogruppo, 18 for foreign).

### "Includi in questo invio" checkbox
Each guest in `guest-reporting/show.blade.php` has a checkbox (checked by default). Deselected guests are skipped by `validateAndPersistGuests()`. If zero guests are included after filtering, a `ValidationException` is thrown before any SOAP call is made.

### Admin workflow
1. Open booking detail → "Ospiti della prenotazione" section.
2. Search people by name (AJAX autocomplete on `GET /admin/ospiti?format=json&q=...`).
3. Click "Aggiungi" to attach via `POST /prenotazioni/{id}/ospiti`.
4. Click "Rimuovi" to detach via `DELETE /prenotazioni/{id}/ospiti/{person}`.
5. Navigate to "Segnala ospiti" → all guests appear; deselect any to exclude from the SOAP payload.

