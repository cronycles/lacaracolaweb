## Why

`ota-portal-price-table` (archived `2026-09-04`) amortised the apartment's
**maximum** occupancy (6 guests' linen) and the fixed 100 € cleaning fee over
the **minimum** stay (3 nights), to guarantee a portal listing could never be
cheaper than the direct site for any real booking. In practice this produces
a blended nightly rate roughly 2–2.5× the direct nightly rate — far above
what comparable listings charge on Airbnb/Booking.com/HomeToGo in the same
area, making the apartment's portal listings look overpriced and hurting
visibility/conversion there.

The owner has since confirmed how each portal actually lets a host price a
listing: a fixed cleaning fee **field** (separate from the nightly rate,
100 € on all 3 portals) plus an optional flat **extra-guest-per-night**
surcharge starting from a host-chosen guest number — a per-guest pricing tier
mechanism, not a single blended number. None of the 3 portals let the owner
charge biancheria (linen) separately per guest in a way the owner wants to
keep enabled (HomeToGo technically allows it; the owner wants all 3 portals
configured identically, so it stays unused everywhere). This means the
nightly rate no longer has to defensively cover the _worst-case_ guest count
— the portal's own extra-guest surcharge field can recover the incremental
linen cost for guests beyond a small reference count, the same way it
recovers it for any other per-guest cost.

## What Changes

- **Strip the cleaning fee amount out of the blended nightly rate entirely.**
  The 100 € `pricing_cleaning_fee` value is never added to any per-night
  figure; the owner types it directly into each portal's own "cleaning fee"
  field, exactly as today, with no computation involved. Its tax gross-up
  (only the tax, not the fee itself) is still recovered by the per-night
  add-on below, alongside the linen recovery — otherwise a 2-guest,
  minimum-stay portal booking would net noticeably less than direct (see
  design.md Decision 1).
- **Lower the nightly-rate reference guest count from bed capacity (6) to a
  fixed 2 guests** (no longer a `Setting` — hardcoded, since the previous
  editable reference-guests knob is superseded by the extra-guest surcharge
  mechanism below). Only the linen cost for 2 guests (tax-grossed-up) is
  amortised into the nightly rate now, over the reference stay length.
- **Reference stay length is no longer a separate, portal-specific `Setting`.**
  It is simply the apartment's minimum stay length, which becomes itself
  `Setting`-editable for the first time (`pricing_min_nights`, replacing the
  hardcoded `config('apartment.booking.min_nights')` used site-wide), so
  there is exactly one number for "minimum/reference nights", not two that
  could drift apart.
- **New "extra guest per night" surcharge**, one flat `Setting`-editable euro
  amount (`pricing_extra_guest_fee`, default `12`) shared identically across
  all 3 portals (matching the owner's wish to keep all 3 portals configured
  the same way), to be typed into each portal's own per-extra-guest pricing
  field, applicable from the 3rd guest onward.
- **Promote `cleaning_fee` and `linen_fee_per_person` from
  `config/apartment.php` to `Setting`s** (`pricing_cleaning_fee`,
  `pricing_linen_fee_per_person`), since they now directly drive an
  admin-configurable formula and the owner wants every calculation input that
  can change to live in `admin/impostazioni`, not in a file requiring a
  deploy. `config/apartment.php` values become the fallback defaults (no
  behavior change until an admin edits the new fields).
- **Remove `pricing_portal_reference_nights` and
  `pricing_portal_reference_guests`** — both are superseded (reference nights
  merges into `pricing_min_nights`; reference guests becomes the fixed
  business constant `2`) and would otherwise sit unused in Settings.
- **Unify `OtaPortalPricingService` and `OtaPortalNightlyRateService` into a
  single service** (`OtaPortalPricingService`) used by both `admin/prezzi`
  (simulator) and `admin/prezzi-portali` (period table), so the two pages can
  never drift onto two different formulas again.
- **`admin/prezzi-portali` gains a legend** explaining what each number means,
  what to type into each portal (with per-portal notes, e.g. HomeToGo's
  unused per-guest-linen option), and a link to the relevant Settings fields.
- **`admin/prezzi` simulator's portal cards now show the real total a guest
  would pay via each portal** for the simulated stay/guest count (nightly
  rate × nights, discounted the same as the direct site for 7+/28+ nights,
  plus the extra-guest surcharge and the flat cleaning fee), plus the owner's
  approximate net-of-commission take, with a visual indicator comparing it to
  the direct-site total for the same simulated stay.

## Capabilities

### Modified Capabilities

- `ota-portal-price-table`: replaces the max-occupancy/min-stay blended
  formula with the 2-guest reference + extra-guest-surcharge model; removes
  the 2 reference `Setting`s; adds the legend requirement.
- `ota-portal-pricing`: replaces the simulator's "gross up the whole direct
  total by discount+commission" formula with the same unified nightly-rate +
  extra-guest-surcharge model, extended to compute a real guest-facing total
  and an owner-net-vs-direct comparison for the simulated stay.
- `tax-gross-up-pricing`: adds "configurable fixed accessory costs" — cleaning
  fee and linen fee per person become `Setting`-backed (previously
  `config/apartment.php`-only), with the config values as fallback defaults.

## Impact

- `app/Services/OtaPortalPricingService.php` — rewritten: new
  `baseNightlyRateCents()` (per-portal, 2-guest reference), new
  `extraGuestFeeCents()` (shared across portals), new
  `guestFacingTotal()`/owner-net helper used by the simulator; keeps the
  `ResolvesLengthDiscountRate`/`ResolvesTaxGrossUp` traits.
- `app/Services/OtaPortalNightlyRateService.php` — deleted, merged into the
  above.
- `app/Services/PricingQuoteService.php` — reads `cleaning_fee` /
  `linen_fee_per_person` / `min_nights` from `Setting` (fallback to
  `config/apartment.php`) instead of `config()` directly.
- `app/Http/Controllers/Admin/PricingController.php` — `portalPrices()` and
  `simulate()` both call the unified service; `simulate()` response gains the
  guest-facing portal total/owner-net/margin-safe fields.
- `app/Http/Controllers/Admin/SettingsController.php` — `updatePricing()` /
  `pricingSettings()`: remove the 2 reference fields, add `pricing_cleaning_fee`,
  `pricing_linen_fee_per_person`, `pricing_min_nights`, `pricing_extra_guest_fee`.
- `app/Http/Controllers/Public/BookingController.php` — reads `min_nights` via
  the new `Setting`-backed resolution instead of `config()` directly.
- `resources/views/admin/settings.blade.php` — replace the "Riferimento
  tabella prezzi portali" field group with "Costi fissi per soggiorno" (pulizie,
  biancheria), "Soggiorno minimo" (notti minime) and "Supplemento ospite
  extra" (portali) field groups.
- `resources/views/admin/pricing/portal-prices.blade.php` — add the legend;
  table columns unchanged (3 portal base-rate columns), formula behind them
  changes.
- `resources/views/admin/pricing/index.blade.php` +
  `resources/ts/components/pricing-simulator.ts` — portal cards show the
  guest-facing total, owner net, and a ✅/⚠️ margin indicator instead of just
  total/avg-per-night.
- `resources/views/components/booking-form.blade.php`,
  `resources/views/admin/stay-discounts/index.blade.php` — read `min_nights`
  via the new `Setting`-backed resolution.
- No database migration — reuses the existing `settings` key-value table.
- Tests: rewrite `OtaPortalNightlyRateServiceTest`/`OtaPortalPricingServiceTest`
  into a single `OtaPortalPricingServiceTest`; update
  `PricingQuoteServiceTest`/`BookingLegalConsentTest` fixtures if they assert
  on `config()` values directly; new feature test coverage for the 4
  Settings changes.
