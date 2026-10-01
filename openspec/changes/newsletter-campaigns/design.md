## Context

The application is a Laravel monolith with server-rendered Blade views, Laravel Mailables, and a shared HTML email layout at `resources/views/emails/layout.blade.php`. Booking-related admin workflows already expose protected previews that render the same Mailable used for delivery. The newsletter page currently queries subscribed `Person` records and supports filters, but sending, reusable content, previews, and history do not exist.

The owner wants a simple campaign tool for a small vacation-rental mailing list, not a general marketing automation system. The workflow must support one bilingual email per recipient, manually entered addresses, uploaded images, legal unsubscribe links, and an auditable send history while remaining compatible with normal email clients.

## Goals / Non-Goals

**Goals:**

- Reuse the existing email shell, logo, colors, spacing, and button treatment for newsletter messages.
- Provide a constrained visual editor whose output is a validated structured document rather than arbitrary HTML.
- Save reusable bilingual templates with one shared subject and an Italian section followed by a visual separator and an English section.
- Let the admin see every newsletter subscriber, including those without an email address, while only allowing eligible addresses to be selected.
- Support individual, bulk, and all-eligible selection plus arbitrary manually entered email addresses.
- Send individually, queue work safely, expose the same rendered email as a protected preview, and retain immutable campaign/delivery history.
- Provide a discreet unsubscribe action that works for stored people and manually entered recipients.

**Non-Goals:**

- No segmentation engine, scheduled campaigns, open/click tracking, analytics dashboard, A/B testing, automation, or public marketing platform.
- No per-recipient language selection; every message contains both languages.
- No free font picker, arbitrary colors, arbitrary HTML, JavaScript, forms, or unsupported email layout primitives in the editor.
- No artificial maximum number of content images; normal upload validation and email/client size constraints still apply.
- No modification of existing booking email designs or existing booking Mailable contracts.

## Decisions

### 1. Use structured newsletter blocks instead of unrestricted HTML

Templates store two ordered block arrays, one for Italian and one for English, in JSON. Supported blocks are paragraph, heading levels 1-3, bulleted list, separator, image, and link/button. Text marks are limited to bold, italic, underline, and an approved relative size/heading choice. The server validates the block schema and renders it through a dedicated Blade partial used by both preview and sending.

This is preferred over storing editor-generated HTML because email-safe output remains under application control and later template changes do not silently introduce CSS or markup that email clients cannot render. A lightweight TypeScript block editor fits the existing Blade + Vite frontend and avoids adding a large editor dependency for this narrow vocabulary.

### 2. Keep the bilingual message vertical and mobile-safe

The Mailable renders the Italian blocks, a styled horizontal divider with a small language label, then the English blocks. The subject is one shared value, not two localized subjects. The standard booking CTA and legal footer are appended after both language sections.

The standard CTA contains two clearly separated actions: an Italian action linking to the named `it.home` route and an English action linking to the named `en.home` route. The email client or the website is not expected to infer a recipient's language. The unsubscribe link is rendered as a small footer action, never as part of the custom core.

### 3. Separate reusable templates from send-time campaigns

Use three persistence concepts:

- `NewsletterTemplate`: editable reusable source with title, shared subject, bilingual block JSON, and archive metadata.
- `NewsletterCampaign`: immutable snapshot of the selected template subject/content plus sender, status, counts, and timestamps.
- `NewsletterDelivery`: one row per resolved recipient with recipient email, optional `person_id`, status, timestamps, and failure detail.

The campaign snapshot is created before dispatching jobs. Editing or archiving a template later cannot change what the history says was sent. Delivery rows are unique per campaign and normalized email address, so selecting a subscriber and typing the same address manually cannot produce duplicate sends.

### 4. Resolve recipients explicitly and show ineligible subscribers

The recipient UI loads the same newsletter-subscriber population as the existing list, preserving filters and pagination. Every person remains visible. A row without a usable email displays a clear unavailable state and a disabled checkbox. Eligible people can be selected individually or through an all-eligible action that applies to the filtered result set, not only the current page.

Manual addresses are entered as a repeatable list and validated server-side with email validation, normalization, deduplication, and a useful error per invalid entry. Manual addresses do not need a `Person` record, but their delivery history stores the address exactly as normalized for audit purposes.

### 5. Send one queued email per delivery

Campaign creation resolves and freezes recipients, creates pending delivery rows, then dispatches one queue job per row. Each job sends a `NewsletterMail` to exactly one `To` address. It never puts the campaign recipient set in `To` or `Cc`, and campaign deliveries do not BCC the apartment address by default. The delivery history and separate test-send action provide the operational record without generating a second copy for every recipient.

The job marks the delivery sent only after the mail transport succeeds. Failures are caught, recorded on the delivery, and reflected in campaign counts without erasing the campaign or retry history. Existing Laravel queue configuration is reused; no new provider is introduced.

The admin can send a test rendering to the configured apartment/admin address before creating the real campaign. A test send is explicitly labeled and does not create campaign deliveries or alter subscriber state.

### 6. Use signed unsubscribe links with reactivatable suppression

Each delivery gets a signed, expiring-safe unsubscribe URL whose token identifies the recipient without exposing mutable campaign content. For a linked `Person`, unsubscribe sets the existing newsletter opt-out state and prevents future subscriber selection. For a manual address, a dedicated suppression record stores the normalized email and prevents future sends until an authorized admin explicitly removes the suppression from the newsletter management UI. Reactivation is an admin-only action and never happens automatically.

The unsubscribe endpoint is public, does not require login, is idempotent, and renders a small confirmation page. The email footer remains visually discreet but the link must be understandable and usable.

### 7. Preview the actual Mailable

Template preview renders unsent content with a controlled preview recipient and no delivery side effect. Campaign preview renders the frozen campaign snapshot. Both use the same `NewsletterMail` content view and shared layout used by the queue job. Protected admin preview routes require `manage_newsletter` and open in a new tab, matching existing booking email preview behavior.

### 8. Store uploaded images as controlled email assets

Images are uploaded through an authenticated newsletter endpoint, validated as supported image MIME types and bounded by a per-file size limit, then stored on the configured public asset disk with generated names. The editor stores only the asset identifier/path and alt text in its image block. There is no count limit, but implementation must preserve email-safe absolute URLs and surface upload failures before a campaign can be sent.

## Risks / Trade-offs

- [Email client rendering differences] -> Render simple table-compatible/inline-safe markup and test the generated HTML through the existing Blade preview; avoid CSS that depends on modern browser layout.
- [Large or too many uploaded images] -> Do not cap image count, but validate each file, use generated storage names, and warn or reject when the assembled email exceeds a practical transport/client size threshold.
- [Queue unavailable or mail transport failure] -> Persist campaigns and delivery rows before dispatch, record failures, expose status in history, and allow a controlled retry of failed deliveries without duplicating successful ones.
- [Manual recipients may not be identifiable as people] -> Store normalized address snapshots and apply the same signed unsubscribe/suppression mechanism independently of `Person`.
- [All-eligible selection across pagination] -> Resolve the selection server-side from the submitted filters/query and never trust a client-provided list as the sole source of recipients.
- [Rich editor introduces unsafe markup] -> Accept only the documented block schema and marks; reject unknown fields/types server-side and escape text during Blade rendering.

## Migration Plan

1. Deploy migrations for templates, campaigns, deliveries, image assets if needed, and manual-email suppression records.
2. Deploy the admin editor, preview, recipient selection, campaign creation, queue job, unsubscribe route, and history views behind the existing `manage_newsletter` permission.
3. Verify the existing subscriber list and toggle behavior remain unchanged.
4. Create and preview a test template, send to controlled addresses, verify individual recipients, unsubscribe, queue failure handling, and history before production use.
5. Rollback removes the new routes/UI and stops new campaign creation; retain send-history tables during rollback unless a separate data-removal decision is made. Existing newsletter subscriber data is untouched.

## Open Questions

- None blocking. The test-send action will use `config('apartment.email')` as the controlled test address; the campaign itself will not BCC the apartment address by default.