# booking-tax-flags Specification

## Purpose
TBD - created by archiving change dichiarazione-redditi. Update Purpose after archive.
## Requirements
### Requirement: Booking has four tax declaration flags
The `bookings` table SHALL have four boolean columns: `income_tax`, `cleaning_tax`, `linen_tax`, and `parking_tax`. Each controls whether the corresponding paid amount is included in the tax declaration. Newly created bookings SHALL use the values from `config/finance.php` `tax_declaration_defaults`, except that bookings created by converting a website availability request SHALL set `income_tax`, `cleaning_tax`, and `linen_tax` to true. For request conversions, `parking_tax` SHALL retain its configured default. Existing bookings SHALL NOT be changed by this request-conversion behavior.

#### Scenario: Flags on a booking created through another path
- **WHEN** a new booking is created through the admin form or an import
- **THEN** all four flags use the corresponding values from `config/finance.php` `tax_declaration_defaults`

#### Scenario: Flags on a website-request conversion
- **WHEN** a pending website availability request is converted into a booking
- **THEN** `income_tax`, `cleaning_tax`, and `linen_tax` are true and `parking_tax` uses its configured default

#### Scenario: Existing bookings after migration
- **WHEN** the migration runs on an existing database
- **THEN** all existing bookings get the config-default values for each flag

---

### Requirement: Booking detail shows tax declaration section
The booking detail page (`/admin/prenotazioni/{id}`) SHALL display a "Dichiarazione dei redditi" sub-section at the bottom of the financial data area. This section SHALL contain one checkbox per financial field that has a non-null amount (income, cleaning, linen, parking). Unchecking a box marks that amount as excluded from the tax declaration.

#### Scenario: Booking with income and cleaning amounts
- **WHEN** an admin views a booking that has `income_amount` and `cleaning_amount` set
- **THEN** the tax section shows two checkboxes: "Incasso" and "Pulizie", each reflecting the current flag value

#### Scenario: Booking with no financial amounts set
- **WHEN** all amounts are null on the booking
- **THEN** the tax declaration section is not shown or is empty

#### Scenario: Admin saves a changed flag
- **WHEN** the admin toggles a checkbox and submits the form
- **THEN** the corresponding `*_tax` column is updated on the booking record

---

### Requirement: Booking tax flags only editable with accounting permission
The tax flag checkboxes on the booking detail page SHALL only be visible and submittable by users with the `view_accounting` permission.

#### Scenario: User without accounting permission
- **WHEN** a user without `view_accounting` views a booking detail
- **THEN** the "Dichiarazione dei redditi" section is not rendered

