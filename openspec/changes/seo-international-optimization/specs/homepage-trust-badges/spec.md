## ADDED Requirements

### Requirement: Direct-booking trust badges section
The homepage SHALL render a trust-signals section between the hero and the feature highlights, listing direct-booking advantages over third-party OTAs, localized in all 4 languages, driven by a `config('apartment.trust_badges')` array in the same `['icon' => ..., 'key' => ...]` shape as `apartment.features`.

#### Scenario: Trust badges render on the homepage
- **WHEN** the homepage is loaded in any of the 4 locales
- **THEN** a trust-badges section SHALL be visible containing at least: best price guarantee, direct host communication, transparent costs, and secure booking

#### Scenario: Costs badge does not claim "no fees"
- **WHEN** the trust badge about pricing/costs is rendered
- **THEN** its copy SHALL communicate cost transparency (no surprise charges) and SHALL NOT claim there are no additional fees, since paid parking exists
