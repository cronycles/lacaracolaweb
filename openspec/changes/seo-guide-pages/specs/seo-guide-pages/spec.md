## ADDED Requirements

### Requirement: Locale-prefixed guide routes
The system SHALL expose informational guide pages under the existing locale-prefix routing convention, with a localized guide section slug and route names that preserve article identity across equivalent translations.

#### Scenario: Guide route follows locale convention
- **WHEN** a guide article route is registered
- **THEN** its URI SHALL include the locale prefix and that locale's configured guide-section slug, and its route name SHALL identify the locale and article

#### Scenario: Hreflang only links equivalent translations
- **WHEN** a guide page has an equivalent article in another supported locale
- **THEN** the page SHALL expose an hreflang alternate for that equivalent route and SHALL NOT link to a different article merely because it is also a guide

### Requirement: Initial guide articles
The system SHALL publish an initial set of three informational articles: an English guide to reaching Andora from Nice airport, an English guide to sandy beaches on the Italian Riviera near Andora, and a German guide to excursions on the Blumenriviera.

#### Scenario: Airport transfer guide exists
- **WHEN** a visitor requests the English Nice-airport guide
- **THEN** the page SHALL explain verified options for reaching Andora from Nice airport and include a link to the home or apartment page

#### Scenario: Beaches guide exists
- **WHEN** a visitor requests the English beaches guide
- **THEN** the page SHALL describe verified sandy-beach options near Andora on the Italian Riviera and include a link to the home or apartment page

#### Scenario: German excursions guide exists
- **WHEN** a visitor requests the German Blumenriviera excursions guide
- **THEN** the page SHALL describe verified excursion options in the region and include a link to the home or apartment page

#### Scenario: Guide content does not invent travel facts
- **WHEN** an article gives transport, distance, price, schedule, or beach information
- **THEN** each specific claim SHALL be supported by a verifiable source, and unverified specifics SHALL be omitted

### Requirement: Guides are discoverable by search engines
Every published guide SHALL be included in the existing sitemap, and its hreflang metadata SHALL represent only equivalent localized versions that are actually available.

#### Scenario: Published guide appears in sitemap
- **WHEN** the sitemap is generated
- **THEN** it SHALL include every eligible localized guide GET route without guide-specific sitemap logic

#### Scenario: Untranslated guide has no false hreflang alternate
- **WHEN** a guide has no equivalent translated route
- **THEN** the page SHALL NOT emit hreflang alternates pointing to unrelated guide articles