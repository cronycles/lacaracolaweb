## Why

`tax-gross-up-pricing` added a portal price *simulator* (one suggested price per
simulated date range, shown in `admin/prezzi`), but the owner sets prices on
Airbnb/Booking.com/HomeToGo the same way as on the direct site: one base
nightly rate per calendar period, tweaked on the portal's own calendar — not
one date range at a time. Simulating a single stay is impractical for
populating a whole year of portal pricing.

Separately, the owner does not want to expose a portal's native "cleaning fee"
field: cleaning + linen must be blended invisibly into the nightly rate, like
today's direct-site price. This means the portal nightly rate must always
recover enough fixed cost to cover the worst case — the apartment's maximum
occupancy — otherwise a large group could end up paying less through a portal
than through the direct site, which the owner explicitly wants to never
happen.

## What Changes

- Add a new read-only admin page, "Prezzi portali" (`admin/prezzi-portali`),
  listing the **same periods as the existing `pricing_rules` table** (one row
  per rule, no separate data entry), with one computed column per portal
  (Airbnb, Booking.com, HomeToGo) showing the suggested blended nightly rate
  to type into that portal's calendar for that period.
- New `App\Services\OtaPortalNightlyRateService` computing, for a given
  `price_per_night` (from a `PricingRule`), a blended nightly rate per portal:
  - Reference stay length and reference guest count are **editable from
    Settings** (new `pricing_portal_reference_nights` /
    `pricing_portal_reference_guests` keys, same pattern as the other
    `pricing_*` settings), defaulting to `config('apartment.booking.min_nights')`
    (currently 3) and `config('apartment.specs.beds')` (currently 6) — i.e. the
    apartment's real minimum stay and maximum occupancy — so the amounts stay
    editable without a deploy if the owner ever needs to tune them, without
    losing the sensible built-in defaults.
  - At the default reference guest count (bed capacity), cleaning + linen
    (grossed up for tax, same as the direct site) is always fully recovered
    regardless of how many guests actually book via the portal, guaranteeing
    the portal rate is never lower than what the same booking would cost
    directly. Lowering this setting below bed capacity reopens that risk (see
    design.md) — the Settings UI documents this trade-off.
  - Same tax gross-up + portal commission formula as `OtaPortalPricingService`,
    reused/shared rather than duplicated, now also applying the same
    weekly/monthly length-discount lookup to the reference stay (needed for
    correctness now that the reference nights count is no longer guaranteed to
    stay below the discount threshold).
  - Final suggested nightly rate rounded to the nearest whole euro (not €5 like
    the guest-facing total) so Airbnb and HomeToGo — which share the same
    default commission — land on the same or very close numbers, and
    Booking.com only differs by its own slightly higher commission.

## Capabilities

### New Capabilities
- `ota-portal-price-table`: a per-pricing-period table of suggested blended
  nightly rates for Airbnb/Booking.com/HomeToGo, computed from the direct
  site's `pricing_rules`, with a settings-editable reference stay length and
  guest count (defaulting to the apartment's minimum stay and maximum
  occupancy) to guarantee the portal rate is never cheaper than the
  equivalent direct booking at default settings.

### Modified Capabilities
_None — the `ota-portal-pricing` capability from `tax-gross-up-pricing` has not
been archived into `openspec/specs/` yet, so there is no existing delta to
apply; this change only adds a new, separate capability._

## Impact

- `app/Services/OtaPortalNightlyRateService.php` — new service (shares the
  weekly/monthly discount trait and commission/tax-rate settings already
  introduced by `tax-gross-up-pricing`, applying the discount to the
  reference stay since reference nights is now settings-editable).
- `app/Http/Controllers/Admin/PricingController.php` — new `portalPrices()`
  read-only action listing `PricingRule`s with computed portal columns.
- `resources/views/admin/pricing/portal-prices.blade.php` (new view).
- `routes/admin.php` — new `GET admin/prezzi-portali` route under the existing
  `manage_pricing` permission group.
- No database changes — reuses existing `pricing_rules` table and the
  `pricing_commission_*`/`pricing_tax_rate`/`pricing_tax_gross_up_items`
  `Setting` keys added by `tax-gross-up-pricing`, plus 2 new keys
  (`pricing_portal_reference_nights`, `pricing_portal_reference_guests`).
- `app/Http/Controllers/Admin/SettingsController.php` +
  `resources/views/admin/settings.blade.php` — 2 new fields in the existing
  "Fiscalità e prezzi" card.
- Tests: new unit tests for `OtaPortalNightlyRateService`, feature test for the
  new admin route/permission and for persisting the 2 new settings.
