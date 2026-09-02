# guest-reporting-driver Specification

## Purpose
TBD - created by archiving change guest-reporting. Update Purpose after archive.
## Requirements
### Requirement: Driver interface decouples application from SOAP
The system SHALL define `GuestReportingDriverInterface` with exactly three public methods. No controller, model, or view SHALL import any SOAP-specific class. All SOAP coupling is contained within `PoliziaStatoAlloggiatiDriver`.

```php
interface GuestReportingDriverInterface
{
    public function checkConnection(): bool;

    /** @param GuestRecord[] $guests */
    public function testDraft(array $guests): SubmissionResult;

    /** @param GuestRecord[] $guests */
    public function sendGuests(array $guests): SubmissionResult;
}
```

#### Scenario: Swapping to a new driver
- **GIVEN** a new class `NewMunicipalDriver` implementing `GuestReportingDriverInterface`
- **WHEN** `GUEST_REPORTING_DRIVER=new_municipal` is set in `.env` and the driver is registered in `config/guest-reporting.php`
- **THEN** the controller and views work without modification

---

### Requirement: Token is fetched and cached transparently
Before every SOAP operation, `PoliziaStatoAlloggiatiDriver` SHALL check Cache for key `guest_reporting_token_polizia_stato_soap`. If absent or expired, it SHALL call `GenerateToken(Utente, Password, WsKey)` and store the returned token with a **55-minute TTL**.

#### Scenario: First call after cache miss
- **GIVEN** no token in cache
- **WHEN** `testDraft()` is called
- **THEN** `GenerateToken` is called once, the token is cached, and the operation proceeds

#### Scenario: Subsequent call within TTL
- **GIVEN** a valid token in cache
- **WHEN** `testDraft()` is called again
- **THEN** `GenerateToken` is NOT called; the cached token is reused

#### Scenario: Token generation failure
- **GIVEN** invalid credentials in config
- **WHEN** any driver method is called
- **THEN** a driver-level exception is thrown with the SOAP error message; `SubmissionResult::success` is `false` and `SubmissionResult::message` describes the auth failure

---

### Requirement: Positional record formatter respects Tabella 1 / Tabella 2
`buildRecord(GuestRecord $guest)` SHALL produce a string of exactly 170 chars (Tabella 1, no `id_appartamento` configured) or 176 chars (Tabella 2, `id_appartamento` present). The driver SHALL assert the string length and throw if it does not match.

Field encoding rules:
- **String fields**: right-padded with ASCII spaces to the declared length; truncated if longer.
- **Date fields**: formatted as `DDMMYYYY` (8 chars).
- **Numeric ID (Tabella 2 only)**: `id_appartamento` left-padded with zeros to 6 chars.
- **Record terminator**: `\r\n` (2 chars) appended to every record **except the last** when building `ElencoSchedine`.

#### Scenario: Single Italian guest (Tabella 1)
- **GIVEN** a `GuestRecord` for an Italian capofamiglia with all fields populated
- **WHEN** `buildRecord()` is called
- **THEN** the returned string is exactly 170 chars, `tipo_alloggiato` occupies positions 1–2, `cognome` occupies 3–52, etc.

#### Scenario: Apartment mode (Tabella 2)
- **GIVEN** `GUEST_REPORTING_ID_APPARTAMENTO=42` in config and a `GuestRecord`
- **WHEN** `buildRecord()` is called
- **THEN** the returned string is exactly 176 chars and positions 168–173 contain `000042`

#### Scenario: String too long
- **GIVEN** a last name with 55 characters
- **WHEN** `buildRecord()` is called
- **THEN** the last name is truncated to 50 chars and the total record length is still correct

---

### Requirement: Country code mapping ISO → Alloggiati ISTAT
`countryIsoToAlloggiati(string $iso)` SHALL map ISO 3166-1 alpha-2 codes to the Alloggiati 3-char numeric code. It SHALL cover at minimum: `IT`, `DE`, `FR`, `ES`, `GB`, `IE`, `NL`, `CH`, `AT`, `BE`, `PT`, `SE`, `NO`, `DK`, `FI`, `PL`, `CZ`, `SK`, `HU`, `RO`, `BG`, `HR`, `SI`, `US`, `CA`, `AU`, `NZ`, `JP`, `CN`, `BR`, `AR`. For unmapped codes it SHALL throw a `\RuntimeException` with a descriptive message.

#### Scenario: Known code
- **GIVEN** ISO code `DE`
- **WHEN** `countryIsoToAlloggiati('DE')` is called
- **THEN** returns `'201'`

#### Scenario: Unknown code
- **GIVEN** ISO code `XX`
- **WHEN** `countryIsoToAlloggiati('XX')` is called
- **THEN** throws `\RuntimeException('Unmapped ISO country code: XX')`

---

### Requirement: Document type mapping
`documentTypeToAlloggiati(string $type)` SHALL map internal app codes to Alloggiati 3-char document codes:

| Internal | Alloggiati | Description |
|----------|-----------|-------------|
| `passport` | `APAT` | Passaporto |
| `id_card` | `CRIT` | Carta d'identità |
| `driving_license` | `PATO` | Patente di guida |
| `residence_permit` | `PPAI` | Permesso di soggiorno |
| `other` | `ALTR` | Altro |

Unknown types SHALL map to `ALTR`.

#### Scenario: Known document type mapped
- **GIVEN** internal type `passport`
- **WHEN** `documentTypeToAlloggiati('passport')` is called
- **THEN** it returns `APAT`

#### Scenario: Unknown type falls back to ALTR
- **GIVEN** internal type `foo`
- **WHEN** `documentTypeToAlloggiati('foo')` is called
- **THEN** it returns `ALTR`

---

### Requirement: SubmissionResult captures full SOAP response
`SubmissionResult` SHALL contain:
- `success: bool` — true if SOAP operation returned no errors
- `message: string` — human-readable summary (Italian, shown directly in admin UI)
- `rowDetails: array` — array of per-row objects with `row`, `esito`, `descrizione` (from SOAP response array)
- `rawResponse: ?string` — JSON-encoded raw SOAP response for audit storage

#### Scenario: Test with validation errors
- **GIVEN** a guest record with a malformed document number
- **WHEN** `testDraft()` is called
- **THEN** `success` is `false`, `rowDetails` contains the specific per-row error from the SOAP service, `message` summarises the first error

