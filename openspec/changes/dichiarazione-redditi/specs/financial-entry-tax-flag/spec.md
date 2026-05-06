## ADDED Requirements

### Requirement: Financial entry has a tax declaration flag
The `financial_entries` table SHALL have a boolean column `tax_declaration` (default false). When true, the entry appears on the tax declaration page.

#### Scenario: New entry with flag checked
- **WHEN** an admin creates a financial entry with `tax_declaration` checked
- **THEN** the entry appears on the tax declaration page for the appropriate year

#### Scenario: New entry with flag unchecked (default)
- **WHEN** an admin creates a financial entry without checking `tax_declaration`
- **THEN** the entry does NOT appear on the tax declaration page

---

### Requirement: Financial entry form shows tax declaration checkbox
The financial entry create/edit form SHALL include a checkbox labeled "Includi nella dichiarazione dei redditi" that controls the `tax_declaration` flag. It SHALL be unchecked by default.

#### Scenario: Creating a new financial entry
- **WHEN** the admin opens the new financial entry form
- **THEN** the `tax_declaration` checkbox is present and unchecked by default

#### Scenario: Editing an existing entry with flag set
- **WHEN** the admin opens an existing entry that has `tax_declaration = true`
- **THEN** the checkbox is pre-checked

#### Scenario: Saving with checkbox checked
- **WHEN** the admin submits the form with the checkbox checked
- **THEN** `tax_declaration` is saved as true on the entry record
