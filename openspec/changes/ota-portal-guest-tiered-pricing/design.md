## Context

`ota-portal-price-table` (archived) solved a narrower problem: guarantee a
portal listing is never cheaper than direct, by defensively amortising the
_maximum_ possible cost (bed-capacity linen + cleaning) over the _minimum_
possible stay (3 nights). That guarantee held, but produced a nightly rate
far above market (roughly 2–2.5× the direct rate), because it treated a cost
that only 3 portals actually let you express as a **per-guest, per-night
surcharge** as if it had to be flattened into one static number.

The owner has since clarified exactly how Airbnb, Booking.com and HomeToGo
let a host price a listing:

- A fixed **cleaning fee** field, separate from the nightly rate (100 € on
  all 3 — confirmed identical).
- An optional flat **extra-guest-per-night** surcharge, starting from a
  host-chosen guest count (amount confirmed identical intent across all 3,
  ~12 €/night/guest).
- HomeToGo additionally allows a **per-guest linen fee** field; Airbnb and
  Booking.com do not. The owner explicitly does not want to use this
  HomeToGo-only field, to keep all 3 portals configured identically.

This means the nightly rate itself only needs to recover the linen cost for a
small, realistic reference guest count (2) — the incremental linen cost for
any guest beyond that is recovered by the portal's own extra-guest surcharge
field, which the direct site has no equivalent of today (the direct site
charges full linen per guest as part of one lump-sum total, not a nightly
surcharge — this is fine, `PricingQuoteService` is unchanged).

## Goals / Non-Goals

**Goals:**

- Produce a portal nightly rate close to the direct site's own
  `price_per_night` (only the 2-guest linen recovery, the cleaning fee's tax
  gross-up, and portal commission separate them), instead of a rate inflated
  for the apartment's full 6-guest capacity.
- Recover the incremental linen cost of guests 3+ through a flat per-portal
  surcharge, mirroring exactly how the portals let the owner price that cost.
- Keep all 3 portals configured with the _same_ extra-guest surcharge amount
  (owner's explicit ask, to avoid managing 3 slightly different numbers).
- Make every number this formula touches (cleaning fee, linen fee, minimum
  stay, extra-guest surcharge, commissions, tax rate) editable from
  `admin/impostazioni`, with `config/apartment.php` values as fallback
  defaults — consistent with how commissions/tax rate/discounts already work.
- Remove the 2 `Setting`s this change makes obsolete
  (`pricing_portal_reference_nights`, `pricing_portal_reference_guests`).
- Use one formula/service for both `admin/prezzi` (simulator) and
  `admin/prezzi-portali` (period table) — no duplicated logic.

**Non-Goals:**

- Do not re-introduce a defensive "never cheaper than direct at _any_ guest
  count" guarantee the way the previous design did — that guarantee is what
  produced the off-market rate this change exists to fix. See Decision 6 and
  Risks for what guarantee actually holds instead.
- Do not touch `max_nights` — only `min_nights` becomes `Setting`-editable
  (explicitly confirmed; `max_nights` stays in `config/apartment.php`).
- Do not change the direct-site guest-facing formula/rounding
  (`PricingQuoteService`'s €5 rounding, weekly/monthly discount, tax gross-up)
  beyond sourcing 3 of its existing inputs from `Setting` instead of `config()`.
- Do not expose the HomeToGo per-guest-linen field — deliberately unused on
  all 3 portals, per the owner's explicit choice to keep them identical.

## Decisions

### 1. Cleaning fee amount is fully scorporated — its tax gross-up is not

Unlike the previous design (which grossed the whole cleaning fee into the
amortised rate), the cleaning fee **amount** is never algebraically blended
into a per-night figure — it is only a reference value shown in the legend
(`admin/prezzi-portali`) and in the simulator (as a flat line in the
guest-facing total). The owner types the existing `pricing_cleaning_fee`
value (100 €, unchanged default) directly into each portal's own
cleaning-fee field, exactly as instructed: "100 € a prenotazione", a flat
pass-through, not a computed value.

However, the direct site's tax gross-up (`pricing_tax_rate` ×
`pricing_tax_gross_up_items`) normally taxes cleaning **and** linen together
(default: both selected). If the portal formula only recovered the linen
share of that tax (as an earlier revision of this design did), a 2-guest,
minimum-stay portal booking would net the owner noticeably less than the
equivalent direct booking — not just the commission cut on the flat cleaning
fee (≈15.50 € at today's rates), but _also_ the entire tax gross-up on the
cleaning fee itself (≈21 €, since that portion is completely absent from a
formula that never references the cleaning fee at all) — a combined ≈36.50 €
shortfall discovered during verification (Phase 9), well beyond the
≈15–16 € originally estimated.

To close that gap while still keeping the owner's literal instruction (100 €
flat, no computation, into the portal's cleaning-fee field), the per-night
add-on (Decision 4) recovers the cleaning fee's **tax gross-up** alongside
the linen recovery — i.e. `taxGrossUpCents($cleaningFeeCents, $referenceLinenCents)`
instead of `taxGrossUpCents(0, $referenceLinenCents)`. The cleaning fee
amount itself still never appears added anywhere in the nightly-rate formula
and is still not grossed up for commission — only its _tax_ is recovered.
This narrows the accepted, documented shortfall back down to just the
commission cut on the flat 100 € (≈15.50 €, see Risks), which is what the
owner actually agreed to keep.

### 2. Reference guest count: fixed at 2, no longer a `Setting`

`pricing_portal_reference_guests` is removed — the previous "defend against
the worst case" reasoning for making it bed-capacity (6) no longer applies,
since incremental guests are now handled by the extra-guest surcharge, not by
inflating the base rate. 2 is a fixed business constant (not
`config`/`Setting`-driven) matching the owner's typical-occupancy assumption
already used elsewhere (e.g. the booking form's default guest count).

### 3. Reference stay length merges into (newly `Setting`-editable) `pricing_min_nights`

The previous `pricing_portal_reference_nights` `Setting` is removed. Instead
of a second, portal-specific "reference nights" number that could silently
drift from the site's actual minimum stay, the formula now reads the same
minimum-stay value the rest of the site enforces — which becomes `Setting`
editable for the first time as `pricing_min_nights` (falling back to the
current `config('apartment.booking.min_nights')`, `3`, when unset). This is a
deliberate scope increase beyond pure OTA pricing (confirmed with the owner):
changing this setting also changes the minimum bookable stay on the direct
site and the public booking form, not just this formula. `max_nights` is
**not** touched — it stays a `config`-only value.

### 4. New base nightly rate formula (per-night, not per-stay-total)

```
referenceGuests   = 2                                                  // fixed constant
referenceNights    = (int) Setting::get('pricing_min_nights', config('apartment.booking.min_nights', 3))
linenFeeCents      = (int) Setting::get('pricing_linen_fee_per_person', config('apartment.booking.linen_fee_per_person', 25)) * 100
referenceLinenCents = linenFeeCents * referenceGuests                    // 25€ × 2 = 50€ → 5000 cents
cleaningFeeCents   = (int) Setting::get('pricing_cleaning_fee', config('apartment.booking.cleaning_fee', 100)) * 100
taxGrossUpCents    = taxGrossUpCents(cleaningCents: cleaningFeeCents, linenCents: referenceLinenCents)
                                                                          // shared ResolvesTaxGrossUp trait; the
                                                                          // cleaning fee AMOUNT is never added anywhere
                                                                          // below — only its tax gross-up is recovered
                                                                          // here (Decision 1); still respects the
                                                                          // cleaning/linen toggles in pricing_tax_gross_up_items
recoverableCents   = referenceLinenCents + taxGrossUpCents               // 50€ linen + 21% tax on (100€ + 50€) = 81.50€ → 8150 cents (default settings)
perNightAddOnCents = round(recoverableCents / referenceNights)          // 8150 / 3 = 2717 cents (≈27.17€/night)

// Per portal (airbnb/booking/hometogo):
commissionRate       = (float) Setting::get("pricing_commission_{portal}", <default>)
baseNightlyRateCents = round((pricingRule.price_per_night + perNightAddOnCents) / (1 - commissionRate))
finalNightlyRate     = round to nearest 100 cents (nearest whole euro) — same rounding as before
```

No length-discount lookup is applied here — unlike the old formula, this one
is inherently per-night (it never builds and then divides back a multi-night
stay total), so the weekly/monthly discount tiers (which apply to a _stay
total_, not a nightly rate) have nothing to act on. This also means the
`ResolvesLengthDiscountRate` trait is no longer needed for this half of the
service (see Decision 8 for where it _is_ still used).

Worked example at today's defaults (100 €/night rule, Airbnb 15.5%):
`(10000 + 2717) / 0.845 = 15044` cents → rounds to **150 €/night** (vs the
previous design's ≈243 €/night for the same input — still a realistic,
market-close figure, and close to the direct site's own 100 € since the
add-on plus commission markup is the only difference).

### 5. Extra-guest-per-night surcharge: one flat, manually-editable `Setting`, not derived per portal

`pricing_extra_guest_fee` (euros, integer, default `12`) is a plain
`Setting`, entered and edited exactly like `pricing_commission_airbnb` etc. —
**not** algebraically derived from linen/tax/commission at request time. The
default (`12`) is _informed_ by the same recovery logic (linen for 1 extra
guest, tax-grossed, commission-grossed, amortised over the reference nights —
see the table below) but intentionally **not** computed per-portal, because
the owner wants exactly one number to type into all 3 portals, and the
per-portal-derived values happen to all round to the same whole euro anyway:

| Portal      | Commission | (25×1.21) ÷ (1‑commission) ÷ 3 nights |
| ----------- | ---------- | ------------------------------------- |
| Airbnb      | 15.5%      | 30.25 / 0.845 / 3 = 11.93 € → 12 €    |
| Booking.com | 16.5%      | 30.25 / 0.835 / 3 = 12.08 € → 12 €    |
| HomeToGo    | 15.5%      | 30.25 / 0.845 / 3 = 11.93 € → 12 €    |

Applies "from the 3rd guest onward" (i.e. guest count − 2, floored at 0) —
this "from the 3rd guest" rule and the apartment's max occupancy (6, unchanged
`config('apartment.specs.beds')`) are configured directly on each portal's own
UI; the app does not need to enforce or compute that tier boundary anywhere
beyond the legend text.

### 6. Cleaning fee and linen fee per person promoted from `config` to `Setting`

`pricing_cleaning_fee` and `pricing_linen_fee_per_person` (both plain integer
euros, matching today's `config/apartment.php` values 100/25) become
`Setting`s, following the exact same fallback pattern as every other
`pricing_*` key: `Setting::get('pricing_cleaning_fee', config('apartment.booking.cleaning_fee', 100))`.
`PricingQuoteService` (direct-site pricing) switches to reading these 2
values from `Setting` instead of `config()` directly — this is the one
existing behavior this change touches outside of OTA pricing, done because
both new and old formulas need the _same_ cleaning/linen figures and the
owner does not want two divergent sources of truth (one in a deploy-only
config file, one editable). `config/apartment.php` values remain as fallback
defaults; nothing changes for an installation that never edits these 2 new
Settings fields. The admin booking form's cleaning/linen _placeholder_ values
(`resources/views/admin/bookings/form.blade.php`) also switch to the
`Setting`-backed values for consistency (still just a placeholder — a booking
can still override its own cleaning/linen amount).

### 7. `OtaPortalPricingService` and `OtaPortalNightlyRateService` merge into one service

Both callers (the period table and the simulator) now need the exact same
base-nightly-rate/extra-guest-fee building blocks — keeping two services
would either duplicate the formula or have one wrap the other for no benefit.
`OtaPortalNightlyRateService` is deleted; its logic and tests move into
`OtaPortalPricingService`, which gains:

- `baseNightlyRateCents(int $pricePerNightCents, string $portal): int` — used
  by `admin/prezzi-portali` (one call per `PricingRule` × portal) and, summed
  across the simulated nights, by the simulator (see Decision 8).
- `extraGuestFeeCents(): int` — the shared, single `Setting` value.
- `cleaningFeeCents(): int` — for legend/simulator display only.
- The existing per-portal `commissionRate(string $portal): float` helper.

### 8. Simulator: real guest-facing portal total + informational owner-net comparison

The simulator (`admin/prezzi`) already computes, for the chosen
checkin/checkout/guests, the direct-site `stay_gross_cents` (raw, per-night
sum across whichever `PricingRule`s apply, before any discount) and
`total_cents` (the real direct total). The unified service reuses
`stay_gross_cents` rather than re-deriving a per-night rule lookup:

```
perNightAddOnCents   = <as in Decision 4>
baseStayBeforeCents  = stay_gross_cents + perNightAddOnCents * nights
baseStayGrossedCents = round(baseStayBeforeCents / (1 - commissionRate))
lengthDiscountRate   = lengthDiscountRateForNights(nights)         // ResolvesLengthDiscountRate — still used
                                                                     // here, mirroring the portal's own
                                                                     // weekly/monthly discount, assumed configured
                                                                     // identically to the direct site (unchanged
                                                                     // assumption from the previous simulator design)
baseStayDiscountedCents = round(baseStayGrossedCents * (1 - lengthDiscountRate))
extraGuestCents      = extraGuestFeeCents * max(0, guests - 2) * nights
guestTotalCents      = baseStayDiscountedCents + extraGuestCents + cleaningFeeCents
ownerNetCents        = round(guestTotalCents * (1 - commissionRate))
marginSafe           = ownerNetCents >= total_cents (the direct quote's own total_cents, same guests/nights)
```

This is shown per portal in the simulator alongside a ✅/⚠️ indicator for
`marginSafe`. See Risks for why this indicator is informational, not a hard
guarantee, and why `marginSafe` can legitimately read ⚠️ in some cases without
that being a bug.

### 9. Legend on `admin/prezzi-portali`

A legend block (above the existing period table, which is otherwise
unchanged in shape — still one row per `PricingRule`, one column per portal)
explains, once (these values do not vary by period):

- The fixed cleaning fee to type into each portal (`pricing_cleaning_fee`).
- The extra-guest-per-night surcharge to type into each portal, and from
  which guest number it applies (`pricing_extra_guest_fee`, "dal 3° ospite").
- A short per-portal note (Airbnb/Booking.com: no per-guest linen field
  available; HomeToGo: has one, deliberately left unused for consistency).
- A link to `admin/impostazioni` to edit any of these values.
- A one-line explanation that the 3 base-rate columns already fold in linen
  for 2 guests + tax + commission, so nothing else needs to be added on top
  of them besides the cleaning fee and extra-guest fields above.
- An explicit note on the accepted cleaning-fee commission trade-off (see
  Risks): the 100 € cleaning fee is typed in flat/un-grossed, so each portal
  keeps roughly `cleaning_fee × commission_rate` (≈15–16 € at today's rates)
  of it as commission instead of passing it through to the owner — written
  down here specifically so the owner has a documented, one-click-away
  reminder of this trade-off if they ever want to revisit grossing up the
  cleaning fee too (Decision 1's alternative).

### 10. Settings page restructuring

Replace the "Riferimento tabella prezzi portali" field group
(`pricing_portal_reference_nights`/`_guests`) with 3 smaller groups in the
same "Fiscalità e prezzi" card:

- **Costi fissi per soggiorno**: `pricing_cleaning_fee` (€), `pricing_linen_fee_per_person` (€/ospite).
- **Soggiorno minimo**: `pricing_min_nights` (notti), with a help note that
  this also changes the minimum bookable stay on the direct site/booking form.
- **Supplemento ospite extra (portali)**: `pricing_extra_guest_fee`
  (€/notte/ospite dal 3° ospite), with a help note linking to
  `admin/prezzi-portali` and stating it applies identically to all 3 portals.

## Risks / Trade-offs

- **[Accepted trade-off] The cleaning-fee commission leakage is not
  compensated.** Because the cleaning fee is deliberately left flat/un-grossed
  (Decision 1, per the owner's explicit instruction), the portion of a real
  portal booking's guest-facing total that is the cleaning fee nets the owner
  only `cleaningFee × (1 − commissionRate)` (e.g. 100 € × 0.845 ≈ 84.50 € on
  Airbnb), not the full 100 € the direct site effectively recovers with no
  commission at all. For a booking of exactly 2 guests at exactly the minimum
  stay, this means the portal booking nets the owner _slightly less_ than the
  equivalent direct booking (roughly `cleaningFee × commissionRate`, ≈15–16 €
  per stay at today's rates) — the literal "margin never lower than direct"
  goal is not a hard mathematical guarantee once this is factored in, only a
  close approximation. Any real stay longer than the minimum, or with more
  than 2 guests, recovers a growing surplus elsewhere in the formula that
  typically outweighs this fixed per-stay shortfall, but this is not proven
  for every input combination. Mitigation: this was an explicit,
  deliberate choice (flat 100 €, no gross-up) reconfirmed during scoping;
  the simulator's ✅/⚠️ indicator (Decision 8) surfaces this per-simulation
  instead of asserting a blanket guarantee, so the owner can see exactly
  when/why a ⚠️ shows up rather than being told "always safe" and having that
  be subtly false. The `admin/prezzi-portali` legend (Decision 9) also states
  this trade-off explicitly in writing, so the owner has a standing reminder
  of it — and of the alternative (grossing up the cleaning fee too, at the
  cost of it no longer being the same flat 100 € on every portal) — the next
  time they revisit these numbers, without having to re-derive it from
  scratch or re-read this design doc. (Phase 9 verification initially found a
  ≈36.50 € shortfall — the commission cut _plus_ a missed tax gross-up on the
  cleaning fee — before Decision 1 was extended to also recover that tax
  portion, narrowing it down to the ≈15–16 € commission-only figure quoted
  above.)
- **[Risk] Widening `pricing_min_nights`'s blast radius.** Making minimum
  stay length `Setting`-editable (previously a deploy-only `config` value)
  means an admin could change it from `admin/impostazioni` and immediately
  affect real booking availability/validation across the whole site, not just
  this pricing table — a bigger change than a typical "pricing tweak".
  Mitigation: explicitly confirmed with the owner; the Settings UI help text
  states this plainly next to the field.
- **[Risk] `pricing_extra_guest_fee` being a flat, non-derived `Setting` means
  it will not automatically stay "correct"** if the owner later changes
  `pricing_linen_fee_per_person`, `pricing_tax_rate`, or the commission rates
  — unlike the base nightly rate (recomputed live from those settings), this
  number must be manually revisited if any of its underlying inputs change.
  Mitigation: the Settings help text and the `admin/prezzi-portali` legend
  both state the derivation reasoning (Decision 5's table) so the owner knows
  when a revisit is warranted; not automated, per the owner's explicit request
  for one manually-set number.

## Migration Plan

No database migration — reuses the existing `settings` key-value table.
4 new `Setting` keys (`pricing_cleaning_fee`, `pricing_linen_fee_per_person`,
`pricing_min_nights`, `pricing_extra_guest_fee`), each falling back to its
current `config/apartment.php` value when unset, so behavior is unchanged
until the owner edits any of the new Settings fields. 2 `Setting` keys
removed (`pricing_portal_reference_nights`, `pricing_portal_reference_guests`)
— any previously-saved values for these 2 keys become inert leftover rows in
the `settings` table (harmless; not read by any code path after this change,
no cleanup migration needed for a simple key-value store).
