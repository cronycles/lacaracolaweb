## ADDED Requirements

### Requirement: Configurable fixed accessory costs and minimum stay length

The system SHALL allow an administrator to configure, without a code deploy,
the cleaning fee, the linen fee per person, and the minimum bookable stay
length, persisted via the key-value `Setting` store, falling back to their
current `config/apartment.php` values (100 €, 25 €, 3 nights respectively)
when unset.

#### Scenario: Admin updates the cleaning fee

- **WHEN** an administrator saves a new `pricing_cleaning_fee` value from
  `admin/impostazioni`
- **THEN** every subsequent direct-site price quote, portal nightly-rate
  calculation, and portal price table legend uses the new value

#### Scenario: Admin updates the linen fee per person

- **WHEN** an administrator saves a new `pricing_linen_fee_per_person` value
  from `admin/impostazioni`
- **THEN** every subsequent direct-site price quote and portal nightly-rate
  calculation uses the new value

#### Scenario: Admin updates the minimum stay length

- **WHEN** an administrator saves a new `pricing_min_nights` value from
  `admin/impostazioni`
- **THEN** the direct-site booking form, availability/quote validation, and
  the portal nightly-rate calculation's reference stay length all use the new
  value, instead of the previously deploy-only `config('apartment.booking.min_nights')`

#### Scenario: Defaults apply when no settings exist yet

- **WHEN** none of these 3 settings has ever been saved (fresh install)
- **THEN** the system behaves exactly as before this change: 100 € cleaning
  fee, 25 € linen fee per person, 3-night minimum stay
