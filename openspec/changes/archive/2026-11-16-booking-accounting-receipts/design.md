## Context

The booking amount, cleaning, linen and parking are stored on each booking. Accounting and the dashboard currently add paid cleaning and linen as both income and expense, and use checkout when a payment date is missing. Tax declaration duplicates that logic and also displays unpaid rows. Guest emails derive the house price from the stored booking amounts.

The owner wants real cash movements and an independent indicative net. Historical totals are recalculated from bookings, not stored as immutable journal entries.

## Goals / Non-Goals

**Goals:**

- Count each actual booking receipt or payment once, on its own date.
- Keep included-parking state synchronized across all booking update paths.
- Recalculate existing bookings consistently and identify checked statuses without dates.
- Preserve existing permission boundaries and unrelated public price estimation.

**Non-Goals:**

- Partial payments, additional standalone cleaning or linen receipts, or separate cleaning and linen payment dates.
- Automatic correction of missing dates or automatic edits to historical amounts.
- Blocking a cleaning or linen payment because its inclusion flag is off.
- Changing public pricing rules or interpreting fiscal law.

## Decisions

1. Store three explicit boolean inclusion flags on bookings. Default all flags to false for every new booking creation path. Existing records receive cleaning/linen inclusion from their respective paid flags and parking inclusion false, including when a paid flag lacks a date. This is more reliable than guessing from amounts or booking source.
2. Keep the existing amount and payment columns. The amount is renamed in the interface, not reinterpreted as a separate stored receipt. The booking-receipt paid status still determines whether it is an actual receipt.
3. Use the booking receipt's status and date as the authoritative values for included parking. Derive or synchronize parking state server-side as well as in the form, including payment-received email actions. When inclusion is removed, retain the last synchronized parking status and date as independently editable starting values. Disabling a form input must not discard its value.
4. Centralize movement eligibility and amount/date rules sufficiently to keep accounting totals, monthly figures, prior-year and global balances, movement lists, dashboard and tax declaration consistent. Do not use checkout as a fallback for booking movements. Ordinary financial entries retain their existing behavior.
5. Cleaning and linen produce only expenses; their shared payment date remains independent from the booking receipt. An expense is recognized with its own paid check and date even if the corresponding inclusion flag is off. No separate service receipt is created.
6. Compute the displayed indicative net as booking amount plus parking only if not included, minus cleaning and linen, regardless of payment status or cleaning/linen inclusion. A missing booking amount gives an unavailable result; missing service amounts count as zero. Keep it separate from accounting and hide it wherever accounting permissions require.
7. Tax flags remain independent: booking income requires the income tax flag, cleaning and linen expenses require their own flags, and separate parking income requires the parking tax flag. An included item never generates a second tax income, even if the booking income tax flag is off. No fiscal flags are cleared merely because inclusion changes.
8. Use the stored booking amount as the guest-facing house total, including when parking is part of it. Show parking separately as descriptive detail with an appropriate included/already-paid/pay-on-arrival message. Keep the public quote engine unchanged.
9. Validate on the server and provide immediate form feedback: checked payment statuses require dates; included amount totals cannot exceed the booking amount; empty service amounts count as zero. Existing bookings follow the same validation on save; preserve entered values when validation fails.

## Risks / Trade-offs

- Historical income and balances will change intentionally, including previous years. The included-service inference is based on paid status, not proof of receipt; the owner will review records.
- Existing checked statuses with missing dates disappear from accounting until corrected. Run a read-only diagnostic immediately before the rollout and report booking IDs and missing date types; never fill dates from checkout. The production read-only check on 2026-11-15 found none among 23 bookings, but data may change.
- A booking can temporarily show a net different from its realized accounting balance, particularly during historical review. Label it as indicative and do not use it as an accounting total.
- The new validation can prevent saving historical bookings until inconsistent dates or included amounts are corrected. Preserve the existing stored data until the owner resolves validation errors.
- Reusing one receipt for different tax flags cannot assign included components independently to tax income; the agreed rules include the whole amount only through the booking income flag. Service tax flags still control their expenses.
- Production schema or data changes require explicit owner approval, an appropriate backup and a verified rollback plan. Rolling back the schema alone removes the new inclusion data; restoring a backup may also undo subsequent legitimate edits, so do not restore without separate authorization.

## Migration Plan

1. Validate the migration and recalculation against representative test data without touching production.
2. Before any production migration, repeat the read-only missing-date diagnostic and report its results to the owner.
3. Obtain explicit owner authorization and a suitable backup before applying schema and initialization changes to production.
4. Verify the initialized flags and representative booking, accounting, dashboard and tax results. Report any records requiring manual review.
5. If rollback is needed, assess whether dropping inclusion fields or restoring a backup would lose later edits and obtain separate authorization for the chosen recovery action.

## Open Questions

None identified in the agreed functional rules; the owner will review this proposal before implementation.
