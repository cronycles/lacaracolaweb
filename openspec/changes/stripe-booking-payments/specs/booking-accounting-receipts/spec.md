## ADDED Requirements

### Requirement: Verified Stripe receipt preserves booking accounting semantics
The system SHALL record one dated gross booking receipt after a verified Stripe payment. It SHALL NOT mark separate parking, cleaning, or linen payments as received or paid merely because the guest paid the house amount.

#### Scenario: House total paid by Stripe
- **WHEN** a Stripe booking payment is verified
- **THEN** the house amount is marked received with its payment date exactly once and separate parking and service expense statuses remain unchanged

### Requirement: Stripe financial detail is distinct from gross receipts
The system SHALL show Stripe fees, net proceeds, and refunds separately from the gross booking receipt. Financial views SHALL identify refunded amounts and SHALL NOT represent the original gross as the net retained after refunds.

#### Scenario: Fee and partial refund
- **WHEN** a gross receipt has a Stripe fee and a partial refund
- **THEN** an authorized administrator can distinguish gross, fee, net proceeds and refunded amount without duplicating booking income

#### Scenario: Settlement data is delayed
- **WHEN** Stripe has not yet provided the fee or refund details
- **THEN** the financial detail is marked pending rather than assumed to be zero
