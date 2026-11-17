## ADDED Requirements

### Requirement: Website request conversion applies request-specific booking flags
When an authorized user confirms a pending website availability request, the system SHALL persist cleaning and linen inclusion flags as true and income, cleaning, and linen tax-declaration flags as true on the created booking. Parking inclusion SHALL retain its existing false default, and the parking tax flag SHALL retain its configured default. These values SHALL be visible as selected in the booking edit form where the corresponding fields are available and SHALL remain editable. This override SHALL NOT change existing bookings or defaults for bookings created through other paths.

#### Scenario: Confirmed request receives the requested defaults
- **WHEN** an authorized user confirms a pending website availability request
- **THEN** the created booking has cleaning and linen included, income/cleaning/linen tax flags enabled, parking not included, and the configured parking tax flag

#### Scenario: Request defaults appear and remain editable
- **WHEN** an authorized user opens the edit form for the newly converted booking
- **THEN** cleaning and linen inclusion are selected, the income/cleaning/linen tax flags are selected for a user with accounting permission, and the owner can change and save the available flags

#### Scenario: Other creation paths and existing bookings are unaffected
- **WHEN** a booking is created through a path other than website-request conversion, or an already existing booking is opened
- **THEN** its flags retain their existing values and creation-path defaults

