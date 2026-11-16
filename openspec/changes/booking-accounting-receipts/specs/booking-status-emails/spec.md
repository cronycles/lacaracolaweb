## ADDED Requirements

### Requirement: Booking emails reflect the booking amount and parking state
Guest confirmation and payment-received emails SHALL use the stored booking amount as "Prezzo totale casa", including before receipt is marked paid, without adding cleaning or linen. When parking has an amount, the emails SHALL show it as a separate descriptive detail. If included, its text SHALL say it is already included in the total price; if separate and actually received with a date, it SHALL say already paid; otherwise it SHALL say payable locally. The operational email SHALL NOT ask the host keeper to collect parking that is included or already received. The public quote engine SHALL remain unchanged.

#### Scenario: Parking included
- **WHEN** the booking amount is 1,000 EUR and includes 80 EUR parking
- **THEN** the guest email shows house total 1,000 EUR and parking 80 EUR as already included, without asking for payment on arrival

#### Scenario: Parking paid separately
- **WHEN** separate parking is marked received with a date
- **THEN** the guest and operational emails indicate that parking has already been paid rather than requesting payment locally

#### Scenario: Separate parking not yet received
- **WHEN** parking is separate and has not been marked received with a date
- **THEN** the emails indicate payment locally

#### Scenario: Confirmation before receipt
- **WHEN** the booking amount is present but its receipt status is unchecked
- **THEN** the confirmation email still shows that amount as the house total
