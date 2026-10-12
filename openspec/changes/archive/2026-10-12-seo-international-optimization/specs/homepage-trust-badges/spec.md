## ADDED Requirements

### Requirement: Direct-booking trust badges section
The homepage SHALL render a trust-signals section as its own standalone section, listing direct-booking advantages over third-party OTAs, localized in all 4 languages, driven by a `config('apartment.trust_badges')` array in the same `['icon' => ..., 'key' => ...]` shape as `apartment.features`.

#### Scenario: Trust badges render on the homepage
- **WHEN** the homepage is loaded in any of the 4 locales with the internal direct-booking form active
- **THEN** a trust-badges section SHALL be visible containing at least: best price guarantee, direct host communication, transparent costs, and secure booking

#### Scenario: Costs badge does not claim "no fees"
- **WHEN** the trust badge about pricing/costs is rendered
- **THEN** its copy SHALL communicate cost transparency (no surprise charges) and SHALL NOT claim there are no additional fees, since paid parking exists

### Requirement: Trust badges only shown for the internal direct-booking flow
The trust-badges section SHALL only render when the site's booking mode is the internal request form, not when bookings are redirected to an external platform, since the "book direct with us" messaging does not apply to an external redirect.

#### Scenario: Hidden when booking mode is external
- **WHEN** the admin-configured booking mode is `external` and an external booking URL is set
- **THEN** the trust-badges section SHALL NOT render, while the apartment feature highlights section SHALL still render
