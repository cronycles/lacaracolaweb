## 1. Settings persistence

- [x] 1.1 Add `pricing_tax_rate`, `pricing_tax_gross_up_items`,
  `pricing_commission_airbnb`, `pricing_commission_booking`,
  `pricing_commission_hometogo`, `pricing_weekly_discount_percent`,
  `pricing_monthly_discount_percent` keys to the `Setting` store usage (no
  migration — `settings` table already exists). Document defaults (`0.21`,
  `["cleaning","linen"]`, `0.155`, `0.165`, `0.155`, `0.10`, `0.20`) as fallback
  values wherever `Setting::get()` is called for these keys.
- [x] 1.2 Add `SettingsController::updatePricing(Request $request)` validating the
  tax rate (numeric, 0–100), gross-up items (subset of `cleaning`/`linen`/`parking`),
  the 3 commission rates (numeric, 0–100), and the 2 discount percentages (numeric,
  0–100), converting percentages to stored decimal fractions, and persisting via
  `Setting::set()`.
- [x] 1.3 Add route `PUT admin/settings/pricing` → `admin.settings.pricing.update`
  in `routes/admin.php`, alongside the existing `admin.settings.update` route.
- [x] 1.4 Pass current pricing settings (with defaults applied) into
  `SettingsController::index()`'s view data.

## 2. Length-of-stay discount + tax gross-up calculation (`PricingQuoteService`)

- [x] 2.1 Add private helpers `resolveTaxRate(): float`,
  `resolveTaxGrossUpItems(): array`, `resolveWeeklyDiscountRate(): float` and
  `resolveMonthlyDiscountRate(): float` reading the new `Setting` keys with the
  documented defaults. Define `WEEKLY_THRESHOLD_NIGHTS = 7` and
  `MONTHLY_THRESHOLD_NIGHTS = 28` as class constants.
- [x] 2.2 In `calculate()`, after computing the nightly-stay sum (rename the
  variable to `stayGrossCents`), compute `lengthDiscountRate` from `$nights` (monthly
  tier if `>= 28`, else weekly tier if `>= 7`, else `0`), then
  `stayDiscountCents = round(stayGrossCents * lengthDiscountRate)` and
  `discountedStayCents = stayGrossCents - stayDiscountCents`.
- [x] 2.3 After computing `cleaningCents`/`linenCents`/`parkingCents`, compute
  `taxableCents` from the selected items that contribute to `total_cents` (today:
  cleaning + linen only), then `taxGrossUpCents = round(taxableCents * taxRate)`.
- [x] 2.4 Compute `rawTotalCents = discountedStayCents + taxGrossUpCents + cleaningCents + linenCents`.
- [x] 2.5 Round `rawTotalCents` to the nearest 500 cents; compute the rounding
  delta and fold it into `stayCents = discountedStayCents + taxGrossUpCents + roundingDelta`.
- [x] 2.6 Update the returned array: keep `stay_cents`/`total_cents`/
  `avg_per_night_cents` semantics (now discounted, tax-aware and €5-rounded), add
  `stay_gross_cents`, `stay_discount_cents`, `length_discount_rate` and
  `tax_gross_up_cents` for UI transparency. Update the `unavailable()` array shape
  to include the four new null fields.
- [x] 2.7 Update the PHPDoc `@return` annotations on `calculate()` and
  `unavailable()` to list the new fields.

## 3. Portal price suggestions (`OtaPortalPricingService`)

- [x] 3.1 Create `app/Services/OtaPortalPricingService.php` with a method
  `suggest(int $totalCents, int $nights): array` returning, for each of
  `airbnb`/`booking`/`hometogo`: `total_cents`, `avg_per_night_cents`,
  `commission_rate`, reading commission settings via `Setting::get()` with the
  documented defaults.
- [x] 3.2 Reuse the same weekly/monthly discount lookup as `PricingQuoteService`
  (`resolveWeeklyDiscountRate`/`resolveMonthlyDiscountRate` — consider extracting
  a small shared helper to avoid duplicating the tiered lookup) to compute
  `lengthDiscountRate` from `$nights`.
- [x] 3.3 Implement the formula
  `portal_total = round(total_cents / ((1 - lengthDiscountRate) * (1 - commission_rate)))`
  and `portal_avg_per_night = round(portal_total / nights)`.

## 4. Admin controller wiring

- [x] 4.1 Inject `OtaPortalPricingService` into
  `Admin\PricingController::simulate()` and merge its output (under a `portals` key)
  into the existing JSON response, alongside `tax_gross_up_cents`,
  `stay_gross_cents`, `stay_discount_cents` and `length_discount_rate` from the
  quote.

## 5. Admin UI

- [x] 5.1 Add a "Fiscalità e prezzi" card to `resources/views/admin/settings.blade.php`
  with: tax rate input, 3 gross-up item checkboxes (with a help note on the
  currently-inert "parking" option), 3 portal commission inputs, and 2 discount
  inputs (weekly / monthly); wire the form to `admin.settings.pricing.update`.
- [x] 5.2 Update `resources/views/admin/pricing/index.blade.php` simulator result
  box to show the tax gross-up line, the length-of-stay discount line (when
  applicable), and 3 portal price cards (total + avg/night).
- [x] 5.3 Update `resources/ts/components/pricing-simulator.ts` `SimulationResponse`
  interface and rendering logic to consume `tax_gross_up_cents`,
  `stay_gross_cents`, `stay_discount_cents`, `length_discount_rate`, and `portals`.
- [x] 5.4 Update `resources/views/admin/stay-discounts/index.blade.php` ("Formula
  prezzi") to document the length-of-stay discount + tax gross-up + €5 rounding +
  portal markup formulas, replacing/extending the existing "Costi Fissi"
  explanation. Do not touch the underlying `StayDiscountRuleController` routes/
  CRUD — this page keeps rendering static documentation content, not a list of
  `StayDiscountRule` records.

## 6. Docs cleanup

- [x] 6.1 Delete `docs/commissioni portali.md` (content preserved in
  `openspec/changes/tax-gross-up-pricing/design.md`).
- [x] 6.2 Update `docs/specific-tech-backend-doc.mdc`'s `PricingQuoteService`
  description (currently states "Returns `total_cents` as the house total... No
  discount logic") to mention the length-of-stay discount, tax gross-up and €5
  rounding.
- [x] 6.3 Update `docs/business-doc.mdc`'s note that `admin/sconti-soggiorno` has
  "no editable discount rules" — clarify that weekly/monthly discount percentages
  are now configured from the Settings page (this page itself still only shows
  the formula documentation).

## 7. Tests

- [x] 7.1 Add unit tests for `PricingQuoteService::calculate()` covering: default
  tax rate applied to cleaning+linen, tax rate 0% no-op, item excluded from
  gross-up, €5 rounding up and down, breakdown reconciliation
  (`stay + cleaning + linen == total`), weekly discount at 7 nights, monthly
  discount at 28 nights (and that it replaces rather than stacks with the weekly
  discount), no discount below 7 nights, and cleaning/linen amounts unaffected by
  the discount.
- [x] 7.2 Add unit tests for `OtaPortalPricingService::suggest()` covering the 3
  default commission rates against known inputs (matching the worked examples in
  `design.md`), including a case where the simulated stay qualifies for the
  weekly/monthly discount.
- [x] 7.3 Update `BookingLegalConsentTest` and `BookingRequestConfirmationTest`
  (and any other test asserting exact `PricingQuoteService` output or
  `Booking.income_amount`) with the new, discount- and tax-aware expected amounts.
- [x] 7.4 Add a feature test for `Admin\SettingsController::updatePricing()`
  (persists values, validation errors) and for `Admin\PricingController::simulate()`
  asserting the new `tax_gross_up_cents`/`stay_discount_cents`/`portals` response
  fields.

## 8. Verification

- [x] 8.1 Run the full test suite (`php artisan test` / project's configured
  command) and confirm all pricing-related tests pass with updated expectations.
- [x] 8.2 Manually verify in the admin pricing simulator that the owner's worked
  example (500 € stay, 200 € cleaning+linen, 21% tax → +42 €, rounded to nearest
  €5) reproduces correctly end to end.
- [x] 8.3 Manually verify a 7-night and a 28-night simulation show the correct
  weekly/monthly discount applied (and only one of the two, never both), and that
  the resulting portal prices net the same amount after the portal's assumed
  commission and length discount.
