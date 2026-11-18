## Context

See `openspec/changes/archive/2026-09-04-ota-portal-guest-tiered-pricing/design.md`
for the full base formula this change extends. Recap of the 2 call sites
this change touches:

```
// baseNightlyRateCents(pricePerNightCents, portal) — admin/prezzi-portali
rateCents = (pricePerNightCents + perNightAddOnCents) / (1 - commissionRate)

// guestFacingTotal(...) — admin/prezzi simulator
baseStayBeforeCents  = stayGrossCents + perNightAddOnCents * nights
baseStayGrossedCents = baseStayBeforeCents / (1 - commissionRate)
```

Both already divide a "grossed" subtotal by `(1 - commissionRate)` before
any rounding/discounting. Neither touches `cleaningFeeCents()` or
`extraGuestFeeCents()` — those stay flat, literal pass-through values typed
directly into each portal's own fields (Decision 1 of the referenced
design.md). This change preserves that boundary.

## Goals / Non-Goals

**Goals:**

- Give the owner one explicit, editable percentage that widens the
  portal-vs-direct gap, usable at any guest count (including 2), without
  touching any real fiscal/cost/commission figure.
- Keep the new figure in the same "Fiscalità e prezzi" settings card (owner's
  explicit preference — it is still a portal-pricing input, just a
  deliberately commercial one rather than a fiscal one).
- Apply it identically wherever the commission-grossing division already
  happens, so `admin/prezzi-portali` (nightly rate) and `admin/prezzi`
  (simulated total) never drift onto two different margin behaviors.

**Non-Goals:**

- Do not apply the margin to `pricing_cleaning_fee` or
  `pricing_extra_guest_fee` — both remain flat numbers the owner types
  verbatim into each portal's own fields; grossing them up would break that
  contract and reintroduce the kind of drift Decision 1 was written to avoid.
- Do not make the margin per-portal — one shared value for all 3 portals,
  consistent with how `pricing_extra_guest_fee` is already shared.
- Do not surface the margin as a separate line item in the simulator
  breakdown — it folds into the existing guest-facing total figure, same as
  the commission markup already does today (owner's explicit choice).
- Do not change `PricingQuoteService` (direct-site pricing) at all.

## Decisions

### 1. New setting: `pricing_portal_extra_margin_percent`, default `5%`

Follows the exact same pattern as `pricing_commission_airbnb` etc.: stored as
a fraction (`'0.05'`), input/display as a percentage (`× 100`) in the
Settings form, validated `numeric, min:0, max:100`. Default `5%` — the owner
confirmed the default value doesn't matter much since it's editable
immediately after this ships.

### 2. Applied once, at the same point the commission divisor is applied

```
extraMarginRate = (float) Setting::get('pricing_portal_extra_margin_percent', '0.05')

// baseNightlyRateCents
rateCents = (pricePerNightCents + perNightAddOnCents) / (1 - commissionRate) * (1 + extraMarginRate)

// guestFacingTotal
baseStayGrossedCents = (stayGrossCents + perNightAddOnCents * nights) / (1 - commissionRate) * (1 + extraMarginRate)
```

Everything downstream (the weekly/monthly length-discount multiplication in
`guestFacingTotal()`, the final euro rounding in both methods, the
`extraGuestCents`/`cleaningFeeCents` flat additions, `ownerNetCents`
derivation) is unchanged — it now simply operates on a larger grossed
subtotal. `ownerNetCents` therefore also increases (the owner nets more of
the extra margin, minus that portal's own commission cut on it, same as
every other euro in the guest-facing total) — this is intentional; the
margin is a real commercial markup, not a cosmetic display-only number.

Worked example at today's defaults (100 €/night rule, Airbnb 15.5%, 5%
margin): `(10000 + 2717) / 0.845 × 1.05 ≈ 15802` cents → rounds to **158
€/night** (vs 150 €/night with no margin, vs the direct site's 100 €/night).

### 3. Why multiply here and not add a flat euro amount

A percentage multiplier scales naturally with room rate (a 300 €/night peak
season rule gets proportionally the same nudge as a 70 €/night rule), so the
owner sets it once and doesn't have to revisit it as pricing rules change
throughout the year — consistent with why `pricing_commission_*` and the
length-discounts are also percentages, not flat amounts.

### 4. Rounding order

The multiplier is applied **before** the per-portal nearest-euro rounding in
`baseNightlyRateCents()` and **before** the length-discount rounding in
`guestFacingTotal()` — i.e., it participates in the existing single rounding
step at the end of each formula rather than introducing a second rounding
pass. This avoids compounding rounding error and keeps both methods'
existing rounding behavior (nearest euro / nearest cent after discount)
unchanged in shape.

## Risks / Trade-offs

- Raising the default margin also raises the owner's own portal net revenue
  proportionally (see Decision 2) — this is expected and desired (the whole
  point is more separation, and the owner nets more too), but worth stating
  explicitly since past changes in this area treated "owner net" as a
  passive, computed-only figure.
- A single shared margin (Non-Goal: not per-portal) means a portal with a
  higher commission (Booking.com, 16.5%) ends up with a very slightly larger
  absolute euro gap than a lower-commission portal for the same input,
  purely because the multiplier is applied before the commission division's
  reciprocal — this mirrors how the existing commission-driven gap already
  varies slightly by portal today, so it introduces no new inconsistency.
