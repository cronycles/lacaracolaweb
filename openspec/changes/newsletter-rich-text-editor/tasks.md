## 1. Dependencies & Data Model

- [x] 1.1 Add `symfony/html-sanitizer` to `composer.json` and `quill` to `package.json`; install both.
- [x] 1.2 Add migration altering `newsletter_templates.content_it`/`content_en` and `newsletter_campaigns.content_it`/`content_en` from `json` to `text`.
- [x] 1.3 Update `NewsletterTemplate` and `NewsletterCampaign` model casts (remove `'array'`, keep as plain string attributes).

## 2. Server-Side Sanitization

- [x] 2.1 Replace `app/Services/Newsletter/NewsletterBlockDocument.php` with a new sanitizer service (`NewsletterContentSanitizer`) configured with the allow-list from design.md (tags, per-tag attributes, allowed `class` values for `a`/`p`/`h1`/`h2`/`h3`, allowed `width` values for `img`, allowed URL schemes, required `img` src prefix).
- [x] 2.2 Update `NewsletterController::saveTemplate()` to sanitize `content_it`/`content_en` as HTML strings instead of validating a JSON block array.
- [x] 2.3 Update `NewsletterController::campaignFromTemplate()` and the send/test-send/preview flows to pass through sanitized HTML strings (no block-array handling).
- [x] 2.4 Add a `destroy` action to `NewsletterController` and a corresponding `DELETE /admin/newsletter/templates/{newsletterTemplate}` route, deleting the template row (campaigns keep their immutable snapshot via the existing `nullOnDelete` foreign key).
- [x] 2.5 Add a "Delete" button with a confirmation prompt to `resources/views/admin/newsletter.blade.php` for every template row (archived or active).

## 3. Email Rendering

- [x] 3.1 Add `.ql-align-left/center/right` CSS rules to `resources/views/emails/layout.blade.php`, next to the existing `.btn` rule.
- [x] 3.2 Rewrite `resources/views/emails/partials/newsletter-blocks.blade.php` (or inline into `resources/views/emails/newsletter.blade.php`) to output the sanitized HTML string directly per locale, keeping the language separator, booking CTA, and unsubscribe footer unchanged.

## 4. Admin Rich-Text Editor (Frontend)

- [x] 4.1 Replace `resources/ts/components/newsletter-editor.ts` with a Quill-based implementation: one Quill instance per locale (IT/EN), toolbar with bold/italic/underline, heading levels, bulleted/numbered list, align, link.
- [x] 4.2 Add a custom toolbar image button that uploads via the existing `admin.newsletter.images.store` endpoint and inserts the image at the cursor; preserve existing client-side validation (allowed mime types, 10 MB limit).
- [x] 4.3 Add four toolbar buttons that set the native `width` attribute (25%/50%/75%/100%) on the currently selected image, using Quill's built-in support for `width`/`height`/`alt` image attributes.
- [x] 4.4 Add a custom toolbar command to insert a CTA button (`<a class="btn" href="...">label</a>`) via a simple label/URL prompt.
- [x] 4.5 Add a custom toolbar command to insert a horizontal rule (`<hr>`).
- [x] 4.6 Configure Quill's clipboard module to strip disallowed formats on paste.
- [x] 4.7 Update `resources/views/admin/newsletter-form.blade.php` to wire the hidden inputs to the Quill editors' HTML output instead of the JSON block textarea wiring.

## 5. Tests

- [x] 5.1 Replace `tests/Unit/NewsletterBlockDocumentTest.php` with unit tests for the new sanitizer service covering: allowed tags/classes pass through, disallowed tags/attributes/styles are stripped, disallowed `img` src (outside `/storage/newsletters/`) is rejected, disallowed URL schemes on `a`/`img` are rejected.
- [x] 5.2 Update `tests/Feature/NewsletterCampaignTest.php` to save/preview/send templates using sanitized HTML strings instead of block arrays, and assert the alignment/width/CTA-button classes survive save-and-render round trips.
- [x] 5.3 Add a feature test covering template deletion (archived and active), asserting the row is removed and any existing campaign snapshot is unaffected.

## 6. Verification

- [x] 6.1 Run `npm run build` and `php artisan test` to confirm the full editor, sanitizer, and rendering pipeline works end to end.
- [x] 6.2 Manually verify in a local preview: typing/selecting text and toggling bold/italic/underline, inserting and aligning/resizing an image, inserting a CTA button and a separator, and that the saved template re-opens with the same formatting.
