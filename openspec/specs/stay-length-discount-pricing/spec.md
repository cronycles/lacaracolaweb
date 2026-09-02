# stay-length-discount-pricing Specification

## Purpose
TBD - created by archiving change tax-gross-up-pricing. Update Purpose after archive.
## Requirements
### Requirement: Configurable weekly and monthly discount percentages
The system SHALL allow an administrator to configure, without a code deploy, a weekly discount percentage (applied to stays of 7 or more nights) and a monthly discount percentage (applied to stays of 28 or more nights), persisted via the key-value `Setting` store. When no value has ever been saved, the system SHALL fall back to a default of `10%` weekly and `20%` monthly. The two night thresholds (7 and 28) are fixed and not configurable.

#### Scenario: Admin updates the weekly discount
- **WHEN** an administrator saves a new weekly discount value (e.g. `15%`) from
  the Settings page
- **THEN** every subsequent quote for a stay of 7–27 nights applies `15%` off the
  nightly-stay portion

#### Scenario: Defaults apply when no discount setting exists yet
- **WHEN** no discount-related setting has ever been saved (fresh install)
- **THEN** the system applies `10%` for stays of 7–27 nights and `20%` for stays
  of 28 or more nights

### Requirement: Length-of-stay discount applied to the nightly-stay portion only
For every price quote, the system SHALL apply the monthly discount to the
nightly-stay portion of the total when the stay is 28 nights or longer, otherwise
the weekly discount when the stay is 7 to 27 nights, otherwise no discount. The
higher (monthly) tier SHALL replace, not stack with, the weekly discount. The
discount SHALL NOT be applied to the cleaning fee or the linen fee.

#### Scenario: Weekly discount applies to a 7-night stay
- **WHEN** a quote is calculated for a 7-night stay with a nightly-stay subtotal of
  700 € and the weekly discount is 10%
- **THEN** the nightly-stay portion is reduced by 70 € before tax gross-up and
  rounding are applied

#### Scenario: Monthly discount replaces the weekly discount for a 28-night stay
- **WHEN** a quote is calculated for a 28-night stay
- **THEN** only the monthly discount percentage is applied to the nightly-stay
  portion — the weekly discount percentage is not additionally applied

#### Scenario: No discount below 7 nights
- **WHEN** a quote is calculated for a stay shorter than 7 nights
- **THEN** no length-of-stay discount is applied to the nightly-stay portion

#### Scenario: Cleaning and linen fees are never discounted
- **WHEN** a length-of-stay discount applies to a quote
- **THEN** the cleaning fee and linen fee amounts in the breakdown are unchanged
  by the discount

### Requirement: Length-of-stay discount applies to the real direct-booking price
The length-of-stay discount SHALL apply to the actual price used across the
entire direct-booking flow — the public quote, the stored `booking_requests`
price estimate, and the confirmed `Booking.income_amount` — using the same
pricing engine (`PricingQuoteService`) as the tax gross-up.

#### Scenario: Public quote reflects the length-of-stay discount
- **WHEN** a website visitor requests a price quote for a stay of 10 nights
- **THEN** the price shown to them already reflects the weekly discount

#### Scenario: Confirmed booking income reflects the length-of-stay discount
- **WHEN** an administrator accepts a pending booking request for a stay long
  enough to qualify for a discount
- **THEN** the resulting `Booking.income_amount` reflects the discounted stay
  amount from the original estimate

### Requirement: Admin pricing simulator displays the length-of-stay discount
The admin pricing simulator SHALL display the length-of-stay discount amount and
percentage as a distinct line item when one applies to the simulated stay.

#### Scenario: Simulator displays the discount line item
- **WHEN** an administrator runs a price simulation for a stay of 7 nights or
  longer
- **THEN** the result breakdown shows the applied discount percentage and amount

#### Scenario: No discount line item shown for short stays
- **WHEN** an administrator runs a price simulation for a stay shorter than 7
  nights
- **THEN** the result breakdown shows no length-of-stay discount line item

