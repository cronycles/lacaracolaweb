## ADDED Requirements

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
The system SHALL compute a suggested listing total and average-per-night price for each configured portal from a given direct-site price (already including the tax gross-up and any length-of-stay discount from the `tax-gross-up-pricing` and `stay-length-discount-pricing` capabilities), such that after the portal deducts its own commission and its own length-of-stay discount (assumed configured identically to the `stay-length-discount-pricing` settings) the owner nets the same amount as the direct-site price: `portal_total = round(direct_total / ((1 - length_discount_rate) * (1 - commission_rate)))` and `portal_avg_per_night = round(portal_total / nights)`, where `length_discount_rate` follows the same 7-night/28-night tiers as the direct site.

#### Scenario: Airbnb suggested price
- **WHEN** the direct-site total for a stay under 7 nights is 100 € and the
  Airbnb commission rate is 15.5%
- **THEN** the suggested Airbnb total is 118.34 € (100 / 0.845, rounded)

#### Scenario: Booking.com suggested price
- **WHEN** the direct-site total for a stay under 7 nights is 100 € and the
  Booking.com commission rate is 16.5%
- **THEN** the suggested Booking.com total is 119.76 € (100 / 0.835, rounded)

#### Scenario: HomeToGo suggested price
- **WHEN** the direct-site total for a stay under 7 nights is 100 € and the
  HomeToGo commission rate is 15.5%
- **THEN** the suggested HomeToGo total is 118.34 € (100 / 0.845, rounded)

#### Scenario: Suggested price accounts for the portal's own length-of-stay discount
- **WHEN** the simulated stay is 10 nights (qualifying for the 10% weekly
  discount) and a portal's commission rate is 15.5%
- **THEN** the suggested total for that portal is computed as
  `direct_total / (0.90 * 0.845)`, so that after the portal applies its own 10%
  weekly discount and 15.5% commission, the owner still nets the direct-site total

#### Scenario: Portal prices update when the direct price changes
- **WHEN** an administrator changes the simulated dates, guest count, or the tax
  settings such that the direct-site total changes
- **THEN** the suggested prices for all 3 portals are recomputed from the new
  direct-site total

### Requirement: Admin pricing simulator displays suggested portal prices
The admin pricing simulator SHALL display, alongside the existing direct-price breakdown, the suggested total and average-per-night price for each of the 3 configured portals.

#### Scenario: Simulator shows portal price cards
- **WHEN** an administrator runs a price simulation for a valid date range
- **THEN** the result includes the suggested Airbnb, Booking.com and HomeToGo
  total and average-per-night prices for that same stay
