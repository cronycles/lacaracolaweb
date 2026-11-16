# tax-declaration-page Specification

## Purpose
TBD - created by archiving change dichiarazione-redditi. Update Purpose after archive.
## Requirements
### Requirement: Tax declaration page displays year tabs
The system SHALL render a tab for each year that has at least one flagged, paid and dated booking movement or flagged financial entry, matching the styling of the Contabilità year tabs. The current year SHALL be active by default if it has data; otherwise the most recent year with data SHALL be selected.

#### Scenario: Multiple years with data
- **WHEN** the admin navigates to `/admin/dichiarazione-redditi`
- **THEN** the page shows one tab per year with eligible movements, ordered descending

#### Scenario: No data exists
- **WHEN** no eligible items exist for any year
- **THEN** the current calendar year tab is shown and the table is empty

---

### Requirement: Tax declaration page shows income and expense totals
The system SHALL display annual "Totale entrate" and "Totale uscite" summary boxes. Booking movements SHALL contribute only when their own payment check and date are present and their respective tax flag is enabled. Booking income uses the full booking amount; cleaning and linen are expenses only; parking is separate income only when not included. Flagged financial entries retain their existing behavior.

#### Scenario: Included services and parking
- **WHEN** a tax-flagged received booking amount includes cleaning, linen and parking
- **THEN** the booking amount is counted once as income, with no separate service or parking income

#### Scenario: Tax-flagged service expense
- **WHEN** cleaning is tax-flagged and marked paid with a date
- **THEN** its amount is counted as an expense on that date, regardless of its inclusion flag

#### Scenario: Missing date or unchecked payment
- **WHEN** a booking item lacks its payment date or has its payment status unchecked
- **THEN** it does not contribute to either total

#### Scenario: Included service and unflagged booking income
- **WHEN** a service is included but the booking income tax flag is off
- **THEN** no separate income for that service is counted

---

### Requirement: Tax declaration page shows a detailed table
The system SHALL render a table with one row per eligible dated and paid booking movement or flagged financial entry, including date, type, category, description, amount, status, attachments and link to source. Booking movements without their own paid check, date or tax flag SHALL NOT appear. Service income rows and included parking income rows SHALL NOT appear.

#### Scenario: Eligible booking income row
- **WHEN** booking income is tax-flagged, received and dated
- **THEN** one income row appears on that receipt date with the booking amount and "Incluso" status

#### Scenario: Unpaid or undated booking cleaning
- **WHEN** cleaning is tax-flagged but unpaid or undated
- **THEN** no cleaning movement row appears

#### Scenario: Flagged financial entry
- **WHEN** a `financial_entry` has `tax_declaration = true`
- **THEN** it appears in the table with the entry's type (income/expense) and amount

---

### Requirement: Tax declaration page accessible via tab in accounting area
The system SHALL add a second tab "Dichiarazione redditi" in the accounting section nav, alongside the existing "Contabilità" tab, visible on both pages. The tab SHALL respect the `view_accounting` permission.

#### Scenario: Admin with view_accounting permission
- **WHEN** the admin with `view_accounting` visits `/admin/contabilita`
- **THEN** both "Contabilità" and "Dichiarazione redditi" tabs are visible

#### Scenario: Admin without view_accounting permission
- **WHEN** a user without `view_accounting` accesses `/admin/dichiarazione-redditi`
- **THEN** the request is rejected (403 or redirect)

