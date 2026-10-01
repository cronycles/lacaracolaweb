# newsletter-templates Specification

## Purpose
Admin-facing bilingual newsletter template authoring, storage, and email rendering.
## Requirements
### Requirement: Admin can manage bilingual newsletter templates
The system SHALL allow an authorized newsletter administrator to create, edit, save, archive, delete, and reuse a newsletter template containing one shared subject and two ordered content sections: Italian and English.

#### Scenario: Create a bilingual template
- **WHEN** an authorized administrator submits a valid template with a subject and Italian/English content
- **THEN** the system stores the template as an editable reusable draft

#### Scenario: Archive a template
- **WHEN** an authorized administrator archives a template
- **THEN** the template is excluded from new-template selection but remains available in history and existing campaigns

#### Scenario: Delete a template
- **WHEN** an authorized administrator deletes a template, archived or not
- **THEN** the system permanently removes the template while leaving any existing campaign snapshots that reference it untouched

### Requirement: Editor permits only supported email-safe rich text
The editor SHALL provide a continuous rich-text writing area per locale (Italian and English) in which an administrator can select a range of text and apply bold, italic, underline, heading levels, bulleted or numbered lists, a horizontal separator, plain text links, and a distinct call-to-action button. The system SHALL reject unsupported HTML tags, attributes, inline styles, and arbitrary classes when saving.

#### Scenario: Save supported rich-text content
- **WHEN** an administrator saves content composed only of supported tags, allowed classes, and allowed link/image attributes
- **THEN** the system sanitizes and stores the content as HTML without altering the supported structure

#### Scenario: Reject disallowed markup
- **WHEN** a save request contains a disallowed tag, attribute, inline `style`, or an unrecognized class value
- **THEN** the system strips or rejects the offending markup and does not store it unchanged

### Requirement: Newsletter rendering uses the shared email experience
The newsletter renderer SHALL use the existing shared email layout and SHALL render the sanitized Italian content followed vertically by a visible language separator and the sanitized English content, then append the standard booking call-to-action and discreet unsubscribe footer.

#### Scenario: Render a bilingual email
- **WHEN** a valid newsletter template is previewed or sent
- **THEN** the output uses the shared branded layout, shows the sanitized Italian and English rich-text content in order, includes a website booking button, and includes an unsubscribe link

### Requirement: Newsletter images are uploadable email assets
The system SHALL allow authorized administrators to upload supported image files, insert them inline in the rich-text content at the cursor position, align them left, center, or right, and resize them to one of four preset widths (25%, 50%, 75%, 100%), storing uploads under generated controlled asset names and rejecting invalid or oversized files.

#### Scenario: Upload and place an image
- **WHEN** an administrator uploads a valid supported image while editing a template
- **THEN** the system stores the image and inserts it into the rich-text content at the cursor position

#### Scenario: Align and resize an inline image
- **WHEN** an administrator selects an inline image and chooses an alignment and a preset width
- **THEN** the system applies the corresponding allowed alignment and width class to the image and persists it on save

#### Scenario: Upload invalid media
- **WHEN** an administrator uploads a non-image or oversized file
- **THEN** the system rejects the upload and does not insert any image

