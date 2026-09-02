## 1. Service

- [ ] 1.1 Extract the shared "taxable cents → tax gross-up cents" computation
  (currently inline in `PricingQuoteService::calculate()`) into a small reusable
  helper (e.g. a method on `ResolvesLengthDiscountRate`'s sibling trait, or a
  new `Concerns/ResolvesTaxGrossUp` trait) taking cleaning/linen cents and the
  selected gross-up items, returning `taxGrossUpCents`. Update
  `PricingQuoteService` to use it, with no behavior change (verify existing
  `PricingQuoteServiceTest` still passes unmodified).
- [ ] 1.2 Create `app/Services/OtaPortalNightlyRateService.php` with a method
  `ratesFor(PricingRule $rule): array` returning, for each of
  `airbnb`/`booking`/`hometogo`: the suggested nightly rate in cents.
- [ ] 1.3 Implement the formula: reference nights =
  `config('apartment.booking.min_nights')`, reference guests =
  `config('apartment.specs.beds')`; compute `stayGrossCents`, `cleaningCents`,
  `linenCents` (using reference guests), `taxGrossUpCents` (via the shared
  helper from 1.1), sum into a reference total (no length discount, no €5
  rounding), then per portal: `round(referenceTotal / (1 - commissionRate))`,
  divide by reference nights, round to the nearest 100 cents.

## 2. Admin controller & route

- [ ] 2.1 Add a `portalPrices(): View` action to `Admin\PricingController`
  (or a new `Admin\OtaPortalPriceController` if preferred for separation)
  fetching all `PricingRule`s (same ordering as `index()`) and, for each,
  the output of `OtaPortalNightlyRateService::ratesFor()`.
- [ ] 2.2 Add route `GET admin/prezzi-portali` → `admin.pricing.portal-prices`
  under the existing `permission:manage_pricing` middleware group in
  `routes/admin.php`.

## 3. Admin UI

- [ ] 3.1 Create `resources/views/admin/pricing/portal-prices.blade.php`: a
  read-only table with columns Periodo / Il tuo prezzo (price_per_night) /
  Airbnb / Booking.com / HomeToGo, one row per `PricingRule`, formatted in
  euros.
- [ ] 3.2 Add a link to this new page from `admin/prezzi/index.blade.php`
  (next to the existing "Formula prezzi" link) and from the "Formula prezzi"
  page, so it's discoverable from both.

## 4. Tests

- [ ] 4.1 Add unit tests for `OtaPortalNightlyRateService::ratesFor()`
  covering: the worked example from design.md (100€/night defaults →
  confirm the 3 portal nightly rates), and the "never cheaper than direct"
  guarantee — assert, for a range of guest counts from 1 to bed capacity, that
  `portalTotal(referenceNights) >= PricingQuoteService::calculate()`'s
  `total_cents` for that same guest count and reference nights.
- [ ] 4.2 Add a feature test for the new `admin/prezzi-portali` route:
  accessible with `manage_pricing`, redirected without it, and lists the
  expected number of rows/columns for a seeded set of `PricingRule`s.

## 5. Docs

- [ ] 5.1 Update `docs/specific-tech-backend-doc.mdc`'s `PricingQuoteService`
  / `OtaPortalPricingService` note to mention the new
  `OtaPortalNightlyRateService` and its distinct reference-nights/reference-
  guests model.
- [ ] 5.2 Update `docs/business-doc.mdc` admin flows section to mention the
  new "Prezzi portali" page alongside the existing pricing simulator note.

## 6. Verification

- [ ] 6.1 Run the full test suite and confirm all tests pass.
- [ ] 6.2 Manually check `admin/prezzi-portali` shows sensible, close-to-equal
  Airbnb/HomeToGo rates and a slightly higher Booking.com rate for at least one
  real pricing period.
