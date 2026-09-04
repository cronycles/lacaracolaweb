## MODIFIED Requirements

### Requirement: Blended nightly rate calculation

For each `PricingRule`, the system SHALL compute a suggested nightly rate per
portal that folds the linen cost for a fixed 2-guest reference and the
cleaning fee's tax gross-up (but never the cleaning fee amount itself) into
the direct nightly rate, then divides by the portal's commission — without
exposing the cleaning fee amount anywhere in this calculation, and without
treating the reference guest count as configurable.

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
- **THEN** the system rounds it to the nearest whole euro (not the €5
  rounding used for the guest-facing direct total)

## REMOVED Requirements

### Requirement: Portal rate never undercuts the direct price at default settings

**Reason**: This requirement was satisfied by defensively amortising the
apartment's full bed capacity over the minimum stay, which produced a
nightly rate far above market. The new formula deliberately no longer makes
this blanket guarantee — incremental guests beyond the 2-guest reference are
instead recovered through the portal's own extra-guest-per-night surcharge
(a real pricing mechanism the previous design didn't use), which keeps the
base rate market-realistic but does not mathematically guarantee the portal
margin is never lower than direct in every possible case (see
`ota-portal-guest-tiered-pricing`'s design.md, Risks). The simulator's
per-simulation margin indicator (see the `ota-portal-pricing` capability)
replaces this blanket guarantee with a per-case, informational check.

**Migration**: No data migration. Any code or documentation asserting this
guarantee unconditionally must be updated to reflect the new, narrower
per-simulation indicator instead.

## ADDED Requirements

### Requirement: Portal price table legend

The portal price table page SHALL display a legend, above the per-period
table, explaining what to configure on each portal and how the shown figures
were derived.

#### Scenario: Legend shows the fixed cleaning fee and its Settings source

- **WHEN** an authorized user opens the portal price table
- **THEN** the legend shows the current `pricing_cleaning_fee` value and
  states that it should be typed directly into each portal's cleaning-fee
  field, unchanged

#### Scenario: Legend shows the extra-guest surcharge and its trigger guest count

- **WHEN** an authorized user opens the portal price table
- **THEN** the legend shows the current `pricing_extra_guest_fee` value,
  states it applies identically to all 3 portals starting from the 3rd
  guest, and links to `admin/impostazioni` to edit it

#### Scenario: Legend includes per-portal notes

- **WHEN** an authorized user opens the portal price table
- **THEN** the legend notes that Airbnb and Booking.com have no per-guest
  linen field, and that HomeToGo has one but it is deliberately left unused
  to keep all 3 portals configured identically

#### Scenario: Legend documents the cleaning-fee commission trade-off

- **WHEN** an authorized user opens the portal price table
- **THEN** the legend states that the flat cleaning fee is not
  commission-grossed, so each portal keeps roughly
  `cleaning_fee × commission_rate` of it instead of passing it through to the
  owner, so this trade-off is documented for future reference if the owner
  wants to revisit it

### Requirement: Extra-guest-per-night surcharge is a single, manually-editable setting

The system SHALL expose one euro amount, `pricing_extra_guest_fee`, editable
from `admin/impostazioni`, shared identically across Airbnb, Booking.com and
HomeToGo — not derived per portal at request time.

#### Scenario: Default value applies when unset

- **WHEN** no `pricing_extra_guest_fee` setting has ever been saved
- **THEN** the system treats the value as `12` (euros per night per guest)

#### Scenario: Admin updates the extra-guest surcharge

- **WHEN** an authorized user saves a new `pricing_extra_guest_fee` value from
  `admin/impostazioni`
- **THEN** the portal price table legend and the simulator's guest-facing
  portal totals immediately reflect the new value for all 3 portals equally
