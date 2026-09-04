## 1. Service

- [x] 1.1 Extract the shared "taxable cents → tax gross-up cents" computation
  (currently inline in `PricingQuoteService::calculate()`) into a small reusable
  helper (e.g. a method on `ResolvesLengthDiscountRate`'s sibling trait, or a
  new `Concerns/ResolvesTaxGrossUp` trait) taking cleaning/linen cents and the
  selected gross-up items, returning `taxGrossUpCents`. Update
  `PricingQuoteService` to use it, with no behavior change (verify existing
  `PricingQuoteServiceTest` still passes unmodified).
- [x] 1.2 Add `pricing_portal_reference_nights` and
  `pricing_portal_reference_guests` to the pricing `Setting` keys, defaulting
  to `config('apartment.booking.min_nights')` and
  `config('apartment.specs.beds')` respectively when unset.
- [x] 1.3 Create `app/Services/OtaPortalNightlyRateService.php` with a method
  `ratesFor(PricingRule $rule): array` returning, for each of
  `airbnb`/`booking`/`hometogo`: the suggested nightly rate in cents.
- [x] 1.4 Implement the formula: read `referenceNights`/`referenceGuests` from
  the 2 new settings (with their config-based defaults); compute
  `stayGrossCents`, apply the same weekly/monthly length-discount tiered
  lookup (`ResolvesLengthDiscountRate`) to `referenceNights`, then
  `cleaningCents`, `linenCents` (using `referenceGuests`), `taxGrossUpCents`
  (via the shared helper from 1.1), sum into a reference total (no €5
  rounding), then per portal: `round(referenceTotal / (1 - commissionRate))`,
  divide by `referenceNights`, round to the nearest 100 cents.

## 2. Settings persistence

- [x] 2.1 Extend `SettingsController::updatePricing()` validation to accept
  `pricing_portal_reference_nights` (integer, min 1) and
  `pricing_portal_reference_guests` (integer, min 1), persisting via
  `Setting::set()`.
- [x] 2.2 Extend `SettingsController::pricingSettings()` (or equivalent view
  data helper) to expose the current/default values of both new settings.
- [x] 2.3 Add the 2 new fields to the "Fiscalità e prezzi" card in
  `resources/views/admin/settings.blade.php`, with help text noting: (a)
  changing the reference nights affects whether the length discount applies
  to this table's calculation, and (b) setting reference guests below the
  apartment's bed capacity may allow a portal price to undercut an equivalent
  direct booking with more guests.

## 3. Admin controller & route

- [x] 3.1 Add a `portalPrices(): View` action to `Admin\PricingController`
  fetching all `PricingRule`s (same ordering as `index()`) and, for each,
  the output of `OtaPortalNightlyRateService::ratesFor()`.
- [x] 3.2 Add route `GET admin/prezzi-portali` → `admin.pricing.portal-prices`
  under the existing `permission:manage_pricing` middleware group in
  `routes/admin.php`.

## 4. Admin UI

- [x] 4.1 Create `resources/views/admin/pricing/portal-prices.blade.php`: a
  read-only table with columns Periodo / Il tuo prezzo (price_per_night) /
  Airbnb / Booking.com / HomeToGo, one row per `PricingRule`, formatted in
  euros.
- [x] 4.2 Add a link to this new page from `admin/prezzi/index.blade.php`
  (next to the existing "Formula prezzi" link) and from the "Formula prezzi"
  page, so it's discoverable from both.

## 5. Tests

- [x] 5.1 Add unit tests for `OtaPortalNightlyRateService::ratesFor()`
  covering: the worked example from design.md at default settings (100€/night
  → confirm the 3 portal nightly rates), the "never cheaper than direct"
  guarantee at default settings — assert, for a range of guest counts from 1
  to bed capacity, that `portalTotal(referenceNights) >=
  PricingQuoteService::calculate()`'s `total_cents` for that same guest count
  and reference nights — and that changing `pricing_portal_reference_nights`
  above the weekly/monthly thresholds correctly applies the length discount to
  the reference total.
- [x] 5.2 Add a feature test for the new `admin/prezzi-portali` route:
  accessible with `manage_pricing`, redirected without it, and lists the
  expected number of rows/columns for a seeded set of `PricingRule`s.
- [x] 5.3 Add a feature test for `SettingsController::updatePricing()`
  persisting the 2 new reference settings and validation errors for
  non-positive values.

## 6. Docs

- [x] 6.1 Update `docs/specific-tech-backend-doc.mdc`'s `PricingQuoteService`
  / `OtaPortalPricingService` note to mention the new
  `OtaPortalNightlyRateService` and the 2 new `pricing_portal_reference_*`
  settings.
- [x] 6.2 Update `docs/business-doc.mdc` admin flows section to mention the
  new "Prezzi portali" page alongside the existing pricing simulator note.

## 7. Verification

- [x] 7.1 Run the full test suite and confirm all tests pass.
- [x] 7.2 Manually check `admin/prezzi-portali` shows sensible, close-to-equal
  Airbnb/HomeToGo rates and a slightly higher Booking.com rate for at least one
  real pricing period.
