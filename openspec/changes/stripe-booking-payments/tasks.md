# Tasks

## 1. Foundations and configurable policy

- [ ] 1.1 Add the official Stripe PHP SDK and environment-only configuration for keys, webhook secret and mode; verify dependency installation, configuration caching, and absence of secrets in repository files or rendered output.
- [ ] 1.2 Add backward-compatible migrations for offer/attempt history, event inbox, notification outbox, refunds/conflicts, policy snapshots and a property availability lock; verify migrations and rollback on a clean database and unchanged existing booking records.
- [ ] 1.3 Add default-off activation, authorized settings and validated limits for an indicative 48-hour approved payment period, 30-minute direct hold, one-hour processing alert and 14-day cancellation window; verify access control, snapshots and that the indicative period never cancels a booking.
- [ ] 1.4 Update `docs/specific-data-model.md`, `docs/specific-tech-backend-doc.mdc`, and contradictory settings guidance to describe the actual records, secrets boundary and configurable policy; verify the documentation matches migrations and permission checks.

## 2. Availability and payment lifecycle

- [ ] 2.1 Implement atomic shared availability locking and half-open interval checks for direct checkout, public requests and local booking creation/changes; verify overlapping concurrent requests with MySQL and existing availability/calendar regression tests.
- [ ] 2.2 Implement offer, attempt and hold transitions with server-side price/consent snapshots, own-hold exclusion and idempotent guest matching/conversion; verify state transitions, cancellation, duplicate conversion, unavailable prices and concurrent submissions.
- [ ] 2.3 Integrate pending and expired payment holds with public/admin calendars, iCal export and Google push; verify each calendar's blocking and release behavior without changing unrelated bookings.
- [ ] 2.4 Define an application-owned payment gateway contract and normalized event/status types, then implement the Stripe adapter and embedded checkout; verify both flows with a fake adapter and contract tests, secure links, correct amounts, retries and Stripe session recreation.
- [ ] 2.5 Document availability locking, payment states, calendar exports and checkout endpoints in the backend/data-model docs; verify every state has a documented release or intervention path.

## 3. Verified payments, reconciliation and accounting

- [ ] 3.1 Implement the signed raw-body webhook, durable event inbox and account/mode/amount/currency/attempt validation; verify invalid signatures, mismatched data, duplicate and reordered events cannot confirm or duplicate a payment.
- [ ] 3.2 Implement payment finalization and scheduled reconciliation of missed notifications, processing, direct hold expiry and provider unavailability; verify browser return alone cannot confirm, late success is quarantined, uncertain payments remain blocked except owner-confirmed manual cancellation, and retries recover safely.
- [ ] 3.3 Integrate verified gross receipts, payment dates, Stripe fees, net proceeds, partial/full refunds and disputes with admin financial views; verify idempotence, authorization, pending data, no duplicated income, unchanged parking/service flags and transparent refund adjustments.
- [ ] 3.4 Implement direct hold expiry and owner-controlled cancellation/resend with safe session invalidation; verify the approved link remains usable after the indicative period, session recreation, price changes, concurrent payment and no double charge.
- [ ] 3.5 Implement explicit warning and owner-confirmed release of approved bookings with processing or uncertain payments, preserving audit and financial history even if requests are deleted; verify cancelled links reject new payments and late payments alert the owner for manual refund without confirmation.
- [ ] 3.6 Add actionable admin alerts and deduplicated email/Telegram notifications for stale processing, payment anomalies and reconciliation errors; verify configured thresholds, disabled Telegram and retry/failure visibility.
- [ ] 3.7 Update backend, accounting, data-model and operations documentation with webhook configuration, event recovery, accounting semantics and refund handling; verify documented recovery paths against implemented behavior.

## 4. Approved request and communications

- [ ] 4.1 Add the authorized review/edit/send flow for newly approved Stripe requests, retaining blocked bookings until manual cancellation and preserving legacy bank-transfer bookings; verify edited totals, locale, overdue status and pre-activation/manual/imported regression cases.
- [ ] 4.2 Implement secure offer access and resend with a new indicative period and revocation of previous links; verify no automatic approved-request expiry, and that active payment or ambiguous email delivery cannot produce two payable offers.
- [ ] 4.3 Implement retryable guest confirmation, owner notice and host-keeper operational messages after verified non-conflicting payment; verify appropriate recipients, no guest price for host keepers, repeated events, mail failures and no direct-abandonment email.
- [ ] 4.4 Update all four localized email templates, booking UI labels and `docs/business-doc.mdc`/frontend documentation; verify links, price, indicative period, parking and pending/confirmed wording for both new and legacy flows.
- [ ] 4.5 Preserve manual guest contact on cancellation of approved unpaid bookings; verify no automatic guest cancellation email is sent, including when a payment outcome is uncertain.

## 5. Direct booking and public checkout

- [ ] 5.1 Add the third authorized booking mode and readiness gate while preserving external and request modes and in-flight offers across mode changes; verify selection, disabled/not-ready errors and legacy behavior.
- [ ] 5.2 Integrate existing form, server quote and mandatory legal consent into direct payment, with 30-minute configurable holds and no booking when price or availability is absent; verify data validation, price tampering, occupancy, stay limits and concurrent dates.
- [ ] 5.3 Build the localized, accessible, mobile-first embedded checkout and pending/success/failure states with cards, wallets and PayPal when supported; verify narrow mobile and desktop viewports, keyboard use, browser return, redirects, unavailable methods and no card details through the application.
- [ ] 5.4 Offer a normal owner-approved request only on definite Stripe startup failure and explicit guest choice; verify revalidated availability/consent and no duplicate request where a payment outcome is uncertain.
- [ ] 5.5 Update four-locale terms and consent snapshots for both booking paths, approved payment and cancellation policies; obtain owner/legal approval and verify the accepted version and policy persist after settings changes.
- [ ] 5.6 Apply mobile-first layout to payment settings and admin views and update `docs/business-doc.mdc` and `docs/specific-tech-frontend-doc.mdc`; verify public and admin controls on narrow screens without horizontal overflow.

## 6. External conflicts and staging

- [ ] 6.1 Detect and deduplicate external calendar overlaps during finalization and later synchronization; verify conflict before/after payment, imported calendar behavior, repeated alerts and owner-visible resolution without automatic refund.
- [ ] 6.2 Provision documented isolated HTTPS staging at `stage.lacaracolaandora.com` with separate database, fake customers/bookings, production-like settings, test Stripe keys, signed webhook and wallet domain registration; verify no staging write reaches production.
- [ ] 6.3 Configure password protection with a signature-verified webhook exception, protected online test inbox, disabled live calendars/Google/Telegram/reporting/newsletter and fail-closed environment checks; verify emails and background jobs never contact real recipients or live integrations.
- [ ] 6.4 Document cPanel staging, scheduler, test and live webhook setup, wallet/PayPal sandbox prerequisites and operational isolation in `docs/DEPLOY.md`, and update `README.md` for changed setup; verify instructions with the provisioned environment.

## 7. End-to-end rollout

- [ ] 7.1 Run focused automated backend and frontend checks plus a MySQL concurrency test; verify existing booking, calendar, authorization, localization and accounting regression suites pass.
- [ ] 7.2 Exercise sandbox happy paths for both modes, indicative periods and replaced/revoked links, abandoned direct checkout, manual cancellation during processing, late outcomes, payment failures, external conflicts, fee/refund updates, email capture and supported wallets/PayPal; record results without real payments.
- [ ] 7.3 Verify live account eligibility, webhook, HTTPS/domain registrations, cron, mail and Telegram readiness; review localized terms and policy settings with the owner before allowing explicit activation.
- [ ] 7.4 Document and rehearse rollback that stops new offers but keeps webhook, reconciliation and active payment records operational; verify no unresolved hold or payment history is deleted.
