## Purpose

Provide secure, reusable Stripe payments for direct bookings, with reliable operational and financial visibility.

## ADDED Requirements

### Requirement: Shared checkout and payment amount
The system SHALL use one payment capability for owner-approved and immediate bookings. It SHALL charge the agreed house total in EUR, calculated or confirmed server-side, excluding separately payable parking and additional customer-facing Stripe fees.

#### Scenario: Both booking paths pay the house total
- **WHEN** a guest starts payment through either supported path
- **THEN** the same payment capability charges the authorized house amount and identifies parking as payable locally

#### Scenario: Amount is changed in the browser
- **WHEN** a visitor manipulates a submitted price
- **THEN** the system rejects or ignores it and uses the server-authorized amount

### Requirement: Secure embedded payment page
The system SHALL offer an embedded Stripe checkout within a localized site page and request cards, Apple Pay, Google Pay, and PayPal when supported and available. It SHALL never collect or persist card details itself.

#### Scenario: Wallet or PayPal requires additional steps
- **WHEN** an available payment method requires device eligibility or a redirect for authorization
- **THEN** the guest can complete the provider's authentication and return to the site without exposing payment credentials to the site

#### Scenario: Payment method unavailable
- **WHEN** a method is not supported or enabled for the account, device, domain, or environment
- **THEN** it is not falsely advertised as usable and other available methods remain usable

### Requirement: Verified and idempotent settlement
The system SHALL treat verified Stripe payment state as authoritative, authenticate webhook notifications, validate account, environment, amount, currency and payment identity, and process repeated or reordered events idempotently. A browser return alone SHALL NOT confirm payment.

#### Scenario: Successful payment notification repeats
- **WHEN** the same successful payment event is delivered more than once
- **THEN** one payment receipt and one set of automatic confirmation communications result

#### Scenario: Invalid notification or premature return
- **WHEN** the webhook signature or payment details are invalid, or the guest returns before payment is verified
- **THEN** the booking is not marked paid or confirmed and the issue is recorded for investigation

#### Scenario: Notification is delayed
- **WHEN** a valid payment notification is delayed or delivery fails
- **THEN** retry and reconciliation recover the payment without duplicate receipt or confirmation

### Requirement: Configurable and explicitly activated payment policy
Authorized administrators SHALL be able to activate Stripe and configure an indicative approved-request payment period, direct checkout hold duration, stale-processing alert threshold, and free-cancellation days, initially 48 hours, 30 minutes, one hour and 14 days. Stripe credentials SHALL remain environment-only.

#### Scenario: Existing site before activation
- **WHEN** the integration is deployed but has not been explicitly activated
- **THEN** current modes and bank-transfer workflows remain unchanged

#### Scenario: Policy changes during an existing transaction
- **WHEN** an administrator changes the indicative payment period or cancellation window
- **THEN** existing offers retain their previously communicated values and new offers use the new values

#### Scenario: Invalid configuration or unauthorized access
- **WHEN** a user without permission changes policy or a value is outside supported bounds
- **THEN** the change is denied with an actionable error

### Requirement: Approved request payment links
After approving a request, the owner SHALL verify or edit the amount and manually send a private payment link. The indicated payment period starts upon successful send and defaults to 48 hours. The link and date block remain active until the owner acts, regardless of the indicated period.

#### Scenario: Approved request is not yet sent
- **WHEN** the owner has approved a request but not sent a payment link
- **THEN** the dates remain blocked until the owner acts and no indicative payment period has started

#### Scenario: Indicative period elapses unpaid
- **WHEN** the indicated period elapses without payment
- **THEN** the booking remains blocked, the link remains payable, no automatic cancellation or expiry email occurs, and the overdue status is visible to the owner

#### Scenario: Link is resent
- **WHEN** the owner resends an approved booking's payment link
- **THEN** a fresh indicative period starts and earlier links cannot initiate new payments

#### Scenario: Older link has an active payment
- **WHEN** an old link is replaced while payment is already in progress
- **THEN** the system prevents a second charge and reconciles the existing attempt before issuing a replacement

### Requirement: Owner-controlled cancellation and late payments
The owner SHALL be able to cancel an approved unpaid booking and release its dates manually. The system SHALL revoke the payment link, retain payment and booking history, and warn before releasing dates if payment is processing or its outcome is uncertain.

#### Scenario: Owner accepts payment uncertainty
- **WHEN** the owner confirms date release despite a processing or uncertain payment
- **THEN** the booking is cancelled and dates are released while the payment remains tracked

#### Scenario: Payment arrives after cancellation
- **WHEN** payment succeeds after the owner has cancelled the booking
- **THEN** the payment is recorded, the owner is alerted to handle a manual refund, and no final confirmation or automatic refund is issued

#### Scenario: Cancelled link is opened
- **WHEN** a guest opens a revoked payment link
- **THEN** the site does not initiate a new payment and explains that the offer is no longer available

### Requirement: Failed, delayed, or exceptional payments
The system SHALL retain availability while a payment is processing, expose payment status to authorized administrators, and alert the owner by email and configured Telegram when processing exceeds the configured threshold or a payment requires intervention.

#### Scenario: Direct payment processing outlives the hold
- **WHEN** a direct payment is still processing when its hold duration is reached
- **THEN** dates remain blocked until the outcome is known and an overdue processing alert is sent

#### Scenario: Stripe outcome cannot be established
- **WHEN** Stripe is unreachable at direct-checkout expiry or during reconciliation
- **THEN** the system does not automatically release a potentially paid reservation and alerts the owner

### Requirement: Payment and refund visibility
Authorized administrators SHALL see payment status, gross amount, date, Stripe fee, net amount, and full or partial refunds when available, with unavailable values clearly identified. Refunds SHALL be initiated manually in Stripe and synchronized to the site without automatically cancelling a booking.

#### Scenario: Manual Stripe refund
- **WHEN** Stripe reports a successful full or partial refund
- **THEN** the booking's payment history and displayed refunded amount are updated idempotently

#### Scenario: Accounting information remains private
- **WHEN** a host keeper or other unauthorized user views a booking
- **THEN** payment amount, fee, net, and refund details are not exposed
