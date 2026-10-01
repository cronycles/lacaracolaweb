# newsletter-templates Specification

## Purpose
TBD - created by archiving change newsletter-campaigns. Update Purpose after archive.
## Requirements
### Requirement: Admin can manage bilingual newsletter templates
The system SHALL allow an authorized newsletter administrator to create, edit, save, archive, and reuse a newsletter template containing one shared subject and two ordered content sections: Italian and English.

#### Scenario: Create a bilingual template
- **WHEN** an authorized administrator submits a valid template with a subject and Italian/English content
- **THEN** the system stores the template as an editable reusable draft

#### Scenario: Archive a template
- **WHEN** an authorized administrator archives a template
- **THEN** the template is excluded from new-template selection but remains available in history and existing campaigns

### Requirement: Editor permits only supported email-safe blocks
The editor SHALL support paragraphs, heading levels, basic bold/italic/underline marks, relative text sizing through headings, bulleted lists, separators, images, and links/buttons, and SHALL reject unsupported block types, arbitrary font selection, and arbitrary HTML.

#### Scenario: Save supported content
- **WHEN** an administrator saves content composed only of supported blocks and marks
- **THEN** the system validates and stores the structured block document

#### Scenario: Reject unknown markup
- **WHEN** a request contains an unknown block type, unsupported mark, or arbitrary HTML field
- **THEN** the system rejects the content and reports a validation error without storing it

### Requirement: Newsletter rendering uses the shared email experience
The newsletter renderer SHALL use the existing shared email layout and SHALL render the Italian section followed vertically by a visible language separator and the English section, then append the standard booking call-to-action and discreet unsubscribe footer.

#### Scenario: Render a bilingual email
- **WHEN** a valid newsletter template is previewed or sent
- **THEN** the output uses the shared branded layout, shows Italian and English in order, includes a website booking button, and includes an unsubscribe link

### Requirement: Newsletter images are uploadable email assets
The system SHALL allow authorized administrators to upload supported image files for newsletter image blocks, store them under generated controlled asset names, and reject invalid or oversized files.

#### Scenario: Upload an image
- **WHEN** an administrator uploads a valid supported image
- **THEN** the system stores the image and returns an asset reference usable by the template editor

#### Scenario: Upload invalid media
- **WHEN** an administrator uploads a non-image or oversized file
- **THEN** the system rejects the upload and does not create an asset reference

