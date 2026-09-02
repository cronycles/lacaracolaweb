# tax-declaration-page Specification

## Purpose
TBD - created by archiving change dichiarazione-redditi. Update Purpose after archive.
## Requirements
### Requirement: Tax declaration page displays year tabs
The system SHALL render a tab for each year that has at least one flagged+paid item (booking or financial entry), matching the styling of the Contabilità year tabs. The current year SHALL be active by default if it has data; otherwise the most recent year with data SHALL be selected.

#### Scenario: Multiple years with data
- **WHEN** the admin navigates to `/admin/dichiarazione-redditi`
- **THEN** the page shows one tab per year that has flagged+paid items, ordered descending

#### Scenario: No data exists
- **WHEN** no flagged+paid items exist for any year
- **THEN** the current calendar year tab is shown and the table is empty

---

### Requirement: Tax declaration page shows income and expense totals
The system SHALL display two summary boxes at the top of the year view:
- **Totale entrate** (green, with + sign): sum of all flagged+paid income items for the year
- **Totale uscite** (red, with − sign): sum of all flagged+paid expense items for the year

Only items where the paid flag is true SHALL contribute to the totals.

#### Scenario: Year with both incomes and expenses
- **WHEN** the admin selects a year with flagged+paid booking incomes and flagged+paid financial entry expenses
- **THEN** the green box shows the correct sum of all income items and the red box shows the correct sum of all expense items

#### Scenario: Flagged but unpaid item
- **WHEN** a booking has `income_tax = true` but `income_paid = false`
- **THEN** that booking's income amount is NOT included in the totals

---

### Requirement: Tax declaration page shows a detailed table
The system SHALL render a table with one row per flagged item (whether paid or not), columns:
- Data (effective paid-at date or checkout/entry_date)
- Tipo (Ingresso / Uscita badge)
- Categoria / Descrizione
- Importo (colored, with +/−)
- Stato (Incluso / Da pagare)
- Link to source (booking detail or entry edit)

Unpaid flagged items SHALL appear in the table with "Da pagare" status but SHALL NOT be counted in the totals.

#### Scenario: Flagged paid booking income row
- **WHEN** a booking has `income_tax = true` and `income_paid = true`
- **THEN** a row appears for that booking with type "Ingresso", the income amount in green, and status "Incluso"

#### Scenario: Flagged unpaid booking cleaning row
- **WHEN** a booking has `cleaning_tax = true` and `cleaning_paid = false`
- **THEN** a row appears for that booking with type "Uscita", the cleaning amount, and status "Da pagare"

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

