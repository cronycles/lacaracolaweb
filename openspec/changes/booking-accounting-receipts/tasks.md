## 1. Booking Data and Historical Initialization

- [x] 1.1 Add persistent inclusion flags with false defaults for all new booking creation paths; initialize existing cleaning and linen flags from their paid statuses and parking to false without altering other fields.
- [x] 1.2 Add focused migration tests for existing paid/unpaid services, missing dates and default flags across booking creation paths.
- [ ] 1.3 Prepare a read-only diagnostic listing booking IDs and payment types with checked statuses but missing dates; rerun and report it before any authorized production migration.
- [x] 1.4 Populate the booking amount from the quoted house total when converting an availability request, without automatically enabling inclusion flags.

## 2. Booking Form and Validation

- [x] 2.1 Rename the booking amount and receipt labels, add inclusion checks, and display the indicative net in the form and booking detail with existing permission boundaries.
- [x] 2.2 Synchronize included parking's status and date in the form and server-side, including the payment-received email action; preserve the synchronized values when parking becomes separate.
- [x] 2.3 Validate checked statuses and dates, included amount limits and missing amounts on the server while retaining input and allowing paid services without inclusion.
- [x] 2.4 Test net examples, missing and negative amounts, immediate form updates, parking synchronization and all validation cases.

## 3. Accounting and Declaration

- [x] 3.1 Apply consistent checked-and-dated booking movement rules to accounting movement rows, yearly/monthly figures, prior-year and global balances, without checkout fallbacks or separate service income.
- [x] 3.2 Align dashboard financial totals and parking payment state with the same rules.
- [x] 3.3 Update tax declaration movement rows, totals and available years, respecting each tax flag and excluding duplicate included income.
- [x] 3.4 Test combinations of included and separate parking, unpaid and undated statuses, service expenses without inclusion, tax flags and movements spanning months and years.

## 4. Booking Communications

- [x] 4.1 Update guest confirmation and payment-received emails to show the stored booking amount and accurate included, separately paid or locally payable parking details.
- [x] 4.2 Update the operational email's parking instruction and preserve the existing public quote calculations.
- [x] 4.3 Test guest and operational email output before receipt and across parking payment states.

## 5. Documentation and Rollout

- [x] 5.1 Update the affected business, data-model, backend and frontend documentation.
- [x] 5.2 Run targeted tests and relevant project checks; review historical recalculation and permission behavior.
- [ ] 5.3 Before production schema or data changes, obtain explicit owner approval, confirm a suitable backup and rollback plan, and report the current missing-date diagnostic.
- [ ] 5.4 After any authorized deployment, verify representative migrated records, accounting, dashboard and declaration results and report records requiring manual review.
