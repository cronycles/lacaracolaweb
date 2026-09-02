## ADDED Requirements

### Requirement: Configurable tax rate and gross-up items
The system SHALL allow an administrator to configure, without a code deploy, a global tax rate (percentage) and which accessory cost items (`cleaning`, `linen`, `parking`) are subject to the tax gross-up. These values are persisted via the key-value `Setting` store. When no value has ever been saved, the system SHALL fall back to a default tax rate of `21%` with `cleaning` and `linen` selected.

#### Scenario: Admin updates the tax rate
- **WHEN** an administrator saves a new tax rate value (e.g. `10%`) from the
  Settings page
- **THEN** every subsequent price quote uses `10%` to compute the tax gross-up

#### Scenario: Admin changes which items are grossed up
- **WHEN** an administrator unchecks "Biancheria" so only "Pulizie" is selected
- **THEN** subsequent quotes compute the tax gross-up on the cleaning fee only,
  excluding the linen fee from the taxable base

#### Scenario: Defaults apply when no settings exist yet
- **WHEN** no tax-related setting has ever been saved (fresh install)
- **THEN** the system computes quotes using a `21%` tax rate applied to the
  cleaning fee and linen fee

### Requirement: Tax gross-up applied to the guest-facing total
The system SHALL add, for every price quote, to the nightly-stay portion of the total an amount equal to the configured tax rate multiplied by the sum of the selected accessory costs (the "tax gross-up"), so that the final total still equals `stay + cleaning + linen` and the owner's net stay revenue is unaffected by tax owed on costs that are simply passed through to the housekeeper/laundry.

#### Scenario: Quote includes tax on cleaning and linen
- **WHEN** a quote is calculated with a cleaning fee of 100 € and a linen fee of
  100 € (200 € total accessory cost), tax rate 21%, and both items selected for
  gross-up
- **THEN** the returned breakdown includes a tax gross-up of 42 € (21% of 200 €)
  added to the stay portion, and the grand total equals
  `stay_base + 42 € + cleaning + linen` (before the €5 rounding step)

#### Scenario: Tax rate of 0% leaves totals unchanged
- **WHEN** the configured tax rate is `0%`
- **THEN** the computed total is identical to the total computed before this
  change (no gross-up added, no rounding delta since totals already resolve to
  whole numbers of cents from existing per-night rates)

#### Scenario: Item excluded from gross-up doesn't add tax
- **WHEN** the "linen" item is not selected for gross-up
- **THEN** the tax gross-up amount is computed only from the cleaning fee,
  regardless of the linen fee's value

### Requirement: Guest-facing total rounds to the nearest €5
The system SHALL round the final grand total to the nearest €5, absorbing the rounding delta into the nightly-stay portion of the breakdown so that `stay + cleaning + linen` still sums exactly to the displayed/charged total.

#### Scenario: Total rounds up
- **WHEN** the raw total (stay + tax gross-up + cleaning + linen) is 693 €
- **THEN** the final total is rounded to 695 € and the extra 2 € is added to the
  stay portion of the breakdown

#### Scenario: Total rounds down
- **WHEN** the raw total is 691 €
- **THEN** the final total is rounded to 690 € and 1 € is subtracted from the
  stay portion of the breakdown

#### Scenario: Rounding delta reconciles breakdown
- **WHEN** any rounding adjustment (positive or negative) is applied
- **THEN** `stay + cleaning + linen` computed from the returned breakdown still
  equals the returned final total exactly (no discrepancy)

### Requirement: Tax gross-up applies to the real direct-booking price
The tax gross-up and rounding SHALL apply to the actual price used across the entire direct-booking flow — not only to an admin-facing report — because the same pricing engine (`PricingQuoteService`) backs the public quote, the stored `booking_requests` price estimate, and the confirmed `Booking.income_amount`.

#### Scenario: Public quote reflects the gross-up
- **WHEN** a website visitor requests a price quote for a stay
- **THEN** the price shown to them already includes the tax gross-up and €5
  rounding

#### Scenario: Booking request estimate stores the grossed-up stay amount
- **WHEN** a guest submits a booking request
- **THEN** the persisted `estimated_stay_amount` on the `booking_requests` row
  includes the tax gross-up and rounding adjustment

#### Scenario: Confirmed booking income reflects the gross-up
- **WHEN** an administrator accepts a pending booking request
- **THEN** the resulting `Booking.income_amount` equals the grossed-up,
  rounded stay amount from the original estimate

### Requirement: Admin pricing simulator displays the tax breakdown
The admin pricing simulator SHALL display the tax gross-up amount as a distinct line item, separate from the base nightly-stay total, so the owner can see how much of the price covers taxes on accessory costs.

#### Scenario: Simulator displays the tax line item
- **WHEN** an administrator runs a price simulation for a valid date range
- **THEN** the result breakdown shows the tax gross-up amount alongside the
  existing stay/cleaning/linen/total figures
