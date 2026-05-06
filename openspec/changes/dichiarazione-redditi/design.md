## Context

The application already has a full Contabilità (accounting) page (`/admin/contabilita`) that merges booking financial items (income, cleaning, linen, parking) with standalone `financial_entries`. The existing data model tracks whether each amount is paid but has no concept of "should this appear on my income tax return". The admin needs to produce an annual income tax declaration using only a subset of items — chosen per-row — following Italian cash-basis accounting rules.

Current state:
- `bookings` has `income_paid`, `cleaning_paid`, `linen_paid`, `parking_paid` (bool) and corresponding amounts and paid-at dates.
- `financial_entries` has `type` (income/expense), `amount`, `entry_date`.
- No flag for "include in tax declaration" exists on either table.

## Goals / Non-Goals

**Goals:**
- Add `income_tax`, `cleaning_tax`, `linen_tax`, `parking_tax` boolean columns to `bookings` (with config-driven defaults).
- Add `tax_declaration` boolean column to `financial_entries`.
- New page `/admin/dichiarazione-redditi?year=YYYY` reusing the year-tab / summary-box / table layout pattern from Contabilità.
- Only include amounts that are both **flagged** and **paid** in the declaration totals.
- Booking detail page gets a "Dichiarazione dei redditi" sub-section with four checkboxes, saved inline with the existing update flow.
- `FinancialEntry` create/edit form gets a single `tax_declaration` checkbox.
- Defaults for the four booking flags come from `config/finance.php` `tax_declaration_defaults` and are applied at booking creation time and via a one-time migration default for existing rows.

**Non-Goals:**
- No PDF/export generation for the actual tax form.
- No locking/freezing of a declaration once submitted.
- No per-guest or per-stay tax calculation.
- No change to permission model (reuse `view_accounting`).

## Decisions

### D1 — Separate controller vs. expanding FinancialEntryController
**Decision:** New dedicated `TaxDeclarationController` with a single `index` action.  
**Rationale:** The Contabilità controller is already large (year totals, monthly breakdown, movement list, unpaid cards). Keeping tax declaration logic separate follows single-responsibility and avoids further bloating. The new controller mirrors the same query patterns.  
**Alternative considered:** Adding a `taxDeclaration()` method to `FinancialEntryController` — rejected because it would share routing namespace and mix concerns.

### D2 — Flag semantics: "flagged AND paid" for totals
**Decision:** A flagged item only contributes to the declaration totals and table if the corresponding paid flag is also true. Items that are flagged but not yet paid are shown in the table with a "da pagare" indicator but are excluded from totals.  
**Rationale:** Italian IRPEF uses cash-basis accounting (principio di cassa). An amount that has not been received/paid yet should not appear on the year's declaration.  
**Alternative considered:** Show all flagged items regardless of paid status — rejected because it would inflate the declared income.

### D3 — Date logic for year assignment
**Decision:** Same as Contabilità: `COALESCE(income_paid_at, checkout)` for booking income; `COALESCE(services_paid_at, checkout)` for cleaning and linen; `COALESCE(parking_paid_at, checkout)` for parking; `entry_date` for `financial_entries`.  
**Rationale:** Consistency with the existing accounting view — using different date logic would produce numbers that don't reconcile with the main Contabilità page.

### D4 — Config-driven booking flag defaults
**Decision:** New key `tax_declaration_defaults` in `config/finance.php`:
```php
'tax_declaration_defaults' => [
    'income'   => true,
    'cleaning' => true,
    'linen'    => true,
    'parking'  => false,
],
```
Defaults: income, cleaning, linen = true; parking = false.  
**Rationale:** The admin explicitly asked for income, cleaning, and linen to default to true. Parking is usually not declared as rental income and defaults to false. All values are overridable in the config file.  
**Migration:** Existing rows get the config defaults via a migration that reads the config and sets the column defaults accordingly. Future rows get defaults applied in `BookingController::store()`.

### D5 — Nav placement: tab alongside Contabilità
**Decision:** Add a second tab "Dichiarazione redditi" in the same toolbar bar used by the Contabilità page (the year-selector + "Nuova voce" toolbar area). The two pages share the same top-level nav item "Contabilità" with sub-tabs rendered on both pages.  
**Alternative considered:** Separate top-level nav item — rejected by the user, who prefers a tab.

### D6 — Booking update flow for tax flags
**Decision:** The four tax flag checkboxes in the booking detail are saved through the existing `BookingController::update()` route. No new AJAX endpoint. The booking show page has an inline form wrapping only the tax section (similar to how other partial forms work in the admin).  
**Rationale:** Keeps the backend simple and consistent. The update route already handles all booking financial fields.

## Risks / Trade-offs

- **Existing bookings get migration defaults**: All existing bookings will have `income_tax`, `cleaning_tax`, `linen_tax` set to `true` and `parking_tax` to `false` (from config). The admin may want to review these manually, but this is the most sensible baseline.  
  → Mitigation: clearly document in the UI that defaults were applied and items can be individually unchecked.

- **No rollback for migration defaults**: The boolean default migration cannot be auto-rolled back to restore the "before" state (columns are dropped on rollback).  
  → Mitigation: this is a non-destructive additive migration; rollback drops the columns and all flag state is lost, which is acceptable since the feature did not previously exist.

- **Parking not declared by default**: Parking income is set `false` by default. If the admin's real tax obligation includes parking, they must either flip the config or manually check each booking.  
  → Mitigation: config override documented; UI checkboxes allow per-booking correction.

## Migration Plan

1. Create migration: add columns to `bookings` (4 booleans, default from config) and `financial_entries` (1 boolean, default false).
2. Add config key to `finance.php`.
3. Implement `TaxDeclarationController` + route + view.
4. Update `BookingController` (store defaults, update fillable, validate new fields).
5. Update `FinancialEntryController` (fillable, validate `tax_declaration`).
6. Update models (casts).
7. Update views: booking show (new section), finance form (new checkbox), admin nav (new tab).
8. Deploy: `php artisan migrate` — no seed changes needed.

Rollback: `php artisan migrate:rollback` drops the four/one columns; remove the config key and revert view/controller changes.
