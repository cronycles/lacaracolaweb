## ADDED Requirements

### Requirement: Portal price table page
The system SHALL provide a read-only admin page listing every `PricingRule`
period alongside its suggested blended nightly rate for Airbnb, Booking.com
and HomeToGo, without requiring any separate data entry from the existing
`pricing_rules` table.

#### Scenario: Table lists the same periods as the pricing rules page
- **WHEN** an authorized user opens the portal price table
- **THEN** it lists the same rows (periods) as `admin/prezzi`, ordered the same
  way, with one additional column per portal showing a suggested nightly rate

#### Scenario: Table updates automatically when a pricing rule changes
- **WHEN** a `PricingRule`'s `price_per_night` is edited and saved
- **THEN** the portal price table reflects the new suggested nightly rates
  without any additional manual step

#### Scenario: Access restricted to the pricing permission
- **WHEN** a user without the `manage_pricing` permission requests the portal
  price table page
- **THEN** the system redirects them away, consistent with other
  `manage_pricing`-gated pages

### Requirement: Blended nightly rate calculation
For each `PricingRule`, the system SHALL compute a suggested nightly rate per
portal that folds cleaning fee, linen fee and the tax gross-up into a single
number, using a fixed reference stay length and a fixed reference guest count,
without exposing separate cost line items.

#### Scenario: Reference stay length is the configured minimum nights
- **WHEN** computing the suggested nightly rate for a pricing rule
- **THEN** the system uses `config('apartment.booking.min_nights')` as the
  number of nights over which cleaning, linen and the tax gross-up are
  amortised, and applies no weekly/monthly length discount to this
  calculation

#### Scenario: Reference guest count is the apartment's bed capacity
- **WHEN** computing the linen fee portion of the suggested nightly rate
- **THEN** the system uses `config('apartment.specs.beds')` as the guest
  count, regardless of how many guests any real booking might have

#### Scenario: Tax gross-up applied consistently with the direct site
- **WHEN** computing the reference total for a pricing rule
- **THEN** the system applies the same `pricing_tax_rate` and
  `pricing_tax_gross_up_items` settings, and the same taxable cleaning/linen
  selection logic, as `PricingQuoteService::calculate()`

#### Scenario: Portal commission applied per portal
- **WHEN** deriving each portal's suggested nightly rate from the reference
  total
- **THEN** the system divides by `(1 - commission_rate)` using that portal's
  `pricing_commission_*` setting, consistent with `OtaPortalPricingService`

#### Scenario: Final rate rounded to the nearest euro
- **WHEN** the suggested nightly rate for a portal has been computed
- **THEN** the system rounds it to the nearest whole euro (not the €5
  rounding used for the guest-facing direct total)

### Requirement: Portal rate never undercuts the direct price
The suggested portal nightly rate SHALL never be lower, for any real booking
of the same length and any guest count up to the apartment's bed capacity,
than the equivalent direct-site total computed by `PricingQuoteService`.

#### Scenario: Full-occupancy booking via a portal is never cheaper than direct
- **WHEN** a guest books the maximum number of guests the apartment holds, for
  the reference stay length, through a portal at its suggested nightly rate
- **THEN** the total the owner receives net of commission is greater than or
  equal to what the same booking would net on the direct site
