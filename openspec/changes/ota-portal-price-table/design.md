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
- Do not pre-bake the weekly/monthly length discount into this table's rate —
  the reference stay length (`min_nights`) is chosen specifically below the
  7-night threshold so the discount never applies here; it still applies
  automatically and identically on both channels for real longer bookings
  (unchanged from `tax-gross-up-pricing`).
- Do not add a configurable "reference guest count" setting — fixed to the
  apartment's bed capacity, matching the "never cheaper" hard requirement (see
  Decision 2). Not user-tunable, to avoid silently reopening the risk the owner
  asked to eliminate.
- Do not change `OtaPortalPricingService` (per-simulated-stay suggestion) or
  the existing `admin/prezzi` simulator — this is a new, separate view.

## Decisions

### 1. Reference stay length = `min_nights` (currently 3), not a new constant
Using the existing `config('apartment.booking.min_nights')` as the reference
number of nights (rather than inventing a new one) keeps this table consistent
with the fact the owner already configures the *same* minimum-nights rule on
every portal ("ho impostato su tutti i portali un minimo di 3 notti, come su
questo portale" — confirmed by the owner). Being the minimum possible booking
length, it is also the point where fixed costs (cleaning/linen) weigh the most
per night — i.e. the worst case for the owner, which biases the computed rate
safely upward for any longer, discounted real booking.

### 2. Reference guest count = apartment bed capacity (6), not a lower/typical value
Considered guest counts 2 (the project's existing "typical occupancy" default,
used e.g. by the pricing simulator and booking form) and 5 (a compromise
suggested by the owner). Worked example with current defaults (cleaning 100€,
linen 25€/guest, tax 21%, commissions 15.5–16.5%, 105€/night, 3-night
reference):

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
typical 2–4 guest bookings) is acceptable in exchange for the hard guarantee.

### 3. Formula (reusing, not duplicating, `tax-gross-up-pricing`'s building blocks)
```
referenceNights = config('apartment.booking.min_nights')        // 3
referenceGuests  = config('apartment.specs.beds')                // 6

stayGrossCents   = pricingRule.price_per_night * referenceNights
cleaningCents    = config('apartment.booking.cleaning_fee') * 100
linenCents       = config('apartment.booking.linen_fee_per_person') * 100 * referenceGuests
taxableCents     = sum of cleaningCents/linenCents for items selected in
                   Setting('pricing_tax_gross_up_items')                // same as PricingQuoteService
taxGrossUpCents  = round(taxableCents * Setting('pricing_tax_rate'))

directTotalCents = stayGrossCents + taxGrossUpCents + cleaningCents + linenCents
                   // no length discount (referenceNights < weekly threshold by construction)
                   // no €5 rounding here — this is an internal reference figure, not a guest-facing charge

portalTotalCents      = round(directTotalCents / (1 - commissionRate))   // per portal
portalNightlyRateCents = round(portalTotalCents / referenceNights)
finalNightlyRate       = round to nearest 100 cents (nearest whole euro)
```
`stayGrossCents`/`taxableCents`/`taxGrossUpCents` reuse the exact same
tax-gross-up logic already in `PricingQuoteService` — extracted into a small
shared helper (or duplicated minimally, see Risks) rather than re-derived, to
avoid the two services drifting apart if the tax formula changes later. The
length-discount lookup (`ResolvesLengthDiscountRate`) is deliberately **not**
used here (see Non-Goals).

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

## Migration Plan

No database migration required — reuses `pricing_rules` and the `Setting` keys
already introduced by `tax-gross-up-pricing`. Purely additive: a new read-only
route/view/service. No rollback concerns (nothing is written, only displayed).
