## ADDED Requirements

### Requirement: Shared branded layout for transactional emails
All transactional emails sent by the application (existing and new) SHALL use a shared branded layout including the official logo and the brand color palette (`--color-primary: #30596C`, `--color-accent: #c7b772`) as defined in `resources/css/tokens.css`, with colors/styles inlined as literal values for email-client compatibility.

#### Scenario: Existing owner notification email is restyled
- **WHEN** the owner receives the availability request notification email
- **THEN** the email displays the official logo and uses the brand colors instead of the previous ad-hoc styling

#### Scenario: New emails use the same layout
- **WHEN** any new booking-related email (pending confirmation, booking confirmed) is sent
- **THEN** it uses the same shared branded layout as the existing emails, ensuring visual consistency
