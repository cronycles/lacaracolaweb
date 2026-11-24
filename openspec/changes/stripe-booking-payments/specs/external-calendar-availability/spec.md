## ADDED Requirements

### Requirement: Escalate external calendar conflicts with Stripe bookings
The system SHALL detect overlapping external events for pending or paid Stripe bookings, both when payment is finalized and when external calendars later synchronize. It SHALL alert the owner by email and configured Telegram and expose the conflict for manual resolution.

#### Scenario: External event arrives before payment finalization
- **WHEN** a Stripe payment succeeds but an overlapping external event is already known
- **THEN** the payment is recorded, final booking communications are withheld, and the owner is alerted to resolve the conflict and any refund

#### Scenario: External event arrives after confirmation
- **WHEN** a later external-calendar synchronization reports an overlap with an already confirmed Stripe booking
- **THEN** the conflict is recorded and the owner is alerted without silently cancelling or refunding either booking

#### Scenario: Repeated synchronization
- **WHEN** the same unresolved conflict is observed again
- **THEN** the system does not create duplicate conflict records or repeatedly send the same initial alert
