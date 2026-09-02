## Context

`tax-gross-up-pricing` introduced `OtaPortalPricingService::suggest(int $totalCents, int $nights)`,
used only by the admin pricing simulator (`admin/prezzi`) to suggest a portal
price for one simulated stay. It divides the (tax-grossed-up, length-discounted)
direct total by `(1 - lengthDiscountRate) * (1 - commissionRate)`.

The owner configures portal prices differently in practice: each portal
(Airbnb, Booking.com, HomeToGo) exposes a calendar where you set one **base
nightly rate per period**, exactly like the site's own `pricing_rules` table
(`admin/prezzi`), then fine-tune specific date ranges from the portal's own UI.
Simulating one date range at a time is not how the owner actually populates
those calendars — they need one nightly figure per existing pricing period,
computed automatically, refreshed once a year when they update `pricing_rules`.

The owner also explicitly rejected exposing a separate "cleaning fee" field on
portals (available on some of them): cleaning + linen must be invisibly folded
into the nightly rate, exactly like the direct site's `stay_cents` already
does. This creates the core technical constraint this design solves: a single
static nightly rate cannot literally track a real per-guest cost (linen scales
with guest count), so a fixed reference guest count must be chosen once, and it
must be conservative enough that the resulting rate can never end up cheaper
than what an equivalent-length direct booking would cost, for any real guest
count 1–6 (the apartment's bed capacity, `config('apartment.specs.beds')`).

## Goals / Non-Goals

**Goals:**
- Show, per existing `pricing_rules` period (no new/duplicate data entry), one
  suggested nightly rate per portal, blending cleaning + linen + tax gross-up
  into the rate exactly as the owner wants (no separate fee lines).
- Guarantee the suggested portal rate is never lower than what the same-length
  stay would cost via the direct site, for any guest count up to the
  apartment's bed capacity — a hard requirement confirmed by the owner, not a
  "usually true" heuristic.
- Make the table fast to use once a year: no manual per-period computation, no
  extra settings beyond what `tax-gross-up-pricing` already introduced.

**Non-Goals:**
- Do not expose a separate portal "cleaning fee" input/column — deliberately
  blended into the nightly rate per the owner's explicit choice.
- Do not change `OtaPortalPricingService` (per-simulated-stay suggestion) or
  the existing `admin/prezzi` simulator — this is a new, separate view.
- Do not add validation preventing the reference guest count from being set
  below bed capacity — the owner explicitly wants it tunable "non si sa mai";
  the Settings UI documents the trade-off instead of blocking it (see
  Decision 2 and Risks).

## Decisions

### 1. Reference stay length: new `pricing_portal_reference_nights` Setting, defaulting to `min_nights`
Rather than reading `config('apartment.booking.min_nights')` directly (a
value that would require a code deploy to change), this is a new `Setting` key
(same pattern as `pricing_tax_rate`, `pricing_weekly_discount_percent`, etc.
from `tax-gross-up-pricing`), **defaulting** to
`config('apartment.booking.min_nights')` (currently 3) when unset — so out of
the box it behaves exactly as reasoned through with the owner, but can be
retuned later from `admin/impostazioni` without a deploy ("non si sa mai").
The default value keeps the property that at 3 nights (below the 7-night
discount threshold) fixed costs weigh the most per night — the worst case for
the owner, biasing the computed rate safely upward for any longer, discounted
real booking. See Decision 3 for why changing this away from the default no
longer relies on staying below the discount threshold.

### 2. Reference guest count: new `pricing_portal_reference_guests` Setting, defaulting to bed capacity (6)
Same pattern as Decision 1 — a new `Setting` key defaulting to
`config('apartment.specs.beds')` (currently 6) rather than a hardcoded
constant, editable from `admin/impostazioni` without a deploy. Considered
guest counts 2 (the project's existing "typical occupancy" default, used e.g.
by the pricing simulator and booking form) and 5 (a compromise suggested by
the owner) as the *default* before settling on bed capacity. Worked example
with current defaults (cleaning 100€, linen 25€/guest, tax 21%, commissions
15.5–16.5%, 105€/night, 3-night reference):

| Reference guests | Portal total (3 nights) | Real direct total, 6 guests, 3 nights | Safe? |
|---|---|---|---|
| 2 | ≈588€ | ≈618€ | **No** — portal ≈30€ cheaper than an actual 6-guest direct booking |
| 5 | ≈695€ | ≈618€ | Yes, with today's tax/commission settings |
| 6 (bed capacity) | ≈731€ | ≈618€ | Yes, unconditionally — cannot happen by construction |

Guest count 2 is provably unsafe (linen for the missing guests is not grossed
up enough by commission alone to close the gap). Guest count 5 happens to be
safe under today's settings, but the margin is a function of the currently
configured tax rate and commission rates (both editable from Settings without
a deploy) — lowering commissions or tax in the future could shrink or remove
that margin. Bed capacity (6) is the only choice that is safe *by
construction*, independent of any current or future tax/commission setting,
because it structurally cannot admit more real guests than it was priced for.
The owner confirmed this trade-off (portal rates look a little higher for
typical 2–4 guest bookings) is acceptable in exchange for the hard guarantee —
and explicitly wants the number itself left editable (Decision 7), accepting
that lowering it below bed capacity reopens the risk this default eliminates.

### 3. Formula (reusing, not duplicating, `tax-gross-up-pricing`'s building blocks)
```
referenceNights = (int) Setting::get('pricing_portal_reference_nights', config('apartment.booking.min_nights'))
referenceGuests = (int) Setting::get('pricing_portal_reference_guests', config('apartment.specs.beds'))

stayGrossCents   = pricingRule.price_per_night * referenceNights
lengthDiscountRate = same tiered lookup as PricingQuoteService/OtaPortalPricingService,
                     evaluated for referenceNights                      // 0 by default (3 < 7)
stayDiscountCents  = round(stayGrossCents * lengthDiscountRate)
discountedStayCents = stayGrossCents - stayDiscountCents
cleaningCents    = config('apartment.booking.cleaning_fee') * 100
linenCents       = config('apartment.booking.linen_fee_per_person') * 100 * referenceGuests
taxableCents     = sum of cleaningCents/linenCents for items selected in
                   Setting('pricing_tax_gross_up_items')                // same as PricingQuoteService
taxGrossUpCents  = round(taxableCents * Setting('pricing_tax_rate'))

directTotalCents = discountedStayCents + taxGrossUpCents + cleaningCents + linenCents
                   // no €5 rounding here — this is an internal reference figure, not a guest-facing charge

portalTotalCents      = round(directTotalCents / (1 - commissionRate))   // per portal
portalNightlyRateCents = round(portalTotalCents / referenceNights)
finalNightlyRate       = round to nearest 100 cents (nearest whole euro)
```
`stayGrossCents`/`taxableCents`/`taxGrossUpCents` reuse the exact same
tax-gross-up logic already in `PricingQuoteService` — extracted into a small
shared helper (see Risks) rather than re-derived, to avoid the two services
drifting apart if the tax formula changes later. Unlike the original design,
the length-discount lookup (`ResolvesLengthDiscountRate`) **is** now applied to
the reference stay: since `referenceNights` is user-editable and no longer
guaranteed to stay below the 7-night threshold, applying the same tiered
lookup used elsewhere keeps the formula internally consistent for *any*
configured value, instead of silently producing a wrong number if the owner
ever raises the reference above 7 or 28 nights.

**Every tunable input in this formula is `Setting`-backed, not hardcoded**, so
the whole table stays in sync with whatever the owner has configured in
`admin/impostazioni` (all with `config/apartment.php`-sourced fallback
defaults, none requiring a deploy to change):
- `pricing_portal_reference_nights` / `pricing_portal_reference_guests` (new,
  this change — Decisions 1, 2, 7)
- `pricing_weekly_discount_percent` / `pricing_monthly_discount_percent` (from
  `tax-gross-up-pricing`, reused as-is via `ResolvesLengthDiscountRate`)
- `pricing_tax_rate` / `pricing_tax_gross_up_items` (from `tax-gross-up-pricing`)
- `pricing_commission_airbnb` / `pricing_commission_booking` /
  `pricing_commission_hometogo` (from `tax-gross-up-pricing`)

Only `cleaning_fee` and `linen_fee_per_person` remain sourced from
`config('apartment.php')` directly (unchanged, out of scope — `tax-gross-up-pricing`
deliberately kept those out of `Setting` per its own Non-Goals).

### 4. New service, not an extension of `OtaPortalPricingService`
`OtaPortalPricingService::suggest()` takes an already-computed `$totalCents`
for one real/simulated stay (guest count baked in by the caller). This new
`OtaPortalNightlyRateService` instead takes a raw `$pricePerNightCents` (one
`PricingRule` row) and internally fixes both reference nights and guests —
different inputs/purpose, so a separate, small service is clearer than
overloading the existing one with two calling conventions.

### 5. New dedicated read-only admin page, not new columns on `admin/prezzi`
The owner asked for "una tabella a parte" — a separate table, not a wider
version of the editable `pricing_rules` CRUD table. New route
`GET admin/prezzi-portali` (under the existing `manage_pricing` permission
group), listing every `PricingRule` (same ordering as `admin/prezzi`) with 3
extra read-only computed columns (Airbnb/Booking.com/HomeToGo). No new
Eloquent model or table — purely a computed view over existing `PricingRule`
rows.

### 6. Rounding: nearest whole euro, not nearest €5
The guest-facing direct total rounds to €5 (a customer-facing convenience,
`tax-gross-up-pricing` Decision 2). This table is an internal reference the
owner types into 3rd-party calendars, where finer granularity is both
acceptable and desirable: rounding to €5 would sometimes make Airbnb and
HomeToGo (identical default commission, 15.5%) diverge from each other purely
due to rounding noise. Rounding to the nearest 1€ keeps them equal or almost
equal whenever their underlying commission is equal, matching the owner's "il
più uguali possibile" request.

### 7. Both reference values are editable Settings, with defaults sourced from `config/apartment.php`
The owner asked explicitly: take the defaults from the existing
`apartment.php` config (don't invent new hardcoded numbers), but make both
values tunable from `admin/impostazioni` "non si sa mai" — same UX as every
other `pricing_*` setting added by `tax-gross-up-pricing`. Both new fields
(`pricing_portal_reference_nights`, `pricing_portal_reference_guests`) are
added to the existing "Fiscalità e prezzi" Settings card, validated as
positive integers, persisted via `Setting::set()`. The nights field includes a
help note that below-default values change how the length discount applies to
this table (Decision 3); the guests field includes a help note that values
below the apartment's bed capacity reopen the "could be cheaper than direct"
risk this feature was built to avoid (Decision 2) — informational only, not a
hard validation block, since the owner explicitly wants it tunable rather than
locked.

## Risks / Trade-offs

- [Risk] Portal nightly rates will read noticeably higher than the direct
  site's own `price_per_night` for the same period, because they embed the
  *maximum* possible cleaning/linen share instead of a typical one → Mitigation:
  this is the explicit, confirmed trade-off the owner accepted in exchange for
  the "never cheaper than direct" guarantee (Decision 2).
- [Risk] Duplicating the tax-gross-up arithmetic between `PricingQuoteService`
  and the new `OtaPortalNightlyRateService` could drift if the formula changes
  later → Mitigation: extract the shared `taxableCents`/`taxGrossUpCents`
  computation (given cleaning/linen cents as input) into a small reusable
  helper referenced by both, during implementation.
- [Trade-off] The "never cheaper" guarantee is proven only under the *current*
  formula shape (fixed cleaning fee + linear per-guest linen fee); if the
  pricing model changes shape again in the future (e.g. tiered linen pricing),
  this guarantee must be re-derived, not assumed to still hold.
- [Risk] Since the reference guest count is editable, the owner (or a future
  admin user) could lower it below bed capacity and unknowingly reopen the
  "portal cheaper than direct" risk this feature was built to prevent →
  Mitigation: the Settings UI carries an explicit help note next to the field
  (Decision 7); no hard validation block, per the owner's explicit request to
  keep it tunable.

## Migration Plan

No database migration required — reuses `pricing_rules` and the `Setting` keys
already introduced by `tax-gross-up-pricing`, plus 2 new `Setting` keys
(`pricing_portal_reference_nights`, `pricing_portal_reference_guests`) that
fall back to `config('apartment.booking.min_nights')` /
`config('apartment.specs.beds')` when unset, so behavior is correct before an
admin ever opens the new Settings fields. Purely additive otherwise: a new
read-only route/view/service. No rollback concerns (nothing is written except
the 2 new settings, only displayed).
