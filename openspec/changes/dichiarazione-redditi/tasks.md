## 1. Database migrations

- [x] 1.1 Create migration: add `income_tax`, `cleaning_tax`, `linen_tax`, `parking_tax` (boolean) to `bookings` table with defaults from `config/finance.php` `tax_declaration_defaults`; set existing rows to those defaults
- [x] 1.2 Create migration: add `tax_declaration` (boolean, default false) to `financial_entries` table

## 2. Configuration

- [x] 2.1 Add `tax_declaration_defaults` array to `config/finance.php` with keys `income`, `cleaning`, `linen`, `parking` (true/true/true/false)

## 3. Models

- [x] 3.1 Add `income_tax`, `cleaning_tax`, `linen_tax`, `parking_tax` to `$casts` in `Booking` model as boolean
- [x] 3.2 Add `tax_declaration` to `$casts` in `FinancialEntry` model as boolean

## 4. Booking backend

- [x] 4.1 In `BookingController::store()` apply config defaults for the four `*_tax` fields when creating a new booking
- [x] 4.2 Add `income_tax`, `cleaning_tax`, `linen_tax`, `parking_tax` to `BookingController::update()` fillable/validated fields
- [x] 4.3 Add validation rules for the four tax flags (nullable boolean) in `BookingController` store and update

## 5. FinancialEntry backend

- [x] 5.1 Add `tax_declaration` to `FinancialEntryController` store/update validated fields
- [x] 5.2 Ensure `tax_declaration` is cast correctly when storing (checkbox sends "on"/null — convert to bool)

## 6. TaxDeclarationController

- [x] 6.1 Create `app/Http/Controllers/Admin/TaxDeclarationController.php` with `index(Request $request)` action
- [x] 6.2 Compute `$availableYears` from union of years with flagged booking items and flagged financial entries
- [x] 6.3 Compute `$totals` (income/expense sums) for selected year — only flagged+paid items
- [x] 6.4 Build `$movements` collection merging all flagged booking sub-items (income, cleaning, linen, parking) and flagged financial entries, sorted by effective date, including both paid and unpaid rows with a `included` boolean
- [x] 6.5 Pass `$year`, `$availableYears`, `$totals`, `$movements` to the view

## 7. Routes

- [x] 7.1 Add `GET /admin/dichiarazione-redditi` route in `routes/admin.php` under the `view_accounting` permission middleware group, pointing to `TaxDeclarationController@index`, named `admin.tax-declaration.index`

## 8. Tax declaration view

- [x] 8.1 Create `resources/views/admin/finance/tax-declaration.blade.php`
- [x] 8.2 Add year tabs (same style as Contabilità: `btn--primary` for active year, `btn--outline` for others)
- [x] 8.3 Add two summary boxes: "Totale entrate" (green, +) and "Totale uscite" (red, −), same card style as Contabilità stat cards
- [x] 8.4 Add movements table with columns: Data, Tipo (badge), Categoria/Descrizione, Importo (colored), Stato (Incluso/Da pagare), Azione (link to booking or entry)
- [x] 8.5 Add the two-tab nav bar ("Contabilità" / "Dichiarazione redditi") at the top of the page

## 9. Update Contabilità view

- [x] 9.1 Add the two-tab nav bar ("Contabilità" / "Dichiarazione redditi") to `resources/views/admin/finance/index.blade.php` above the existing toolbar, linking to both pages

## 10. Booking detail view

- [x] 10.1 Add "Dichiarazione dei redditi" sub-section to `resources/views/admin/bookings/show.blade.php` below the financial data card, wrapped in `@if(auth()->user()->hasPermission('view_accounting'))`
- [x] 10.2 Render a checkbox for each amount field that is non-null (income, cleaning, linen, parking) labeled respectively "Incasso", "Pulizie", "Biancheria", "Posto auto"
- [x] 10.3 Wrap the checkboxes in a `<form>` POST to `BookingController::update()` with `@method('PUT')` and a save button

## 11. FinancialEntry form view

- [x] 11.1 Add "Includi nella dichiarazione dei redditi" checkbox to `resources/views/admin/finance/form.blade.php`, unchecked by default, bound to `tax_declaration`

## 12. Documentation

- [x] 12.1 Update `docs/specific-data-model.md`: add `income_tax`, `cleaning_tax`, `linen_tax`, `parking_tax` columns to `bookings` table; add `tax_declaration` to `financial_entries` table
- [x] 12.2 Update `docs/business-doc.mdc`: document the tax declaration feature under Admin flows
- [x] 12.3 Update `docs/specific-tech-backend-doc.mdc`: document `TaxDeclarationController` and new route
