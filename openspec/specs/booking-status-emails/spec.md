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

