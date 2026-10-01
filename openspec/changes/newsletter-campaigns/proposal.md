## Why

The admin newsletter area currently only lists subscribed people and cannot create, preview, send, or audit a newsletter. The site already has a shared email layout and established previewable Laravel mailables, so the owner needs a small, controlled campaign workflow that reuses that email experience without introducing arbitrary HTML or a general-purpose marketing platform.

## What Changes

- Add reusable newsletter templates with one subject and one combined Italian/English message.
- Add a constrained visual block editor for headings, paragraphs, basic text emphasis, lists, separators, links/buttons, and uploaded images, without arbitrary font selection or unrestricted HTML.
- Render every newsletter inside the existing shared email layout and append two standard bilingual calls-to-action: Italian to the Italian homepage and English to the English homepage, plus a discreet legal unsubscribe link.
- Extend the newsletter admin page with a recipient selection view showing every newsletter subscriber, including subscribers without email addresses; people without email addresses must remain visible but not selectable.
- Allow selecting one, multiple, or all eligible subscribers and adding arbitrary valid manual email addresses.
- Send one email per recipient, never exposing other recipients through `To` or `Cc`.
- Add protected previews that render the same newsletter Mailable used for delivery.
- Add a test-send action to the configured administrator/apartment address and campaign/per-recipient delivery history with sent/failed status and immutable snapshots of the subject/content/recipient data used at send time.
- Keep the existing subscriber list, filters, phone column, and unsubscribe/toggle workflow; allow an admin to remove a manual-email suppression and re-enable that address.

## Capabilities

### New Capabilities

- `newsletter-templates`: Create, edit, save, archive, preview, and render bilingual newsletter templates through a constrained visual editor and the shared email layout.
- `newsletter-recipient-selection`: Display newsletter subscribers, identify ineligible subscribers without email addresses, select eligible subscribers individually or in bulk, and add manual email recipients.
- `newsletter-delivery`: Validate and send one email per recipient with the standard footer, website booking call-to-action, and unsubscribe behavior.
- `newsletter-history`: Record immutable campaign and per-recipient delivery history and expose protected campaign and delivery details in the admin area.

### Modified Capabilities

None.

## Impact

- New Laravel models, migrations, mailables, services/jobs, controllers, routes, Blade views, and tests for newsletter templates, campaigns, deliveries, previews, and unsubscribe handling.
- Existing `admin/newsletter` UI will gain template management, recipient selection, send confirmation, preview, and history sections while preserving the current subscriber table.
- The existing email layout and brand styles will be reused; no unrestricted rich-text editor or arbitrary email HTML will be introduced.
- Uploaded newsletter images require controlled storage, validation, and email-safe asset URLs.
- Delivery will use the existing Laravel mail configuration and queue infrastructure; campaign records will preserve failures for later inspection.
- Backend, frontend, data-model, and business documentation will be updated because this adds admin routes, persistence, email behavior, and a new UI workflow.