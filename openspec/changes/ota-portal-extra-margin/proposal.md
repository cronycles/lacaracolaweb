## Why

`ota-portal-guest-tiered-pricing` (archived `2026-09-04`) deliberately shrank
the portal-vs-direct gap at the 2-guest reference count, to fix a nightly
rate that was previously 2–2.5× the direct rate and looked off-market. That
fix worked, but the owner has since observed that for the very common
2-guest booking, the guest-facing portal total and the direct-site total now
land within a few cents/euros of each other — because the only
guest-count-dependent lever (`pricing_extra_guest_fee`) only applies from the
3rd guest onward, and every other input that drives the gap
(`pricing_tax_rate`, `pricing_commission_*`, `pricing_cleaning_fee`,
`pricing_linen_fee_per_person`, `pricing_min_nights`) is a real fiscal/cost
figure the owner does not want to distort just to create separation.

There is currently no lever whose sole purpose is "make portal prices a bit
more expensive than direct, by an amount I choose", independent of guest
count and independent of the real commission/tax/fee data.

## What Changes

- **New `pricing_portal_extra_margin_percent` setting** (percentage, default
  `5%`), added to the existing "Fiscalità e prezzi" settings card (not a
  separate section — it is still a portal-pricing figure, just editable
  alongside the fields it complements).
- **Applied as a final multiplier on the commission-grossed portion only**
  of both portal pricing computations (`OtaPortalPricingService::baseNightlyRateCents()`
  and `::guestFacingTotal()`), i.e. the same place the `1 / (1 - commission)`
  division already happens. It does **not** touch the flat, literally-typed
  pass-through fields (`pricing_cleaning_fee`, `pricing_extra_guest_fee`),
  consistent with the existing design principle (see
  `ota-portal-guest-tiered-pricing`'s design.md, Decision 1) that those two
  fields are never algebraically blended into a computed figure.
- Because it multiplies the same grossed portion the commission divisor
  already applies to, the margin now widens the portal/direct gap uniformly
  **at every guest count**, including exactly 2 (or 1) guests where the
  extra-guest surcharge cannot help.
- No change to the direct-site price (`PricingQuoteService`) or to any
  existing fiscal/commission/discount figure — this is purely an additive
  markup applied only inside the OTA portal suggestion formulas.

## Capabilities

### Modified Capabilities

- `ota-portal-pricing`: `guestFacingTotal()`'s base-stay computation gains the
  extra-margin multiplier; the admin pricing settings page gains the new
  field.
- `ota-portal-price-table`: `baseNightlyRateCents()` gains the same
  extra-margin multiplier, so the period table's suggested nightly rates
  reflect it too.

## Impact

- `app/Services/OtaPortalPricingService.php` — new private `extraMarginRate(): float`
  helper; `baseNightlyRateCents()` and `guestFacingTotal()` each multiply
  their commission-grossed subtotal by `(1 + extraMarginRate())` before
  rounding/discounting.
- `app/Http/Controllers/Admin/SettingsController.php` — add
  `pricing_portal_extra_margin_percent` to `PRICING_SETTING_DEFAULTS`,
  `updatePricing()` validation/persistence, and `pricingSettings()`.
- `resources/views/admin/settings.blade.php` — new field in the existing
  "Fiscalità e prezzi" card.
- `tests/Unit/OtaPortalPricingServiceTest.php` — update worked-example
  assertions for the new default 5% margin; add coverage for a non-default
  margin value and for the 0% (disabled) case.
- `docs/specific-tech-backend-doc.mdc` — update the `OtaPortalPricingService`
  summary to mention the extra-margin multiplier.
