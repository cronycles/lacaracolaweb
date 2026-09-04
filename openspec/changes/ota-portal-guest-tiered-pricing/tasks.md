## 1. Settings: new/removed keys

- [x] 1.1 Add `pricing_cleaning_fee`, `pricing_linen_fee_per_person`,
      `pricing_min_nights`, `pricing_extra_guest_fee` to
      `SettingsController::PRICING_SETTING_DEFAULTS` is not applicable (these 3
      default from `config/apartment.php`, not a fixed literal, like the removed
      reference settings did) — instead resolve each with
      `Setting::get('pricing_X', (string) config('apartment.booking.X', <default>))`,
      except `pricing_extra_guest_fee` which defaults to the fixed literal `'12'`.
- [x] 1.2 Remove `pricing_portal_reference_nights` and
      `pricing_portal_reference_guests` from `updatePricing()` validation,
      persistence, and `pricingSettings()`.
- [x] 1.3 Add validation + persistence in `updatePricing()` for the 4 new
      keys: `pricing_cleaning_fee` (integer, min 0), `pricing_linen_fee_per_person`
      (integer, min 0), `pricing_min_nights` (integer, min 1, max
      `config('apartment.booking.max_nights') - 1`), `pricing_extra_guest_fee`
      (integer, min 0).
- [x] 1.4 Extend `pricingSettings()` to expose the 4 new current/default
      values (drop the 2 removed ones) for the Settings view.

## 2. Settings UI

- [x] 2.1 In `resources/views/admin/settings.blade.php`, replace the
      "Riferimento tabella prezzi portali" field group with 3 groups in the same
      "Fiscalità e prezzi" card: "Costi fissi per soggiorno" (pulizie, biancheria),
      "Soggiorno minimo" (notti minime, with a help note that it also changes the
      direct-site minimum bookable stay), "Supplemento ospite extra (portali)"
      (with a help note: applies identically to all 3 portals, dal 3° ospite,
      link to `admin/prezzi-portali`).

## 3. Direct-site pricing: config → Setting migration

- [x] 3.1 `PricingQuoteService::calculate()`: read `cleaning_fee`,
      `linen_fee_per_person`, `min_nights` via `Setting::get('pricing_X', config(...))`
      instead of `config()` directly. `max_nights` stays `config`-only.
- [x] 3.2 `Admin\PricingController::simulate()` and
      `Public\BookingController` (availability + quote actions): same
      `min_nights` resolution change.
- [x] 3.3 `resources/views/components/booking-form.blade.php`,
      `resources/views/admin/pricing/index.blade.php` (simulator date picker),
      `resources/views/admin/stay-discounts/index.blade.php`: read `min_nights`
      (and, on the stay-discounts formula-explainer page, `cleaning_fee`/
      `linen_fee_per_person`) via the same `Setting`-backed resolution.
- [x] 3.4 `resources/views/admin/bookings/form.blade.php`: cleaning/linen
      placeholder values switch to the `Setting`-backed values (still only a
      placeholder/default; per-booking override behavior unchanged).

## 4. Unified `OtaPortalPricingService`

- [x] 4.1 Delete `app/Services/OtaPortalNightlyRateService.php`.
- [x] 4.2 Rewrite `app/Services/OtaPortalPricingService.php`:
    - `baseNightlyRateCents(int $pricePerNightCents, string $portal): int` —
      2-guest reference linen recovery + tax gross-up, amortised over
      `pricing_min_nights`, divided by `(1 - commission)`, rounded to the
      nearest whole euro (see design.md Decision 4).
    - `extraGuestFeeCents(): int` — reads `pricing_extra_guest_fee` (euros → cents).
    - `cleaningFeeCents(): int` — reads `pricing_cleaning_fee` (euros → cents).
    - `commissionRate(string $portal): float` — unchanged (kept from the
      existing service).
    - `guestFacingTotal(int $stayGrossCents, int $nights, int $guests, string $portal): array{guest_total_cents:int, owner_net_cents:int, commission_rate:float, margin_safe_hint:bool}` —
      the simulator formula from design.md Decision 8 (`marginSafe` computed
      against a `$directTotalCents` argument passed in by the caller).
    - Remove the 2-argument `resolveReferenceNights()`/`resolveReferenceGuests()`
      methods; keep `ResolvesLengthDiscountRate` (now used only by
      `guestFacingTotal()`) and `ResolvesTaxGrossUp`.
- [x] 4.3 Update `Admin\PricingController::portalPrices()` to call
      `baseNightlyRateCents()` per rule × portal (replacing
      `OtaPortalNightlyRateService::ratesFor()`).
- [x] 4.4 Update `Admin\PricingController::simulate()` to call
      `guestFacingTotal()` per portal (replacing `OtaPortalPricingService::suggest()`),
      passing `$quote['total_cents']` as the direct total for the margin check.

## 5. Admin UI: portal price table legend

- [x] 5.1 Add a legend block to
      `resources/views/admin/pricing/portal-prices.blade.php` above the table:
      fixed cleaning fee, extra-guest surcharge + "dal 3° ospite" rule, per-portal
      notes (Airbnb/Booking.com: no per-guest linen field; HomeToGo: has one,
      deliberately unused), link to `admin/impostazioni`.
- [x] 5.2 In the same legend block, add a note documenting the accepted
      cleaning-fee commission trade-off (design.md Risks): the 100 €
      cleaning fee is typed in flat/un-grossed, so each portal keeps
      roughly `cleaning_fee × commission_rate` (≈15–16 € at today's rates)
      of it instead of passing it through — written down so the owner can
      revisit this decision later without re-deriving it from scratch.

## 6. Admin UI: simulator portal cards

- [x] 6.1 Update `resources/ts/components/pricing-simulator.ts`: rename/extend
      the `PortalSuggestion` interface to
      `{ guest_total_cents, owner_net_cents, base_nightly_rate_cents, commission_rate, margin_safe }`;
      render guest total, owner net, and a ✅/⚠️ indicator per portal card.
- [x] 6.2 Rebuild frontend assets (`npm run build`) so
      `public/build/assets/*` reflect the updated TS.

## 7. Tests

- [x] 7.1 Replace `tests/Unit/OtaPortalNightlyRateServiceTest.php` and
      `tests/Unit/OtaPortalPricingServiceTest.php` with a single
      `tests/Unit/OtaPortalPricingServiceTest.php` covering: the worked example
      from design.md Decision 4 (100 €/night → 3 portal base rates), the
      `guestFacingTotal()` formula from Decision 8 for a few guest/night
      combinations, and that changing `pricing_min_nights` above the
      weekly/monthly thresholds correctly applies the length discount inside
      `guestFacingTotal()` only (not `baseNightlyRateCents()`).
- [x] 7.2 Update `tests/Unit/PricingQuoteServiceTest.php` (or add cases) to
      confirm `cleaning_fee`/`linen_fee_per_person`/`min_nights` `Setting`
      overrides are honored, and unset `Setting`s fall back to `config()`
      defaults unchanged from today.
- [x] 7.3 Update `tests/Feature/BookingLegalConsentTest.php` (and any other
      test reading `config('apartment.booking.cleaning_fee'/'linen_fee_per_person')`
      directly) if the `Setting`-backed default no longer matches — expected to
      still pass unchanged since fallback defaults are identical.
- [x] 7.4 Add/update the `admin/prezzi-portali` and
      `SettingsController::updatePricing()` feature tests for the new field set
      (remove assertions on the 2 deleted settings, add assertions for the 4 new
      ones).

## 8. Docs

- [x] 8.1 Update `docs/specific-tech-backend-doc.mdc`: rewrite the
      `OtaPortalPricingService`/`OtaPortalNightlyRateService` note into one
      paragraph for the unified service; update the "`settings` DB only for" /
      "Do not use `settings` for" lists (move `cleaning_fee`, `linen_fee_per_person`,
      `min_nights` from the "do not" list to the "settings DB" list; remove the 2
      deleted keys).
- [x] 8.2 Update `docs/business-doc.mdc`'s `admin/prezzi-portali` /
      `admin/prezzi` description to match the new formula and the legend.

## 9. Verification

- [x] 9.1 Run the full test suite and confirm all tests pass.
- [x] 9.2 Manually check `admin/prezzi-portali` shows a nightly rate close to
      the direct `price_per_night` (not inflated by bed-capacity amortisation)
      for at least one real pricing period, and that the legend renders correctly.
- [x] 9.3 Manually check the `admin/prezzi` simulator for a 2-guest/3-night
      stay, a 6-guest/3-night stay, and a 2-guest/10-night stay, confirming the
      guest total, owner net, and margin indicator look sensible in each case.
