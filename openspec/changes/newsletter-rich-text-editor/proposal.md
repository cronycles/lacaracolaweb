## Why

The current newsletter template editor is a block-list form (dropdown-per-block with separate textareas and checkboxes). It does not let an administrator write continuous text, select a range of text to toggle formatting, or place and resize an image inline the way a word processor does. This makes composing newsletters slow and error-prone, and the admin has repeatedly asked for a simpler, more natural writing experience.

## What Changes

- Replace the block-list editor in the admin newsletter template form with a WYSIWYG rich-text editor (Quill.js) offering one continuous writing area per locale (Italian/English), with a toolbar for bold/italic/underline, heading levels, bulleted lists, and links.
- Add toolbar commands to insert an image (uploaded through the existing upload endpoint) and to align it left/center/right and resize it using preset sizes (25/50/75/100% of content width).
- Add a dedicated toolbar command to insert a styled call-to-action "button" (link with the existing CTA visual style), distinct from a plain text link.
- **BREAKING**: Change the stored template content format from a JSON array of typed blocks to a single sanitized HTML string per locale (`content_it`/`content_en`). Existing block-based data model, validation, and migration are replaced; there is no automatic migration of pre-existing templates (confirmed negligible/no production data to preserve).
- Add server-side HTML sanitization (allow-list of tags/attributes) before persisting template content, replacing `NewsletterBlockDocument` validation.
- Update the email rendering partial to render the sanitized HTML directly (still wrapped in the existing shared branded layout, language separator, booking CTA, and unsubscribe footer) instead of switching over block types.
- Add a new PHP HTML sanitization dependency (`symfony/html-sanitizer`).
- Add a new JS dependency (`quill`) bundled through the existing Vite pipeline.
- Add the ability to permanently delete a newsletter template (currently templates can only be archived, never removed).

## Capabilities

### New Capabilities
(none)

### Modified Capabilities
- `newsletter-templates`: Editor changes from a constrained block-based form to a WYSIWYG rich-text editor; stored content format changes from JSON block arrays to sanitized HTML; image blocks are replaced by inline images with alignment/resize controls; the "button" block becomes a toolbar-inserted CTA element within the rich text; validation changes from block-schema validation to HTML sanitization; administrators can now permanently delete a template (archived or not) in addition to archiving it.

## Impact

- Affected code: `app/Services/Newsletter/NewsletterBlockDocument.php` (replaced by an HTML sanitizer service), `app/Http/Controllers/Admin/NewsletterController.php` (save/validate logic, new destroy action), `app/Models/NewsletterTemplate.php` and `NewsletterCampaign.php` (content casts), `resources/ts/components/newsletter-editor.ts` (rewritten around Quill), `resources/views/admin/newsletter-form.blade.php`, `resources/views/admin/newsletter.blade.php` (delete action), `resources/views/emails/partials/newsletter-blocks.blade.php` (renamed/rewritten to render sanitized HTML), `routes/admin.php` (new delete route), `database/migrations` (new migration changing `content_it`/`content_en` columns from `json` to `text`/`longText`), existing tests in `tests/Unit/NewsletterBlockDocumentTest.php` and `tests/Feature/NewsletterCampaignTest.php`.
- New dependencies: `league/html-sanitizer` (composer), `quill` (npm).
- No changes to recipient selection, campaign snapshotting, delivery queueing, preview/test-send routes, or unsubscribe handling.
