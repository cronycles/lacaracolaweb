# ota-portal-pricing Specification

## Purpose

TBD - created by archiving change tax-gross-up-pricing. Update Purpose after archive.

## Requirements

### Requirement: Configurable portal commission rates

The system SHALL allow an administrator to configure, without a code deploy, a commission percentage for each external booking platform the apartment is listed on (Airbnb, Booking.com, HomeToGo), persisted via the key-value `Setting` store. When no value has ever been saved, the system SHALL fall back to default rates of `15.5%` (Airbnb), `16.5%` (Booking.com) and `15.5%` (HomeToGo, "Flex" model).

#### Scenario: Admin updates a portal commission

- **WHEN** an administrator saves a new commission rate for Booking.com (e.g.
  `18%`) from the Settings page
- **THEN** every subsequent suggested Booking.com price uses `18%`

#### Scenario: Defaults apply when unset

- **WHEN** no commission setting has ever been saved (fresh install)
- **THEN** suggested prices use `15.5%` for Airbnb, `16.5%` for Booking.com and
  `15.5%` for HomeToGo

### Requirement: Suggested portal listing prices computed from the direct price

For a simulated stay, the system SHALL compute, per portal, the real guest-facing total a guest would pay via that portal's own pricing fields (base nightly rate, extra-guest surcharge, cleaning fee) and the owner's approximate net revenue after that portal's commission, reusing the same base-nightly-rate formula as the `ota-portal-price-table` capability rather than grossing up the already-computed direct total.

#### Scenario: Guest-facing total combines the 3 portal pricing fields

- **WHEN** the simulator computes a portal's suggested price for a stay of N
  nights and G guests
- **THEN** the guest-facing total equals: the sum of that portal's base
  nightly rate across the N nights (discounted the same way the direct site
  discounts stays of 7+/28+ nights), plus the extra-guest surcharge
  multiplied by `max(0, G - 2)` guests and N nights, plus the flat cleaning
  fee

#### Scenario: Owner net is computed from the guest-facing total

- **WHEN** the simulator has computed a portal's guest-facing total
- **THEN** it also computes the owner's approximate net revenue as
  `guest_facing_total × (1 - commission_rate)`

#### Scenario: Portal prices update when the direct price changes

- **WHEN** an administrator changes the simulated dates, guest count, or any
  of the tax/cleaning/linen/commission/extra-guest settings
- **THEN** the guest-facing total and owner net for all 3 portals are
  recomputed accordingly

### Requirement: Admin pricing simulator displays suggested portal prices

The admin pricing simulator SHALL display, alongside the existing direct-price breakdown, each portal's guest-facing total, the owner's approximate net revenue, and an indicator comparing that net revenue to the direct-site total for the same simulated stay.

#### Scenario: Simulator shows portal price cards

- **WHEN** an administrator runs a price simulation for a valid date range
- **THEN** the result includes, for Airbnb, Booking.com and HomeToGo, the
  guest-facing total, the owner's approximate net revenue, and that portal's
  commission rate

#### Scenario: Margin indicator shown per portal

- **WHEN** a portal's approximate owner net revenue is greater than or equal
  to the direct-site total for the same simulated stay
- **THEN** the simulator shows a positive indicator (e.g. ✅) for that portal;
  otherwise it shows a cautionary indicator (e.g. ⚠️), without blocking or
  altering the displayed figures either way — this indicator is informational
  only, since the cleaning fee is not commission-grossed in the underlying
  formula (see `ota-portal-guest-tiered-pricing`'s design.md, Risks)
