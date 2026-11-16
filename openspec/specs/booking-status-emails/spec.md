# booking-status-emails Specification

## Purpose
TBD - created by archiving change prenotazione-privata-legal-checkbox. Update Purpose after archive.
## Requirements
### Requirement: Owner notification email states consent was given
The email sent to the owner upon a new availability request (`BookingRequestMail`) SHALL state that the guest accepted the House Rules and the Short-Term Tourist Lease Agreement, including the acceptance timestamp.

#### Scenario: Owner sees consent confirmation
- **WHEN** the owner receives the availability request notification email
- **THEN** the email body includes a statement confirming the guest accepted the terms, with the date/time of acceptance

### Requirement: Guest receives a pending-confirmation email
Immediately after a valid availability request is submitted, the system SHALL send the guest an email clearly stating the booking is **not yet confirmed** and is pending the owner's written confirmation.

#### Scenario: Guest submits a request
- **WHEN** a guest successfully submits the availability request form
- **THEN** the guest receives an email, in their current locale, stating the request was received and that confirmation from the owner is still pending

### Requirement: Owner-triggered booking confirmation email with payment instructions
The system SHALL allow the owner to manually trigger, from the admin `Booking` show page, a "booking confirmed" email to the guest containing: a restatement of accepted terms, payment instructions (IBAN/beneficiary from configuration) with a copy-pasteable payment reference (the stay dates), a 48-hour payment deadline computed from the send time, and the free-cancellation deadline computed as check-in date minus 14 days. The owner is BCC'd on this email.

#### Scenario: Owner sends the confirmation email
- **WHEN** the owner clicks the "Send confirmation email" action on a `Booking` that has not yet had a confirmation email sent
- **THEN** the system sends the guest the confirmation email with payment instructions and deadlines, BCCs the owner, and records `confirmation_sent_at` on the booking

#### Scenario: Preventing accidental duplicate sends
- **WHEN** the owner attempts to send the confirmation email again for a `Booking` where `confirmation_sent_at` is already set
- **THEN** the system warns the owner before allowing a resend

#### Scenario: Cancellation deadline already passed
- **WHEN** the confirmation email is triggered and the check-in date is less than 14 days away
- **THEN** the email does not state a free-cancellation deadline (since it has already elapsed)

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

