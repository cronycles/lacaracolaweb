# people-reporting-fields Specification

## Purpose
TBD - created by archiving change guest-reporting. Update Purpose after archive.
## Requirements
### Requirement: New Alloggiati fields visible in standard guest form
The system SHALL add a "Dati per segnalazione" fieldset to both the guest create and edit forms. The fieldset SHALL contain:
- `gender`: select field with options M (Maschio) and F (Femmina), nullable
- `birth_municipality`: conditional input — searchable dropdown when `birth_country_code == IT`, plain text input otherwise
- `birth_province`: text input (2 chars), shown only when `birth_country_code == IT`
- `birth_country_code`: select from the same country list already used elsewhere in the app (`config/apartment.php` guest_countries)
- `nationality_code`: select from the same country list
- `document_issue_place`: text input (municipality name or country name)
- `document_issue_country_code`: select from country list

All fields are optional (nullable) so existing guest creation flows are not broken.

#### Scenario: Creating a new Italian guest
- **GIVEN** the admin opens `/admin/ospiti/create`
- **WHEN** they select `IT` in the `birth_country_code` dropdown
- **THEN** the `birth_municipality` input switches to a searchable select of Italian comuni and the `birth_province` field becomes visible

#### Scenario: Creating a new foreign guest
- **GIVEN** the admin opens `/admin/ospiti/create`
- **WHEN** they select a non-IT country in `birth_country_code`
- **THEN** `birth_municipality` is a plain text field and `birth_province` is hidden/disabled

#### Scenario: Saving incomplete Alloggiati fields
- **GIVEN** a guest form with `gender` and other Alloggiati fields left blank
- **WHEN** the admin submits
- **THEN** the guest is saved successfully — Alloggiati fields are nullable

---

### Requirement: Alloggiati fields are persisted and reusable
The system SHALL store all 7 new fields in the `people` table. Any changes made through the standard guest edit form or through the Alloggiati send form SHALL be saved permanently to the `Person` record.

#### Scenario: Editing guest before Alloggiati send
- **GIVEN** a guest with `birth_municipality` empty
- **WHEN** the admin fills `birth_municipality` in the standard edit form and saves
- **THEN** the `people` record is updated and the field is pre-filled the next time the Alloggiati form is opened for any booking with this guest

#### Scenario: Re-sending schedine for returning guest
- **GIVEN** a guest whose Alloggiati data was completed during a previous booking's send
- **WHEN** the same guest has a new booking and the admin opens the Alloggiati send form
- **THEN** all fields are pre-filled from the stored `Person` record, requiring no re-entry

---

### Requirement: Italian municipality conditional UI
The system SHALL load the Italian comuni list from `app/Services/GuestReporting/Data/ItalianMunicipalities.php` and render it client-side as a searchable select when the birth country is IT. The TypeScript module `people-reporting-fields.ts` SHALL handle the toggling.

#### Scenario: Switching birth country from IT to FR
- **GIVEN** `birth_country_code` is `IT` and a comune is selected
- **WHEN** the admin changes `birth_country_code` to `FR`
- **THEN** `birth_municipality` becomes a plain text field (previous selection is cleared) and `birth_province` is hidden

