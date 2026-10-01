## 1. Persistence and domain model

- [ ] 1.1 Add migrations for reusable newsletter templates, immutable campaign snapshots, per-recipient deliveries, and manual-email suppression records.
- [ ] 1.2 Add Eloquent models, casts, relationships, status constants/enums where useful, and factories/fixtures needed by tests.
- [ ] 1.3 Add server-side block-document validation and normalization for the supported bilingual editor schema.
- [ ] 1.4 Add recipient resolution that preserves filtered subscriber visibility, excludes missing/invalid emails from eligibility, deduplicates normalized addresses, and applies suppression rules.

## 2. Newsletter email rendering

- [ ] 2.1 Implement `NewsletterMail` with one shared subject, one recipient, no campaign BCC by default, and the campaign snapshot as its source.
- [ ] 2.2 Add the newsletter Blade content partials for structured blocks, Italian/English separator, two localized booking CTAs, and discreet unsubscribe footer using the existing email layout.
- [ ] 2.3 Add signed unsubscribe URL generation, public unsubscribe endpoint, idempotent stored-person opt-out, reactivatable manual-address suppression, and confirmation/error views.
- [ ] 2.4 Add image upload handling with authenticated permission checks, MIME/size validation, generated controlled asset names, and absolute email-safe URLs.

## 3. Admin template editor and previews

- [ ] 3.1 Add authorized admin routes and controller actions for template listing, creation, editing, saving, archiving, image upload, and template preview.
- [ ] 3.2 Build the constrained Blade/TypeScript block editor for Italian and English sections, preserving the project's mobile-first admin UI conventions.
- [ ] 3.3 Add template list actions for new, edit, duplicate/reuse, archive, and protected preview.
- [ ] 3.4 Add campaign snapshot preview using the same `NewsletterMail` content view as delivery.
- [ ] 3.5 Add a clearly labeled test-send action to the configured apartment/admin address without creating campaign delivery rows.

## 4. Recipient selection and campaign creation

- [ ] 4.1 Extend the newsletter admin page with a recipient-selection workflow that shows all matching subscribers, including disabled rows for people without email addresses.
- [ ] 4.2 Add individual selection, all-eligible filtered selection across pagination, clear selected-recipient counts, and manual email entry with per-entry validation errors.
- [ ] 4.3 Add send confirmation that displays the shared subject, recipient count, manual addresses, excluded subscribers, and the individual-send behavior before creating a campaign.
- [ ] 4.4 Create the immutable campaign snapshot and pending deliveries transactionally before queue dispatch; prevent empty campaigns and duplicate normalized deliveries.

## 5. Queued delivery and history

- [ ] 5.1 Implement one queued delivery job per recipient with retries, successful-send timestamping, failure capture, and no duplicate send after a successful delivery.
- [ ] 5.2 Add campaign and delivery history routes, controllers, filters, status summaries, and per-recipient detail views protected by `manage_newsletter`.
- [ ] 5.3 Add controlled retry for failed deliveries without recreating successful delivery rows or bypassing unsubscribe suppression.
- [ ] 5.4 Add an admin-only suppression management action that can reactivate manually suppressed addresses and stored newsletter subscribers.
- [ ] 5.5 Preserve the existing subscriber table, filters, phone column, and subscription toggle behavior while integrating the new sections.

## 6. Tests and documentation

- [ ] 6.1 Add model/service tests for block validation, image validation, recipient resolution, missing-email ineligibility, pagination-wide selection, normalization, deduplication, and suppression.
- [ ] 6.2 Add feature tests for permission protection, template CRUD/archive, previews, campaign creation, individual recipients, queue dispatch, failure history, and failed-delivery retry.
- [ ] 6.3 Add mail rendering tests asserting shared layout usage, bilingual order/separator, one shared subject, both localized booking CTAs, unsubscribe link, and absence of other recipients from headers.
- [ ] 6.4 Add unsubscribe tests for stored people, manual addresses, invalid signatures, idempotency, future-send exclusion, and admin reactivation.
- [ ] 6.5 Add test-send tests proving one test recipient, shared renderer usage, no campaign creation, and failure reporting.
- [ ] 6.6 Update `docs/specific-data-model.md`, `docs/specific-tech-backend-doc.mdc`, `docs/specific-tech-frontend-doc.mdc`, and `docs/business-doc.mdc` with the implemented newsletter behavior and operational constraints.
- [ ] 6.7 Run focused newsletter tests, full backend tests, frontend typecheck/build, and inspect the generated email preview at desktop and narrow widths.