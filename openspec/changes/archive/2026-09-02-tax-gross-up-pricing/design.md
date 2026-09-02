## Context

The owner rents privately (no VAT number, flat-rate "Cedolare Secca" tax, currently
21%). Under this regime tax is owed on the *whole* amount transferred by the guest,
including the cleaning fee and linen fee that are simply passed through to the
housekeeper/laundry. `PricingQuoteService::calculate()` is the single pricing engine
used by both the public booking quote (`Public\BookingController::quote` /
`requestAvailability`) and the admin simulator (`Admin\PricingController::simulate`).
It currently returns `total_cents = stay_cents + cleaning_cents + linen_cents` with
no tax adjustment (see `docs/specific-tech-backend-doc.mdc`, "Service map").

The owner also lists the apartment on Airbnb, Booking.com and HomeToGo. Each portal
deducts a commission from the payout, so the price advertised there must be higher
than the direct-site price to net the same revenue. The owner supplied a reference
analysis (previously `docs/commissioni portali.md`, folded into this document,
see "Portal commission defaults" below) with the commission figures to use as
defaults.

On all 3 portals the owner also configures a native length-of-stay discount (10%
for stays of 7+ nights, 20% for 28+ nights — the higher tier replacing, not
stacking with, the lower one). The direct site has no equivalent price reduction
today beyond the natural "linear amortisation" effect (fixed costs spread over
more nights already lower the *average*, but there is no actual percentage
discount). A `StayDiscountRule` model/table/CRUD controller exists in the codebase
(`app/Models/StayDiscountRule.php`, migration `2026_04_04_130000_create_stay_discount_rules_table`,
routes under `admin/sconti-soggiorno`) but is dormant by design: its `index()` view
was repurposed into a read-only "Formula prezzi" explanation page, and
`docs/business-doc.mdc` explicitly documents "No editable discount rules". This
change reverses that earlier product decision, at the owner's request, but with a
simpler fixed-tier model (7/28 nights) rather than reviving the more general
arbitrary-tier `StayDiscountRule` scaffold.

Settings that must be editable without a deploy (tax rate, gross-up item selection,
portal commissions, weekly/monthly discount percentages) are stored via the
existing generic `Setting` key-value model (`app/Models/Setting.php`, `settings`
table: `key` PK, `value` text) — the same mechanism already used for
`booking_mode` / `booking_external_url`. No new table or migration is needed.

## Goals / Non-Goals

**Goals:**
- Gross up the guest-facing total by the tax owed on the accessory costs the owner
  chooses to include (default: cleaning + linen), so the owner's net take-home is
  unaffected by paying tax on money that just passes through to third parties.
- Apply this gross-up to the REAL price used across the whole direct-booking flow
  (public quote, `booking_requests` estimate, confirmed `Booking.income_amount`) —
  not just an admin-only report.
- Round the final guest-facing total to the nearest €5, absorbing the rounding
  delta into the nightly-stay portion so the breakdown still reconciles exactly.
- Compute and display, in the admin pricing simulator, the suggested listing price
  (total + avg/night) for Airbnb, Booking.com and HomeToGo, derived from the
  (already tax-grossed, discounted) direct price, each portal's commission rate,
  and the assumed length-of-stay discount configured on that portal.
- Apply a real weekly (7+ nights) / monthly (28+ nights) discount to the
  nightly-stay portion of the direct-site price, the higher tier replacing rather
  than stacking with the lower one.
- Make the tax rate, the set of accessory items it applies to, the 3 portal
  commission rates, and the weekly/monthly discount percentages editable from the
  admin Settings page.

**Non-Goals:**
- Do not build a generic settings framework / migrate other `config/apartment.php`
  values into `Setting` — only the new pricing-related values described here.
  (The owner mentioned wanting to migrate more things later; that's a separate,
  future change.)
- Do not change how parking is collected (still cash, on-site, out of `total_cents`).
  If the owner ticks "parking" in the gross-up item selector, the checkbox has no
  effect on `total_cents` today because parking was never part of it — documented
  as a known limitation (see Risks), not solved in this change.
- Do not touch the "Dichiarazione dei redditi" report (`TaxDeclarationController`)
  or its `income_tax`/`cleaning_tax`/`linen_tax`/`parking_tax` flags — those track
  which amounts the owner declares to their accountant and are unaffected by how
  the total was priced.
- Do not add per-portal seasonal/date-based commission variation — a single flat
  rate per portal, editable any time.
- Do not add per-portal-specific weekly/monthly discount percentages — a single
  global pair of percentages is used both for the direct site and assumed
  identical across all 3 portals (the owner confirmed they'll configure the same
  values everywhere to avoid confusion); per-portal overrides are a possible
  future enhancement, not built now.
- Do not resurrect or reuse the dormant `StayDiscountRule` model/table/CRUD — it
  remains untouched, disconnected dead code, out of scope for this change.
- Do not add configurable night thresholds (7 / 28) — fixed in code as constants;
  only the two percentages are configurable.

## Decisions

### 1. Settings storage: flat `Setting` keys, not a new table
Reuse `Setting::get()/set()` with flat string keys (matching the existing
`booking_mode` convention, no dot-namespacing helper exists in the model):
- `pricing_tax_rate` — string-encoded decimal fraction, e.g. `"0.21"`.
- `pricing_tax_gross_up_items` — JSON array of item keys, e.g. `["cleaning","linen"]`.
  Valid keys: `cleaning`, `linen`, `parking`.
- `pricing_commission_airbnb` — `"0.155"`.
- `pricing_commission_booking` — `"0.165"`.
- `pricing_commission_hometogo` — `"0.155"`.
- `pricing_weekly_discount_percent` — `"0.10"` (applies to stays of 7+ nights).
- `pricing_monthly_discount_percent` — `"0.20"` (applies to stays of 28+ nights,
  replacing the weekly discount rather than stacking with it).

Alternative considered: add these to `config/apartment.php`. Rejected because the
owner explicitly wants to tune the tax rate, commissions and discounts without a
deploy, and the existing `Setting` store already exists for exactly this purpose.

### 2. Combined formula: length-of-stay discount → tax gross-up → €5 rounding
All three adjustments are computed in `PricingQuoteService::calculate()`, in this
order, and folded into the stay component:
```
stay_gross_cents      = sum(nightly PricingRule rate for each night)     // unchanged today

length_discount_rate  = nights >= 28 ? monthly_discount_rate
                       : nights >= 7  ? weekly_discount_rate
                       : 0
stay_discount_cents    = round(stay_gross_cents * length_discount_rate)
discounted_stay_cents  = stay_gross_cents - stay_discount_cents

taxable_cents         = sum of cents for each selected item in {cleaning, linen, parking}
                        that actually contributes to total_cents (today: cleaning, linen)
tax_gross_up_cents    = round(taxable_cents * tax_rate)

raw_total_cents       = discounted_stay_cents + tax_gross_up_cents + cleaning_cents + linen_cents
rounded_total_cents   = round(raw_total_cents / 500) * 500                // nearest €5
rounding_adjustment   = rounded_total_cents - raw_total_cents

stay_cents            = discounted_stay_cents + tax_gross_up_cents + rounding_adjustment
total_cents           = stay_cents + cleaning_cents + linen_cents         // == rounded_total_cents
avg_per_night_cents   = round(total_cents / nights)                      // unchanged formula
```
The length-of-stay discount applies only to the nightly-stay portion (not
cleaning/linen), matching how Airbnb's own weekly/monthly discount works and the
owner's confirmation. The rounding delta is absorbed into the stay portion (not
cleaning/linen) because those are meant to reflect the literal, real pass-through
costs paid to the housekeeper/laundry — the discount, tax gross-up and rounding
are effectively all part of the room rate.

`stay_cents` keeps its existing name/meaning as "whatever gets copied into
`Booking.income_amount`" so no downstream caller (`Public\BookingController`,
`Admin\BookingRequestController::confirm()`) needs to change — they automatically
start persisting the discounted, tax-aware amount. New fields `stay_gross_cents`,
`stay_discount_cents`, `length_discount_rate` and `tax_gross_up_cents` are added to
the return array purely for UI transparency (so the simulator can show e.g.
"Tariffa notte: 500 € (sconto soggiorno 7+ notti: −50 €) + tasse costi accessori:
+42 €").

Alternative considered for the tax gross-up: `portal-style` gross-up dividing by
`(1 - tax_rate)` instead of adding `tax_rate * taxable_cents`. Rejected — it
doesn't match the owner's own worked example (500 net + 200 costs + 42 tax = 742,
not 500+200 grossed by `1/(1-0.21)`), and the owner confirmed the additive formula
in Q&A.

### 3. Portal price suggestion as a separate service
New `App\Services\OtaPortalPricingService` (single responsibility: turn a direct
total into 3 portal totals), independent from `PricingQuoteService`:
```
length_discount_rate(nights)  = same tiered lookup as Decision 2 (same global setting,
                                 assumed identical on all 3 portals)
portal_total_cents             = round(total_cents / ((1 - length_discount_rate) * (1 - commission_rate)))
portal_avg_per_night_cents     = round(portal_total_cents / nights)
```
It reads the 3 commission settings and the 2 discount settings via `Setting::get()`
with the defaults below. Kept separate from `PricingQuoteService` because it's a
pure display/reporting concern with no bearing on real charges, matching the two
independent capabilities in the proposal (`tax-gross-up-pricing` vs
`ota-portal-pricing`).

**Simplification / trade-off**: `total_cents` already bundles the stay discount,
tax gross-up, cleaning and linen together. Dividing the *whole* total by
`(1 - length_discount_rate)` implicitly assumes the portal's own weekly/monthly
discount also applies to cleaning/linen and to the tax portion, which isn't
strictly how Airbnb/Booking.com apply their discount (usually accommodation fare
only). A fully precise formula would need to re-split `total_cents` back into its
stay/cleaning/linen components before applying the two factors differently. This
is deliberately not done — the portal price is a rounded *suggestion* the owner
still reviews and rounds manually (per the earlier "exact value, I'll round myself"
decision), so the small overcorrection is an acceptable trade-off for a much
simpler formula. Documented under Risks below.

### 4. Portal commission defaults (from the owner-provided analysis)
Source: owner-supplied `docs/commissioni portali.md` (deleted after this change is
merged; content preserved here).

| Portal | Model | Commission | Default used |
|---|---|---|---|
| Airbnb | Host-only fee on booking total (nightly rate + cleaning) | 15.5% | `0.155` |
| Booking.com | 15.0% base + ~1.5% payment collection | 16.5% total | `0.165` |
| HomeToGo | "Flex" model (chosen by owner over the 3% "Split" model) | ~15.5% gross | `0.155` |

Formula for all 3 portals (owner confirmed this is acceptable for all of them):
```
P_portal = P_direct_with_tax / ((1 - length_discount_rate) * (1 - commission_rate))
```
Worked example (100 €/night direct, stay under 7 nights so no length discount):
Airbnb/HomeToGo → 100 / 0.845 = 118.34 €; Booking.com → 100 / 0.835 = 119.76 €.

### 5. Settings UI: new dedicated form, own controller action
Add a new "Fiscalità e prezzi" card to `admin/settings.blade.php` with its own
`<form>` posting to a new `PUT admin.settings.pricing` route /
`SettingsController::updatePricing()`, kept separate from the existing booking-mode
form (same page already has multiple independent forms — booking mode, one per
calendar provider). Fields:
- Tax rate (%, number input, e.g. `21`) → stored as `0.21`.
- 3 checkboxes: Pulizie, Biancheria, Parcheggio (parking has a help note explaining
  it currently has no effect, per Non-Goals).
- 3 number inputs (%): Airbnb, Booking.com, HomeToGo.
- 2 number inputs (%): Sconto settimanale (7+ notti), Sconto mensile (28+ notti).

### 6. Admin pricing simulator: extend existing endpoint, not a new one
`PricingController::simulate()` already returns the JSON breakdown consumed by
`pricing-simulator.ts`. Extend the same response with `tax_gross_up_cents`,
`stay_gross_cents`, `stay_discount_cents`, `length_discount_rate`, and a `portals`
map (`airbnb`/`booking`/`hometogo` → `total_cents`, `avg_per_night_cents`,
`commission_rate`), computed via the new `OtaPortalPricingService`.
`pricing-simulator.ts` renders extra breakdown lines and 3 small portal cards
under the existing result box.

## Risks / Trade-offs

- [Risk] Existing feature tests assert exact price totals from
  `PricingQuoteService` (e.g. `BookingLegalConsentTest`,
  `BookingRequestConfirmationTest`) → Mitigation: update expected amounts as part
  of this change's tasks; defaults (21% tax on cleaning+linen only, no length
  discount below 7 nights) are deterministic so the new expected numbers are easy
  to compute.
- [Risk] Real guest-facing prices change silently the moment this ships (default
  tax rate 21% and 10%/20% length discounts are active immediately) → Mitigation:
  this is the explicit goal of the change; no feature flag is planned since the
  owner wants it live for the next booking, but the Settings UI allows setting the
  tax rate and discount percentages to `0` to fully disable either adjustment if
  needed.
- [Risk] "Parking" gross-up checkbox is offered but currently inert (parking isn't
  part of `total_cents`) → Mitigation: documented explicitly in Non-Goals and in
  the Settings UI help text, to avoid the owner thinking it does something it
  doesn't.
- [Risk] Reintroducing a direct-site length-of-stay discount reverses the earlier,
  explicitly documented decision to not use percentage discounts (`docs/business-doc.mdc`,
  `admin/stay-discounts/index.blade.php`) → Mitigation: this change updates that
  page and doc note to reflect the new behavior; the dormant `StayDiscountRule`
  scaffold is intentionally left untouched rather than revived, to avoid confusing
  two parallel discount mechanisms.
- [Trade-off] The portal price formula divides the whole `total_cents` by both
  `(1 - length_discount_rate)` and `(1 - commission_rate)`, slightly overcorrecting
  since real portals typically don't apply their length discount to the cleaning
  fee — accepted as a simplification for a suggestion the owner rounds manually
  (see Decision 3).
- [Trade-off] Folding discount + tax + rounding into `stay_cents` means the
  per-night average price shown to guests no longer maps 1:1 to any single
  `PricingRule.price_per_night` value — acceptable since `avg_per_night_cents` was
  already a derived/blended figure before this change (linear amortisation model).

## Migration Plan

No database migration required (`Setting` table already exists). Deploy steps:
1. Ship code with `Setting::get(key, <default>)` fallbacks so behavior is correct
   even before an admin ever opens the new Settings form (defaults: `21%` tax on
   cleaning+linen, commissions `15.5% / 16.5% / 15.5%`, discounts `10%` weekly /
   `20%` monthly).
2. Delete `docs/commissioni portali.md` (content preserved in this design doc).
3. No rollback data concern — worst case, the owner sets the tax rate and/or
   discount percentages back to `0` from the Settings page to restore the
   pre-change pricing instantly.

## Open Questions

None outstanding — all key decisions (scope, taxed items, settings location,
rounding behavior, HomeToGo model, portal formula, length-of-stay discount scope,
thresholds and percentages) were confirmed with the owner before writing this
design.
