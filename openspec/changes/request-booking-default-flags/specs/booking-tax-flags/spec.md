## MODIFIED Requirements

### Requirement: Booking has four tax declaration flags
The `bookings` table SHALL have four boolean columns: `income_tax`, `cleaning_tax`, `linen_tax`, and `parking_tax`. Each controls whether the corresponding paid amount is included in the tax declaration. Newly created bookings SHALL use the values from `config/finance.php` `tax_declaration_defaults`, except that bookings created by converting a website availability request SHALL set `income_tax`, `cleaning_tax`, and `linen_tax` to true. For request conversions, `parking_tax` SHALL retain its configured default. Existing bookings SHALL NOT be changed by this request-conversion behavior.

#### Scenario: Flags on a booking created through another path
- **WHEN** a new booking is created through the admin form or an import
- **THEN** all four flags use the corresponding values from `config/finance.php` `tax_declaration_defaults`

#### Scenario: Flags on a website-request conversion
- **WHEN** a pending website availability request is converted into a booking
- **THEN** `income_tax`, `cleaning_tax`, and `linen_tax` are true and `parking_tax` uses its configured default

#### Scenario: Existing bookings after migration
- **WHEN** the existing tax-flag migration runs on an existing database
- **THEN** all existing bookings get the config-default values for each flag
