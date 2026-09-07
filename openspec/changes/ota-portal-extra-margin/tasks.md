## 1. Settings: new key

- [ ] 1.1 Add `pricing_portal_extra_margin_percent` (default `'0.05'`) to
      `SettingsController::PRICING_SETTING_DEFAULTS`.
- [ ] 1.2 Add validation (`numeric, min:0, max:100`) and persistence
      (`/ 100` before `Setting::set`, same pattern as `pricing_commission_*`)
      in `updatePricing()`.
- [ ] 1.3 Expose the current/default value from `pricingSettings()` as
      `portal_extra_margin_percent`.

## 2. Settings UI

- [ ] 2.1 In `resources/views/admin/settings.blade.php`, add a new field to
      the existing "Fiscalità e prezzi" card (near "Commissioni portali" or
      "Supplemento ospite extra"): "Margine extra prezzi portali (%)", with a
      help note clarifying it's a commercial markup layered on top of the
      commission-based calculation (not a fiscal figure), applied to both the
      period table's nightly rate and the simulator's total, and that it does
      not affect the flat cleaning-fee/extra-guest-fee figures typed into the
      portals.

## 3. `OtaPortalPricingService` formula

- [ ] 3.1 Add a private `extraMarginRate(): float` helper reading
      `Setting::get('pricing_portal_extra_margin_percent', '0.05')`.
- [ ] 3.2 `baseNightlyRateCents()`: multiply the commission-grossed rate by
      `(1 + extraMarginRate())` before the nearest-euro rounding.
- [ ] 3.3 `guestFacingTotal()`: multiply `baseStayGrossedCents` by
      `(1 + extraMarginRate())` before the length-discount rounding.
      `extraGuestCents` and `cleaningFeeCents` stay untouched (flat
      pass-through, per design.md Non-Goals).

## 4. Tests

- [ ] 4.1 Update `tests/Unit/OtaPortalPricingServiceTest.php`'s existing
      worked-example assertions for the new default 5% margin.
- [ ] 4.2 Add a test setting `pricing_portal_extra_margin_percent` to a
      non-default value (e.g. `10%`) and asserting both `baseNightlyRateCents()`
      and `guestFacingTotal()` reflect it.
- [ ] 4.3 Add a test setting the margin to `0` and asserting the result
      matches the pre-change formula exactly (no regression when disabled).

## 5. Docs

- [ ] 5.1 Update the `OtaPortalPricingService` summary in
      `docs/specific-tech-backend-doc.mdc` to mention the extra-margin
      multiplier and where it applies.
