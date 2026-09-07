## ADDED Requirements

### Requirement: Configurable extra margin on portal prices

The system SHALL allow an administrator to configure, without a code
deploy, a single "extra margin" percentage applied identically to all 3
portals' suggested prices, persisted via the key-value `Setting` store under
`pricing_portal_extra_margin_percent`. When no value has ever been saved,
the system SHALL default to `5%`. This margin SHALL widen the gap between
the portal price and the direct-site price at every guest count, including
counts at or below the 2-guest reference where the extra-guest surcharge
does not apply.

#### Scenario: Admin sets an extra margin

- **WHEN** an administrator saves a new value (e.g. `10%`) for the portal
  extra margin from the Settings page
- **THEN** every subsequently suggested portal price (both the period table's
  nightly rate and the simulator's guest-facing total) reflects `10%`

#### Scenario: Default applies when unset

- **WHEN** no extra-margin setting has ever been saved (fresh install)
- **THEN** suggested portal prices apply a `5%` extra margin

#### Scenario: Margin widens the gap even at the 2-guest reference count

- **WHEN** the simulator computes a portal's guest-facing total for exactly
  2 guests (where the extra-guest surcharge is `0`)
- **THEN** the guest-facing total is still higher than it would be with the
  margin set to `0%`, by the configured percentage of the commission-grossed
  base-stay subtotal

#### Scenario: Margin does not affect the flat cleaning fee or extra-guest surcharge

- **WHEN** the system computes a portal's guest-facing total
- **THEN** the flat `pricing_cleaning_fee` and `pricing_extra_guest_fee`
  amounts are added to the total unchanged, exactly as configured, without
  the extra margin being applied to either of them

## MODIFIED Requirements

### Requirement: Suggested portal listing prices computed from the direct price

For a simulated stay, the system SHALL compute, per portal, the real
guest-facing total a guest would pay via that portal's own pricing fields
(base nightly rate, extra-guest surcharge, cleaning fee), inflated by the
configurable extra margin, and the owner's approximate net revenue after
that portal's commission, reusing the same base-nightly-rate formula as the
`ota-portal-price-table` capability rather than grossing up the
already-computed direct total.

#### Scenario: Guest-facing total combines the 3 portal pricing fields and the extra margin

- **WHEN** the simulator computes a portal's suggested price for a stay of N
  nights and G guests
- **THEN** the guest-facing total equals: the sum of that portal's base
  nightly rate across the N nights (commission-grossed and inflated by the
  configured extra margin, then discounted the same way the direct site
  discounts stays of 7+/28+ nights), plus the extra-guest surcharge
  multiplied by `max(0, G - 2)` guests and N nights, plus the flat cleaning
  fee

#### Scenario: Owner net is computed from the margin-inflated guest-facing total

- **WHEN** the simulator has computed a portal's guest-facing total
  (including the extra margin)
- **THEN** it also computes the owner's approximate net revenue as
  `guest_facing_total × (1 - commission_rate)`
