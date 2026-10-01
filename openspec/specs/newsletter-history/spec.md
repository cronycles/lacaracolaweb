# newsletter-history Specification

## Purpose
Preview and historical visibility into sent newsletter campaigns and templates.
## Requirements
### Requirement: Administrator can preview the exact newsletter output
An authorized newsletter administrator SHALL be able to preview both an editable template and a frozen campaign snapshot, and both previews SHALL render the same Mailable content view and shared layout used for delivery.

#### Scenario: Preview template
- **WHEN** the administrator opens a template preview
- **THEN** the system renders the current template without sending any email

#### Scenario: Preview sent campaign
- **WHEN** the administrator opens a campaign preview
- **THEN** the system renders the immutable campaign snapshot without sending any email

### Requirement: System records immutable campaign history
The system SHALL record each campaign with its subject, bilingual content snapshot, sender, status, recipient counts, and lifecycle timestamps, independently of later template edits.

#### Scenario: Template changes after send
- **WHEN** an administrator edits a template after a campaign has been created or sent
- **THEN** the campaign history continues to display the original snapshot used by that campaign

### Requirement: System records per-recipient delivery history
The system SHALL record each campaign recipient with normalized email, optional linked person, delivery status, timestamps, and failure detail when applicable.

#### Scenario: Inspect delivery history
- **WHEN** an administrator opens a campaign history detail page
- **THEN** the page shows every recipient and whether that individual delivery is pending, sent, or failed

### Requirement: Newsletter history is permission-protected
Template management, campaign previews, recipient selection, sending, and delivery history SHALL require the existing newsletter management permission.

#### Scenario: Unauthorized admin access
- **WHEN** an authenticated user without newsletter management permission requests a newsletter management action
- **THEN** the system denies access and does not reveal campaign or recipient data

