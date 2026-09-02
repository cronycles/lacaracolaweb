## Why

The admin needs a consolidated tax declaration view to simplify the annual Italian income tax return (dichiarazione dei redditi). Currently, financial data is scattered across bookings and accounting entries, making it difficult to extract only the items that should appear on the declaration. Adding opt-in flags lets the admin control exactly which items count toward the declaration.

## What Changes

- New admin page **Dichiarazione dei redditi** (tab alongside the existing Contabilità page) showing only flagged, paid items grouped by year.
- New boolean flag `tax_declaration` on `financial_entries` table: when checked, the entry appears on the declaration page.
- Four new boolean flags on `bookings` table (`income_tax`, `cleaning_tax`, `linen_tax`, `parking_tax`): when checked, the corresponding paid amount appears on the declaration page.
- Default values for the booking flags are driven by a new key in `config/finance.php` (`tax_declaration_defaults`), applied automatically at booking creation and pre-populated via migration for existing records.
- The new page follows the same year-tab / totals-box / table layout as the Contabilità page.
- Only **paid** amounts are included in the declaration totals (cash-basis accounting).
- The booking detail page (show.blade.php) gains a new "Dichiarazione dei redditi" section at the bottom of the financial data area with checkboxes for the four flags.

## Capabilities

### New Capabilities

- `tax-declaration-page`: New admin page at `/admin/dichiarazione-redditi` showing flagged paid items by year with year tabs, income/expense total boxes, and a detailed table.
- `booking-tax-flags`: Per-booking checkboxes (income, cleaning, linen, parking) to include/exclude each paid item from the tax declaration. Defaults configurable in `finance.php`.
- `financial-entry-tax-flag`: Per-entry checkbox on `financial_entries` to include it in the tax declaration.

### Modified Capabilities

_(none — no existing spec files to delta)_

## Impact

- **DB migrations**: add `income_tax`, `cleaning_tax`, `linen_tax`, `parking_tax` (boolean) to `bookings`; add `tax_declaration` (boolean) to `financial_entries`.
- **`config/finance.php`**: new `tax_declaration_defaults` array with keys `income`, `cleaning`, `linen`, `parking` (booleans).
- **Booking model** (`app/Models/Booking.php`): cast four new boolean fields.
- **FinancialEntry model** (`app/Models/FinancialEntry.php`): cast `tax_declaration` boolean.
- **BookingController** (`app/Http/Controllers/Admin/BookingController.php`): apply config defaults on store; handle four new flags on update.
- **FinancialEntryController** (`app/Http/Controllers/Admin/FinancialEntryController.php`): handle `tax_declaration` flag on store/update.
- **New controller** `TaxDeclarationController`: compute year totals (income/expense) and row list from flagged+paid data.
- **New route** `GET /admin/dichiarazione-redditi` (with optional `?year=YYYY`).
- **New view** `resources/views/admin/finance/tax-declaration.blade.php`.
- **Modified views**: `bookings/show.blade.php` (new section), `finance/form.blade.php` (new checkbox), `layouts/admin.blade.php` (new nav tab or link).
- **Permission guard**: same `view_accounting` permission as the Contabilità page.
