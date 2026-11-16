## Why

Booking receipts currently double-count service and parking amounts in some cases, while payment dates and the booking's indicative net are not represented consistently. Accounting must reflect actual receipts and payments without counting included services twice.

## What Changes

- Rename the booking amount field to "Importo prenotazione" and add three per-booking flags indicating whether cleaning, linen and parking are included in that amount.
- Recognize booking movements only when their payment status is checked and their actual date is present; never generate separate cleaning or linen income.
- Synchronize included parking's received status and date with the booking receipt, and keep separate parking independently manageable.
- Show an indicative booking net independent of payment status, without using it as an accounting balance.
- Recalculate accounting, dashboard and tax-declaration figures under the new rules and update booking emails to avoid duplicate amounts and incorrect parking payment instructions.
- **BREAKING** Recalculate historical balances: mark existing paid cleaning and linen as included and existing parking as not included, without inventing missing dates.
- Validate included amounts and payment dates, and report existing bookings with checked payment statuses but missing dates.

## Capabilities

### New Capabilities

- `booking-accounting-receipts`: Booking receipt inclusions, payment dates, indicative net, accounting movements, and historical conversion.

### Modified Capabilities

- `tax-declaration-page`: Show and total only correctly dated and paid booking movements, without separate service income or duplicated included parking.
- `booking-status-emails`: Use the booking amount and parking payment state consistently in booking communications.
- `admin-dashboard`: Align booking financial summaries and parking payment state with the new accounting rules.

## Impact

- Booking data model, migration, administrative booking form and detail, payment-received action, and finance calculations.
- Accounting, tax declaration, dashboard totals, monthly and historical balances, and year navigation.
- Guest and operational booking emails; public pricing calculations remain unchanged.
- Tests and affected business, data-model, backend and frontend documentation.
- Production data migration requires explicit owner confirmation before execution.
