## ADDED Requirements

### Requirement: Booking amount and inclusion flags
The booking form SHALL label `income_amount` "Importo prenotazione (€)" and its receipt status "Ingresso ricevuto". It SHALL provide independent flags for included cleaning, linen and parking. All three flags SHALL default to false for newly created bookings, regardless of creation path.

#### Scenario: New booking
- **WHEN** a booking is created through the admin form, a request conversion, or an import
- **THEN** cleaning, linen and parking inclusion flags are all false unless explicitly set

#### Scenario: Amount before receipt
- **WHEN** the booking amount is entered but the receipt status is unchecked
- **THEN** the amount remains available as the agreed booking price and does not create an accounting receipt

#### Scenario: Booking created from a priced request
- **WHEN** an availability request with a quoted house total is converted into a booking
- **THEN** the booking amount is initialized from that house total, not just its stay component, and the inclusion flags remain off

### Requirement: Booking movements represent actual cash flow
The system SHALL recognize booking income only when the booking receipt is checked and dated. It SHALL never create separate cleaning or linen income, regardless of their inclusion flags. Cleaning and linen SHALL each produce an expense only when their own paid status is checked and the shared service payment date is present. Separate parking SHALL produce income only when parking is not included, and its received status is checked and dated. Included parking SHALL never create a separate receipt.

#### Scenario: Included services paid later
- **WHEN** a 1,000 EUR booking receipt includes 100 EUR cleaning, is received on one date, and cleaning is paid on a later date
- **THEN** accounting shows a 1,000 EUR receipt on the receipt date and a 100 EUR cleaning expense on its payment date, without separate cleaning income

#### Scenario: Service paid without an inclusion flag
- **WHEN** cleaning is marked paid with a date but is not marked included
- **THEN** its expense appears and no separate cleaning income appears

#### Scenario: Status or date missing
- **WHEN** a booking payment status is unchecked or its corresponding date is missing
- **THEN** that movement does not appear and does not contribute to accounting totals or balances

#### Scenario: Parking included or separate
- **WHEN** a parking amount is included in a received booking amount
- **THEN** no additional parking receipt is recorded
- **WHEN** the parking amount is not included and parking is checked as received with a date
- **THEN** it appears once as separate parking income on that date

### Requirement: Parking follows an included booking receipt
When parking is included, the parking received status and date SHALL follow the booking receipt status and date, be visible but not independently editable, and stay synchronized when either booking receipt value changes through any supported update path. Removing inclusion SHALL leave the last synchronized status and date as editable starting values.

#### Scenario: Included parking and receipt update
- **WHEN** included parking is selected and the booking receipt is marked received or its date changes
- **THEN** parking reflects the same status and date without a separate accounting movement

#### Scenario: Parking becomes separate
- **WHEN** the owner unchecks included parking
- **THEN** the parking status and date become editable and retain their last synchronized values

### Requirement: Payment and inclusion validation
Saving a booking SHALL require a date for each checked receipt or payment status. The sum of amounts marked included SHALL NOT exceed the booking amount, even before receipt. A missing service amount SHALL count as zero and zero is allowed. A positive included amount with no booking amount SHALL prevent saving. The same rules apply to existing bookings. The system SHALL NOT block a cleaning or linen expense solely because its inclusion flag is off.

#### Scenario: Checked status without a date
- **WHEN** the owner saves a booking with a checked payment status and its date empty
- **THEN** saving is rejected with a clear validation error and entered values are retained

#### Scenario: Included amounts exceed the booking amount
- **WHEN** the included cleaning, linen and parking amounts sum to more than the booking amount
- **THEN** saving is rejected with a clear validation error

#### Scenario: Empty or zero included service
- **WHEN** an included service amount is empty or zero and the included amounts do not exceed the booking amount
- **THEN** that service amount is treated as zero for inclusion validation

### Requirement: Indicative booking net
The booking form SHALL show "Ingresso netto prenotazione" below economic data and before payment statuses, updating as amounts and parking inclusion change. The booking detail SHALL show the same net in place of its current net. The net SHALL equal booking amount plus parking only when not included, minus cleaning and minus linen, independently of payment statuses and cleaning/linen inclusion flags. Empty service amounts count as zero, a missing booking amount is not calculable, and negative values are valid. This value SHALL NOT determine accounting balances.

#### Scenario: Separate parking
- **WHEN** the booking amount is 1,000 EUR, cleaning is 100 EUR, linen is 50 EUR and separate parking is 80 EUR
- **THEN** the indicative net is 930 EUR, regardless of payment statuses

#### Scenario: Included parking
- **WHEN** the same amounts have included parking
- **THEN** the indicative net is 850 EUR

#### Scenario: Missing and negative amounts
- **WHEN** the booking amount is missing
- **THEN** the net is shown as not calculable
- **WHEN** the booking amount is present and an empty service amount is encountered
- **THEN** that service is treated as zero and a negative result is displayed if applicable

### Requirement: Historical bookings are recalculated without invented dates
For each booking existing at migration time, the cleaning inclusion flag SHALL equal its existing cleaning paid status, the linen inclusion flag SHALL equal its existing linen paid status, and parking inclusion SHALL be false. Existing amounts, payment statuses and dates SHALL NOT be changed by this initialization. Historical accounting SHALL be recalculated under the new movement rules.

#### Scenario: Paid service and separate parking
- **WHEN** an existing booking has cleaning paid, linen unpaid and parking recorded
- **THEN** cleaning is marked included, linen is not marked included and parking is not marked included

#### Scenario: Paid status without a date
- **WHEN** an existing paid status has no date
- **THEN** its missing date remains empty and the corresponding movement is not recognized until corrected

### Requirement: Report missing historical payment dates
Before any authorized production migration, the operator SHALL run a read-only diagnostic for booking payment statuses that are checked but lack their corresponding date and SHALL report the booking identifiers and missing date types to the owner. The diagnostic SHALL NOT assign dates or change bookings.

#### Scenario: Historical missing service date
- **WHEN** a booking has cleaning or linen marked paid and no shared service payment date
- **THEN** the diagnostic identifies the booking and each affected service without modifying the record
