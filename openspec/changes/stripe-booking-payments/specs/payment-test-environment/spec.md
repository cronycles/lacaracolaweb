## Purpose

Provide a safe online environment for realistic booking and Stripe payment tests without affecting production.

## ADDED Requirements

### Requirement: Isolated online staging
The system SHALL support a password-protected HTTPS staging site at `stage.lacaracolaandora.com` with a separate database, Stripe test credentials, and test webhook. It SHALL use fictitious customer and booking data and production-like pricing and settings.

#### Scenario: Staging booking and payment
- **WHEN** an administrator completes a test booking and payment in staging
- **THEN** only staging records and Stripe test data change and no real money moves

#### Scenario: Stripe webhook reaches protected staging
- **WHEN** Stripe sends a correctly signed test webhook to staging
- **THEN** the endpoint remains reachable without browser password authentication and validates the Stripe signature

### Requirement: No production side effects
Staging SHALL capture all outbound email in a protected online test inbox and prevent production calendar, messaging, reporting, and other external integrations from being contacted.

#### Scenario: Guest or owner email is generated in staging
- **WHEN** a staging booking triggers email
- **THEN** the message is captured for inspection and no real recipient is contacted

#### Scenario: Production credentials or services are supplied
- **WHEN** a staging configuration would connect to live Stripe, the production database, or a production external integration
- **THEN** activation or startup is blocked with an actionable diagnostic

### Requirement: Test payment methods and notifications
Staging SHALL support end-to-end testing of successful and failed payment, authentication, webhook retries, expiry, refund, and supported payment methods, including PayPal test prerequisites.

#### Scenario: Simulated failed payment
- **WHEN** a tester uses a Stripe test payment that fails
- **THEN** no real charge occurs, the booking is not confirmed, and the failure is visible for inspection
