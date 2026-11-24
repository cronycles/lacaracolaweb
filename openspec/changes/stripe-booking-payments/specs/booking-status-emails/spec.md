## MODIFIED Requirements

### Requirement: Owner-triggered booking confirmation email with payment instructions
The system SHALL allow the owner to manually trigger an email from the admin `Booking` show page. For an eligible Stripe-approved request, it SHALL contain a private payment link, the agreed amount, an indicative payment deadline, and applicable free-cancellation deadline; it SHALL describe the booking as awaiting payment. Existing non-Stripe and pre-activation bookings SHALL retain their bank-transfer instructions, payment reference, and established email behavior. The owner SHALL be BCC'd.

#### Scenario: Owner sends the confirmation email
- **WHEN** the owner clicks the "Send confirmation email" action on a non-Stripe `Booking` that has not yet had a confirmation email sent
- **THEN** the system sends the guest the confirmation email with bank-transfer instructions, the configured payment deadline, the free-cancellation deadline, BCCs the owner, and records `confirmation_sent_at` on the booking

#### Scenario: Preventing accidental duplicate sends
- **WHEN** the owner attempts to send the confirmation email again for a `Booking` where `confirmation_sent_at` is already set
- **THEN** the system warns the owner before allowing a resend

#### Scenario: Cancellation deadline already passed
- **WHEN** the confirmation email is triggered and the check-in date is less than the applicable free-cancellation window away
- **THEN** the email does not state a free-cancellation deadline

#### Scenario: Approved Stripe request receives payment link
- **WHEN** the owner sends the link for an eligible approved Stripe request
- **THEN** the localized email states that payment is required before final confirmation and shows the agreed amount, indicative payment deadline, and applicable cancellation policy without claiming automatic cancellation

## ADDED Requirements

### Requirement: Verified payment triggers final communications
After a verified, non-conflicting Stripe payment, the system SHALL send the localized guest confirmation with payment and check-in details, notify the owner, and send an operational summary to host keepers without disclosing guest financial amounts.

#### Scenario: Successful payment
- **WHEN** payment for either Stripe booking path is verified and the dates are not in conflict
- **THEN** the guest, owner, and host keepers receive their appropriate messages once

#### Scenario: Conflict or unverified payment
- **WHEN** a Stripe payment is unverified or an external date conflict requires owner intervention
- **THEN** no final guest booking confirmation or host-keeper operational confirmation is sent

### Requirement: Notification delivery recovery
The system SHALL send no automatic expiry notice when the indicative approved-request payment period elapses, no cancellation email when the owner manually cancels an approved unpaid booking, and no abandonment email for direct checkout. It SHALL retain failed transactional notifications for retry and owner visibility.

#### Scenario: Indicative payment period passes
- **WHEN** an approved request remains unpaid past its indicative payment period
- **THEN** no automatic expiry notification is sent and the owner retains control of cancellation

#### Scenario: Owner cancels an unpaid approved booking
- **WHEN** the owner manually cancels an approved unpaid booking
- **THEN** no cancellation email is sent automatically and the owner may contact the guest separately

#### Scenario: Confirmation delivery fails
- **WHEN** a payment is recorded but an email delivery fails
- **THEN** payment remains recorded and the notification can be retried without duplicate accounting
