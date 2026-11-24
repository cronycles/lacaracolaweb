# Proposal

## Why

Direct guests currently receive bank-transfer instructions and the owner must manually verify and record payment. A reusable Stripe integration can simplify payment while supporting both owner-approved requests and immediate bookings without duplicating payment logic.

## What Changes

- Add an explicitly enabled Stripe payment capability shared by the existing owner-approved request flow and a third, immediately payable booking mode. Preserve external booking, existing bank-transfer bookings, and manual/imported booking workflows.
- Use an embedded Stripe checkout on a localized, mobile-first site page; offer cards, Apple Pay, Google Pay, and PayPal when supported and available. Record verified payments and send appropriate guest, owner, and host-keeper communications.
- Separate provider-independent booking and payment use cases from a Stripe-specific adapter, making later provider replacement localized rather than duplicating business logic.
- Keep approved-request cancellation and date release under owner control, with an indicative payment deadline; retain automatic temporary hold expiry for direct checkout, and flag payment or external-calendar conflicts for owner intervention.
- Add authorized admin controls for operational settings and visibility into payment, fee, net, refund, and exception status. Keep secret credentials in environment configuration.
- Provide an isolated, password-protected online staging environment at `stage.lacaracolaandora.com` with Stripe test data, separate database, safe captured email, and no production calendar or external-service side effects.
- Update localized terms and payment communications, accounting integration, documentation, and rollout procedures.

## Capabilities

### New Capabilities

- `stripe-booking-payments`: Shared payment lifecycle, Stripe verification, administration, financial visibility, and failure handling.
- `direct-payment-booking`: Immediate booking and payment, temporary availability holds, and fallback to a manual request.
- `payment-test-environment`: Isolated online staging and safe end-to-end testing of payment workflows.

### Modified Capabilities

- `booking-status-emails`: Add approved-request payment links and post-payment communications while preserving legacy and non-Stripe workflows.
- `booking-terms-page`: Describe direct payment and owner-approved flows, including the indicative payment deadline and manual cancellation, without changing the existing cancellation policy by default.
- `booking-accounting-receipts`: Record verified Stripe receipts and surface associated fee and refund information without treating parking or service expenses as paid.
- `external-calendar-availability`: Detect and escalate overlapping external events when payment is in progress or has succeeded.

## Impact

- Laravel booking, availability, pricing, settings, email, calendar, accounting, authorization, scheduling, and public checkout flows; Blade, TypeScript, and four supported locales.
- New payment records, lifecycle states, server-side Stripe SDK and signed webhook; validated admin settings and environment-only secrets.
- cPanel cron, HTTPS domains, Stripe account and wallet registration, isolated staging database, and email capture.
- Product, backend, frontend, data-model, deployment, and setup documentation plus targeted automated and end-to-end verification.
