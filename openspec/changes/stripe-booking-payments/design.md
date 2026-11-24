# Design

## Context

The site is a Laravel/Blade monolith deployed on cPanel, with MySQL in production, SQLite for tests, four locales, and an existing minute-level scheduler. `BookingRequest` and pending `AvailabilityBlock` records currently block public dates, approval creates a linked `Booking`, and the owner manually sends bank-transfer and paid emails. The public quote service computes the house price in cents and keeps parking separate. External iCal imports and Google calendar exports run periodically, not atomically with other booking platforms.

The admin UI already persists pricing settings even though older project guidance describes a narrower settings table. This change explicitly extends operational settings for payment policy and must update that guidance rather than embedding operational values in code. The current model treats every non-cancelled booking as unavailable and pending requests are also exported to calendars, so pending Stripe states and expiry must be integrated consistently across availability, calendar views, iCal, and Google push.

## Goals / Non-Goals

**Goals:**

- Provider-independent payment domain and application use cases shared by both direct-booking paths, with Stripe confined to an adapter and its integration boundary.
- Make commercial intervals and thresholds editable by authorized administrators, while preserving the terms of offers already sent or accepted.
- Preserve existing manual, imported, external, and pre-activation booking behavior.
- Make actual paid state recoverable despite retries, delayed events, cancellation, failures, and synchronization races.
- Support realistic online staging without live financial or communications side effects.

**Non-Goals:**

- Automatic refunds, a guest self-service cancellation portal, deposits/installments, Stripe surcharges, or collection of separate parking.
- Guaranteed real-time availability across external booking platforms; their feeds are eventually consistent.
- Applying new Stripe flows retroactively to existing unpaid bank-transfer bookings.
- Exposing Stripe secret keys in the admin UI or sending secrets to the assistant.

## Decisions

### Policy and activation

Keep Stripe disabled by default; enabling requires authorized explicit action and readiness checks (configured matching environment keys, reachable signed webhook configuration, HTTPS, scheduler, and required payment-method account/domain setup). Never infer readiness solely from a saved public key. Preserve `form` and `external` modes and introduce a distinct direct-payment mode; enabling Stripe changes only new approved website requests, not existing unpaid bookings. Admin-made and imported bookings retain current behavior. A later mode or activation change does not strand in-flight offers or webhooks.

Add a dedicated admin payment-settings section, restricted to existing appropriate owner/super-admin permissions. Persist validated, bounded non-secret operational settings with defaults in config: approved-request indicative payment period **48 hours**, direct checkout hold **30 minutes**, processing alert threshold **1 hour** as an initial operational default, and cancellation window **14 days**. Display units, consequences, and Stripe/provider constraints; reject invalid input rather than silently clamping. The indicative period never automatically invalidates the approved link or releases dates; the owner acts manually. Show overdue state in admin. Snapshot policy, communicated period, price, locale, and a version or immutable rendering of the terms per offer; changes apply to new offers only, with explicit disclosure and legal review of modified terms. Use configuration/environment for secrets, provider environment, mail routing and integration isolation; never expose or commit them. UTC instants and the site's configured local timezone determine display and cancellation cutoff.

### Provider-independent architecture

Define a narrow application-owned payment gateway contract in domain terms: create or resume a checkout, inspect a payment attempt, invalidate an open checkout where supported, and translate verified provider notifications and financial updates into normalized events. Booking, availability, accounting and messaging use cases consume this contract and stable internal IDs, amounts and statuses; they do not depend on Stripe SDK objects, event names or session IDs. Bind a Stripe adapter in infrastructure, including SDK, signature verification, provider IDs, webhook parsing, checkout presentation and Stripe-specific idempotency and error mapping. Store provider identity plus external IDs in the payment history without making booking rules depend on Stripe.

Stripe remains the only production implementation; do not build a speculative multi-provider framework. Provider-specific checkout UI, webhook setup, wallet availability and historical reconciliation may need changes when switching providers. Verify the boundary with a fake adapter and shared application-level contract tests, and preserve the original provider on existing attempts so later provider changes cannot strand them.

### State, holds, and checkout

Represent a payment offer and its attempt history separately from `Booking`/`BookingRequest`. Persist opaque token hashes, state, expiry, snapshot amounts in integer cents, currency, Stripe object IDs, mode/account identity, idempotency keys, receipt/fee/refund information, and guest notification status. Create a unique identifier for each attempt and a unique verified event/inbox record. Show sensitive payment details only to users with the same finance access as existing booking amounts. Do not equate Stripe balance payout date with guest payment date.

Use the existing quote and guest matching logic. Recompute and validate all inputs server-side, persist the existing consent evidence and versioned terms, then acquire a local half-open date hold in one atomic DB transaction. A singleton property-level lock row (one apartment) serialized using MySQL row locking covers availability recheck and hold/booking creation across both Stripe and existing local request/admin paths; a check followed by insert alone is not safe. Define equivalent tests for MySQL concurrency; SQLite tests alone cannot prove row-locking behavior. Recheck availability before a Stripe attempt and on finalization, ignoring only the attempt's own hold. Synchronize only known external data; no local DB lock can prevent an external platform from booking concurrently.

An approved request keeps its existing booking and verified price; the owner explicitly sends a link. A direct request creates a pending record and hold, not a fully confirmed booking; on verified success reuse guest matching and convert once, preserving quote/inclusion defaults and legal evidence. Block/booking state must be consistent across public and admin calendars, iCal exports, and Google push. No automatic guest email for abandoned direct checkout. On unavailable price, show not bookable in direct mode. On definite Stripe startup failure before an attempt exists, offer an explicitly selected normal request and atomically transfer the hold after rechecking availability; never offer this as a second booking when payment state is uncertain.

Implement a Laravel Stripe adapter using the official PHP SDK and an embedded Checkout Session on a localized site route, shared by both flows. Design public and admin payment workflows mobile-first, testing narrow viewports, keyboard access, legibility, no horizontal overflow and provider redirect/return. Verify that enabled card, wallet and PayPal support works for the merchant account and embedded integration; register production and staging domains independently and use HTTPS. PayPal may temporarily redirect to PayPal; test requirements depend on the presented button. The price comes only from the persisted server snapshot, with no customer surcharge; parking stays locally payable.

### Deadlines, attempts, and cancellation

Stripe Checkout Session expiration is limited to 30 minutes–24 hours from creation. The approved-request link is an application-owned link with an indicative payment period, not a Stripe session or an automatic expiry deadline. Create sessions only when the guest opens the link and safely recreate expired Stripe sessions while the approved booking remains active. Direct-checkout sessions and holds still have a bounded lifetime; expiry requires confirming that no payment can still succeed. Do not rely on a client timer.

Replacing a link, changing the offered amount, manually cancelling, restoring, or changing dates must serialize with payment state. Revoke the old token and expire its open session before enabling a new offer; if a payment may be in flight, suspend replacement and reconcile or require owner intervention. A retry or failed email send must not create parallel payable offers. Use an outbox with a stable email idempotency key and a persisted indicative date; derive it from the recorded successful send, with safe handling of uncertain mail delivery. A resend explicitly warns, invalidates the prior offer, snapshots the new amount/policy, and starts a fresh indicative interval upon sending, without automatic cancellation. Do not expose reusable or guessable booking IDs in public links; throttle token access and avoid logging tokens.

For direct checkout, a scheduled job closes expired sessions, checks authoritative state and only automatically releases a definitively unpaid hold. Pending or unknown direct payment stays blocked until resolved; the stale-processing threshold produces an actionable alert. For an approved request, the owner cancels the existing booking manually; the system revokes the link and attempts to expire any open session, without automatically emailing the guest. If a payment is processing or its outcome is uncertain, explain the risk and allow the owner to explicitly confirm immediate cancellation and release. Persist the payment attempt and cancellation audit even if a request is deleted; prevent destructive deletion from erasing financial references. If the provider cannot close a session, keep the link revoked and alert the owner. Any later success is recorded and flagged for manual refund, never silently discarded, auto-refunded, or treated as a confirmed booking.

### Webhooks, recovery, and communication

Verify Stripe signature against the raw body and environment-specific endpoint secret before reading events. Check expected livemode, account, currency, exact amount, offer/attempt identity, and eligible state. Persist an event inbox with unique Stripe event IDs; ACK only after durable acceptance. Process events with row locks and idempotent transitions, tolerating retries and out-of-order arrival; reconcile against Stripe when notification ordering or delayed delivery leaves state ambiguous. An idempotent scheduled reconciler handles missing webhook deliveries, expiring sessions, pending attempts, fees, refunds, and owner-visible errors. Use deterministic per-offer/per-attempt idempotency keys for API operations and never make unbounded external HTTP calls while holding a DB lock.

Persist payment and transition first, then use an outbox/retryable delivery record for guest confirmation, owner notice, and separate host-keeper operational mail. Show a pending result on browser return until verified. A successful payment without conflict marks booking income received with payment date exactly once; service expense and separate parking flags remain unchanged. Deliver host-keeper information without guest financial amounts. Flag duplicate charges, mismatched amounts, late success, disputes, and failed delivery for manual action rather than hiding them.

Stripe refunds are initiated in the Stripe dashboard. Signed refund/dispute events and periodic reconciliation update an auditable payment/refund timeline; cancellation remains a separate explicit owner action, and no refund is automatic. Show gross receipt, fees, net proceeds, and refunds separately with unavailable/pending states; preserve existing accounting and tax classifications and make refund adjustments explicit so reports do not misrepresent refunded gross as retained proceeds. Do not infer settlement fees before Stripe reports them or treat a cancellation as proof of refund.

### External calendars and conflicts

During finalization check already imported events and thereafter compare newly synchronized events against pending and confirmed Stripe holds. Store a deduplicated conflict by provider event and booking/hold, alert email plus Telegram if configured, and expose an owner resolution workflow. On known conflict before finalization, record the payment but do not send final confirmation; after confirmation, alert without silently undoing it. Distinguish genuine external reservations from echoes of the site's own exported blocks where provider identifiers make that possible; otherwise surface ambiguous conflicts for review. These controls reduce but cannot eliminate cross-platform double booking.

### Isolation and operations

Deploy staging to `https://stage.lacaracolaandora.com`, with an independently configured application, separate MySQL database and Stripe sandbox/test keys, webhook secret and domain registration. Populate non-sensitive configuration and fictitious records only. Use a protected browser login or HTTP access control with a narrow exception for the signature-verified webhook and any necessary provider return routes. Capture and redirect **all** staging mail to a protected online inbox, without sending to original recipients. Disable external-calendar imports/exports, Google writes, Telegram, guest reporting, newsletter, and other real integrations by default; use fakes or explicitly isolated sandbox services for tests.

Fail closed on verified environment mismatches: require explicit staging allowlists for DB host/name, Stripe test mode, mail sink, and enabled external integrations; never use production credentials or data. Treat allowlists and separation as operational controls, not proof that arbitrary endpoints are safe. Document provision, protected access, test key management, webhook forwarding, wallet/PayPal sandbox setup, and inspection. Run the existing scheduler on staging with staging-only configuration.

## Risks / Trade-offs

- [Delayed or unknown provider result] → Keep dates blocked and reconcile unless the owner explicitly accepts the risk and releases an approved booking manually; track late payments for manual refund.
- [External feed delay] → Detect conflicts on synchronization, alert urgently, and explicitly avoid promising cross-platform atomicity.
- [Stripe session expires while approved link remains valid] → Recreate safe sessions while the approved booking is active and reconcile session state before another charge.
- [Mail transport cannot prove exactly-once delivery] → Persist and retry outbox items, prevent parallel sends, and expose ambiguous delivery for review; do not claim perfect exactly-once SMTP delivery.
- [Provider or account configuration limits methods] → Verify cards, wallets, PayPal and domains in staging before production activation; show only available options.
- [Policy or legal changes] → Snapshot terms and deadlines for each offer and obtain owner/legal approval for updated localized text.
- [Old guidance conflicts with actual settings] → Document the explicit new payment-policy settings and align related backend/data-model docs.

## Migration Plan

1. Add backward-compatible schema, default Stripe disabled, and leave old booking/payment values unchanged. Deploy code, scheduler jobs, and admin visibility without changing existing mode.
2. Provision isolated staging and complete real sandbox tests including concurrent bookings, expiry, refunds, failed mail, provider event retries, external conflicts, and all available methods.
3. Configure live keys and signed webhook privately; register domains and verify scheduler, transactional email, alerts, and account readiness. Have the owner review localized legal copy and operational values.
4. Explicitly activate Stripe for new approved requests; the owner independently selects direct payment when ready. Monitor reconciliation and exceptions.
5. To roll back, disable new Stripe offers and choose an existing booking mode while keeping webhook, scheduler, reconciliation and existing payment records operational until all active attempts are resolved. Never delete or roll back payment history or release holds with uncertain payment state.

## Open Questions

- The protected capture-inbox provider remains a deployment choice and must satisfy the isolation requirements.
- Confirm production account eligibility, provider-specific wallet/PayPal behavior, and Stripe webhook connectivity during staging rather than assuming enablement in the dashboard guarantees availability.
