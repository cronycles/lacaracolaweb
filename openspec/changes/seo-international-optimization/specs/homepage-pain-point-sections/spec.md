## ADDED Requirements

### Requirement: Pain-point sections use real distance data
The homepage SHALL include dedicated sections addressing parking, WiFi, and precise proximity to the beach/station/town, driven by a `config('apartment.pain_points')` array, reusing the real distances already present in `config('apartment.useful_places')` instead of vague claims.

#### Scenario: Distance claims are precise
- **WHEN** the proximity pain-point section is rendered
- **THEN** it SHALL state a specific walking or driving time (e.g. "X minutes on foot") sourced from existing `useful_places`/property data rather than a generic phrase like "near the beach"

#### Scenario: Parking is disclosed as paid without a fixed price
- **WHEN** the parking pain-point section is rendered
- **THEN** it SHALL state that private on-site parking is available for a fee, without stating a specific price in the marketing copy

#### Scenario: WiFi reassurance
- **WHEN** the WiFi pain-point section is rendered
- **THEN** it SHALL confirm WiFi is available at the property

#### Scenario: Heat reassurance without mentioning AC
- **WHEN** any pain-point section addresses summer comfort
- **THEN** it SHALL reference ceiling fans and/or mosquito nets and SHALL NOT mention air conditioning
