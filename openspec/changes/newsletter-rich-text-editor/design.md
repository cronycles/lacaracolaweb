## Context

The newsletter template editor (`resources/ts/components/newsletter-editor.ts` + `app/Services/Newsletter/NewsletterBlockDocument.php`) was shipped very recently (migrations dated 2026-10-01) as a constrained block-list form: each paragraph/heading/list/image/button is a separate form row with its own inputs, stored as a JSON array per locale. No campaign has been sent with it yet, so there is no production content or historical "sent" snapshot to preserve compatibility with.

The project has no JS framework (vanilla TypeScript bundled by Vite) and no existing HTML sanitization dependency. The shared email layout (`resources/views/emails/layout.blade.php`) already defines reusable CSS classes in a `<style>` block (e.g. `.btn`), proving that class-based styling (not only inline styles) already works across the email clients this project targets.

## Goals / Non-Goals

**Goals:**
- Replace the block-list form with one continuous, selectable rich-text area per locale (IT/EN), edited with a toolbar (bold/italic/underline, heading levels, bulleted/numbered list, link, horizontal rule).
- Insert images through the existing upload endpoint directly into the text, with left/center/right alignment and 25/50/75/100% preset width resizing.
- Keep a distinct "insert CTA button" toolbar command producing the existing styled `.btn` link, separate from plain text links.
- Store content as a single sanitized HTML string per locale, validated against a strict allow-list on every save (defense in depth against stored XSS, since this HTML is broadcast by email to all subscribers).
- Keep the existing shared email layout, language separator, booking CTA, and unsubscribe footer unchanged.

**Non-Goals:**
- Drag-handle image resizing (preset percentage buttons only).
- Arbitrary font family/size/color selection, tables, code blocks, blockquotes, or nested embeds beyond image/CTA-button/horizontal-rule.
- Migrating existing template rows from the block format (none exist with real content; feature is unreleased).
- Backward-compatible rendering of old block-format campaign snapshots (none have been sent).
- Full inline-style CSS conversion pipeline (project already relies on `<style>` classes for email, e.g. `.btn`).

## Decisions

1. **Editor library: Quill.js (Snow theme)**, loaded as a plain npm dependency and initialized from `resources/ts/components/newsletter-editor.ts`, same as today's vanilla-TS component pattern. Alternatives considered: TipTap (ProseMirror) — more powerful but heavier/more complex API for a "keep it simple" requirement; TinyMCE/CKEditor — large bundle size and license/cloud-key friction for a self-hosted admin tool. Quill is small, dependency-free, and its toolbar/formats model maps directly onto the marks already supported today (bold/italic/underline/headings/lists).

2. **Storage format: sanitized HTML string**, one per locale, replacing the JSON block array. `content_it`/`content_en` columns change from `json` to `text` on both `newsletter_templates` and `newsletter_campaigns` (new migration; safe because no real data exists yet). Model casts drop `'array'` and become plain strings.

3. **Alignment via CSS classes; resizing via the native `width` HTML attribute.** Quill's built-in `align` format (registered as `AlignClass` by default) emits `ql-align-left|center|right` classes on the block-level container (the paragraph/heading wrapping the image), added once to `resources/views/emails/layout.blade.php`'s `<style>` block, matching the existing `.btn` pattern. Image resizing uses Quill's built-in support for the `width` attribute directly on `<img>` (not CSS) via four preset toolbar buttons (25/50/75/100%) — this is a deliberate email-compatibility choice: Outlook's rendering engine ignores CSS `width` on images but honors the legacy HTML `width` attribute, which is why this pattern is standard in email template tooling. The sanitizer allow-lists the exact `class` values on block elements/links and the exact `width` values on images, instead of parsing free-form `style` attribute content, which is both simpler and safer.

4. **CTA button as a custom Quill toolbar command**, not a plain link. It prompts for label + URL and inserts `<a class="btn" href="...">label</a>`. The sanitizer allows `class="btn"` only on `<a>` tags; plain links have no class.

5. **Server-side sanitization: `symfony/html-sanitizer`** (Composer; actively maintained successor to the abandoned `tgalopin/html-sanitizer`), configured with an explicit allow-list:
   - Tags: `p, h1, h2, h3, strong, em, u, ul, ol, li, a, img, hr, br`.
   - `a`: attributes `href` (scheme `http`/`https`/`mailto` only), `class` (exact value `btn` or absent).
   - `img`: attributes `src` (must match the app's own `/storage/newsletters/...` asset prefix — the same constraint `NewsletterBlockDocument` enforced via `starts_with:newsletters/`), `alt`, `width` (one of `25%|50%|75%|100%` or absent).
   - `p`/`h1`/`h2`/`h3`: attribute `class` (one of the three `ql-align-*` values or absent).
   - No `style` attribute allowed anywhere; no `script`, `iframe`, `svg`, `on*` handlers, `data:`/`javascript:` URLs.
   - This runs server-side on every save (create/update), independent of and in addition to Quill's own clipboard sanitization on paste — the client is never trusted.

6. **Image upload flow is unchanged** (same controller endpoint, same validation: allowed mime types, 10 MB limit, stored under `newsletters/`). Only the insertion point changes: the toolbar's image button uploads then inserts an `<img>` at the cursor instead of adding a new block row.

7. **Horizontal rule ("separator")** is kept as a small custom toolbar button (Quill has no built-in HR), inserting a sanitizer-allowed `<hr>`.

## Risks / Trade-offs

- **Pasting rich content from Word/Google Docs/webpages** can carry disallowed tags/attributes → Quill's clipboard module is configured to strip unknown formats on paste, and the server-side sanitizer is the authoritative gate regardless of what the client sends.
- **`quill@2.0.3` has a known low-severity client-side XSS advisory in its HTML export feature** (GHSA-v3m3-f69x-jf25) → not exploitable server-side; mitigated the same way as clipboard paste: the server-side sanitizer re-validates on every save regardless of what the editor produced in the browser, so no unsanitized HTML is ever persisted or emailed.
- **Allow-lists must stay in sync**: the alignment classes between Quill's defaults, the sanitizer config, and the email `<style>` block; the width presets between the toolbar buttons and the sanitizer's allowed `width` values → all live in the same change and are covered by a feature test asserting a round-trip save+render for each alignment/width combination.
- **Removing JSON block storage is breaking** for any code reading `content_it`/`content_en` as arrays → scoped to this app only (no external API consumers); grep confirms the only readers are the controller, the mailable, and the two Blade partials, all updated in this change.
- **No automatic content migration** → acceptable since no template currently has real content; the Why/Impact sections already flag this as a conscious, confirmed trade-off.

## Migration Plan

1. New migration: alter `newsletter_templates` and `newsletter_campaigns` `content_it`/`content_en` columns from `json` to `text`; update model casts.
2. Remove `NewsletterBlockDocument`; add `NewsletterHtmlSanitizer` (or similar) service used by `NewsletterController::saveTemplate()`.
3. Rewrite `resources/views/emails/partials/newsletter-blocks.blade.php` to output the sanitized HTML directly (still inside the existing layout/separator/CTA/unsubscribe wrapper), or inline it since it no longer needs a block loop.
4. Add the `.ql-align-*`, `.w-*` CSS rules next to the existing `.btn` rule in `resources/views/emails/layout.blade.php`.
5. Replace `resources/ts/components/newsletter-editor.ts` with a Quill-based implementation; update `resources/views/admin/newsletter-form.blade.php` accordingly.
6. Update/replace `tests/Unit/NewsletterBlockDocumentTest.php` and the relevant assertions in `tests/Feature/NewsletterCampaignTest.php` to exercise the new HTML sanitization path.
7. Rollback: migration `down()` reverts columns to `json`; since no real content exists yet, no data-loss concern.

## Open Questions

- None outstanding — confirmed with the user: Quill.js, preset-size image resizing, no legacy content to migrate, dedicated CTA button command, and a new sanitizer dependency are all approved.
