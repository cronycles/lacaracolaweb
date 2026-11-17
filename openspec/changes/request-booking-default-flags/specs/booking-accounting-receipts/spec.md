## MODIFIED Requirements

### Requirement: Booking amount and inclusion flags
The booking form SHALL label `income_amount` "Importo prenotazione (€)" and its receipt status "Ingresso ricevuto". It SHALL provide independent flags for included cleaning, linen and parking. For newly created bookings, all inclusion flags SHALL default to false except that a booking created by converting a website availability request SHALL default cleaning and linen inclusion to true. Parking inclusion SHALL default to false for every creation path. The flags SHALL remain editable.

#### Scenario: New booking through another creation path
- **WHEN** a booking is created through the admin form or an import
- **THEN** cleaning, linen and parking inclusion flags are all false unless explicitly set by that creation path

#### Scenario: Website request conversion
- **WHEN** a pending website availability request is converted into a booking
- **THEN** cleaning and linen inclusion flags are true and parking inclusion is false

#### Scenario: Amount before receipt
- **WHEN** the booking amount is entered but the receipt status is unchecked
- **THEN** the amount remains available as the agreed booking price and does not create an accounting receipt

#### Scenario: Booking created from a priced request
- **WHEN** an availability request with a quoted house total is converted into a booking
- **THEN** the booking amount is initialized from that house total, not just its stay component, and cleaning and linen inclusion are selected
