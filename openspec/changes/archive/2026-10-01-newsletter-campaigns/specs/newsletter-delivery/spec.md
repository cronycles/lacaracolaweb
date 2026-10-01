## ADDED Requirements

### Requirement: Campaign sends one email per recipient
The system SHALL create one delivery per unique recipient and SHALL send each newsletter email with exactly one campaign recipient in `To`; recipients SHALL NOT be exposed to one another through `To` or `Cc`.

#### Scenario: Send to multiple recipients
- **WHEN** a campaign contains multiple valid recipients and delivery processing runs
- **THEN** each recipient receives an individual email addressed only to that recipient

### Requirement: Campaign delivery is queued and auditable
The system SHALL persist the campaign and pending delivery rows before dispatching delivery jobs, mark deliveries as sent only after successful transport, and record failures without deleting the campaign.

#### Scenario: Successful delivery
- **WHEN** the mail transport accepts a queued delivery
- **THEN** the delivery is marked sent with its send timestamp and the campaign counts are updated

#### Scenario: Failed delivery
- **WHEN** the mail transport fails for a delivery
- **THEN** the delivery is marked failed with an actionable error detail and other deliveries remain independently processable

### Requirement: Unsubscribe is available on every newsletter
Every sent newsletter SHALL include a discreet signed unsubscribe link that works without authentication, is idempotent, and prevents the associated stored person or manual address from being selected for future sends until an authorized administrator reactivates it.

#### Scenario: Stored person unsubscribes
- **WHEN** a person follows a valid unsubscribe link
- **THEN** the system records the person as opted out and future subscriber selection excludes the person as eligible

#### Scenario: Manual recipient unsubscribes
- **WHEN** a manually entered recipient follows a valid unsubscribe link
- **THEN** the system records a normalized suppression for that address and future manual sends reject or exclude it

#### Scenario: Invalid unsubscribe link
- **WHEN** an unsubscribe request has an invalid or tampered signature
- **THEN** the system refuses the change and shows an error without modifying subscription state

#### Scenario: Administrator reactivates a suppressed address
- **WHEN** an authorized administrator removes a manual-email suppression or re-enables a stored person's newsletter subscription
- **THEN** that address becomes eligible for future recipient selection

### Requirement: Campaign uses one shared subject and bilingual standard footer
The campaign SHALL use one subject for both language sections and SHALL append an Italian booking call-to-action linked to the Italian homepage, an English booking call-to-action linked to the English homepage, and the unsubscribe footer without requiring the editor to recreate them.

#### Scenario: Send bilingual campaign
- **WHEN** the administrator sends a valid bilingual campaign
- **THEN** the Italian and English body sections use the same subject and are followed by separate Italian and English booking CTAs linked to their respective homepages and the unsubscribe footer

### Requirement: Administrator can send a test email
The system SHALL allow an authorized administrator to send the current template or campaign rendering as a clearly labeled test email to the configured administrator address before a real campaign send, without creating campaign delivery records or changing subscription state.

#### Scenario: Send template test
- **WHEN** the administrator requests a test for a valid template
- **THEN** exactly one test email is sent to the configured test address using the same renderer as the real campaign

#### Scenario: Test send fails
- **WHEN** the test email transport fails
- **THEN** the system reports the failure to the administrator and does not create a campaign or subscriber delivery