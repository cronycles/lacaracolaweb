## MODIFIED Requirements

### Requirement: Blended nightly rate calculation

For each `PricingRule`, the system SHALL compute a suggested nightly rate per
portal that folds the linen cost for a fixed 2-guest reference and the
cleaning fee's tax gross-up (but never the cleaning fee amount itself) into
the direct nightly rate, divides by the portal's commission, then inflates
the result by the configurable extra-margin percentage
(`pricing_portal_extra_margin_percent`) — without exposing the cleaning fee
amount anywhere in this calculation, and without treating the reference
guest count as configurable.

#### Scenario: Extra margin inflates the commission-grossed rate before rounding

- **WHEN** computing the suggested nightly rate for any portal
- **THEN** the system multiplies the commission-grossed rate (nightly rate
  plus the 2-guest linen/tax recovery, divided by `1 - commission_rate`) by
  `(1 + pricing_portal_extra_margin_percent)` before rounding to the nearest
  euro

#### Scenario: Reference guest count is fixed at 2, not configurable

- **WHEN** computing the linen portion of the suggested nightly rate for any
  `PricingRule`
- **THEN** the system always uses 2 as the guest count, regardless of the
  apartment's real bed capacity, and no Setting exists to change this value

#### Scenario: Reference stay length comes from the minimum-stay setting

- **WHEN** computing how many nights the 2-guest linen recovery is amortised
  over
- **THEN** the system uses `pricing_min_nights` (falling back to
  `config('apartment.booking.min_nights')`, 3, when unset) — the same value
  that governs the site's real minimum bookable stay, not a separate
  portal-only reference value

#### Scenario: Cleaning fee amount is excluded from the nightly rate, but its tax gross-up is recovered

- **WHEN** computing the suggested nightly rate for any portal
- **THEN** the cleaning fee amount (`pricing_cleaning_fee`) itself does not
  contribute to the computed figure — it is only ever shown as a separate,
  flat reference value for the owner to type into each portal's own
  cleaning-fee field — but the tax gross-up on that amount is still recovered
  alongside the linen recovery, so a 2-guest, minimum-stay portal booking
  does not net noticeably less than the equivalent direct booking

#### Scenario: Linen cost and the cleaning fee's tax are grossed-up together

- **WHEN** computing the recoverable amount for the 2-guest reference
- **THEN** the system applies the same `pricing_tax_rate` and the
  `cleaning`/`linen` entries of `pricing_tax_gross_up_items` used by
  `PricingQuoteService` to the reference linen cost and the cleaning fee
  amount together, so disabling either toggle site-wide also affects this
  calculation, while the cleaning fee amount itself still never appears
  added to the nightly rate

#### Scenario: Portal commission applied per portal

- **WHEN** deriving each portal's suggested nightly rate
- **THEN** the system adds the per-night linen recovery amount to the
  `PricingRule`'s `price_per_night` and divides the sum by
  `(1 - commission_rate)` using that portal's `pricing_commission_*` setting

#### Scenario: Final rate rounded to the nearest euro

- **WHEN** the suggested nightly rate for a portal has been computed
  (including the extra-margin multiplier)
- **THEN** the system rounds it to the nearest whole euro (not the €5
  rounding used for the guest-facing direct total)
