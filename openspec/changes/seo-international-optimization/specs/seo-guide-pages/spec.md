## ADDED Requirements

### Requirement: Locale-prefixed guide routes
The system SHALL expose informational guide pages under the existing locale-prefix routing convention (`{locale}.guide.*` route names, per-locale localized slug segment), so they are automatically included in the sitemap and hreflang alternates without special-casing.

#### Scenario: Guide route follows locale convention
- **WHEN** a guide route is registered
- **THEN** its route name SHALL match `{locale}.guide.{article}` and its URI SHALL be prefixed by the locale and the locale's localized "guide" slug segment (e.g. `guide` in English, `ratgeber` in German)

#### Scenario: Guide pages appear in the sitemap automatically
- **WHEN** `/sitemap.xml` is generated
- **THEN** it SHALL include every published guide route, with no changes required to `SitemapController`

### Requirement: First batch of guide articles
The system SHALL ship at least 3 initial guide articles targeting low-competition, high-intent informational queries: reaching Andora from Nice airport, best sandy beaches on the Italian/Ligurian Riviera, and excursions in the Riviera dei Fiori (Blumenriviera).

#### Scenario: Airport transfer guide exists
- **WHEN** a visitor requests the English airport-transfer guide route
- **THEN** the page SHALL describe how to reach Andora from Nice airport and SHALL include a link back to the homepage or apartment page with keyword-rich anchor text

#### Scenario: Beaches guide exists
- **WHEN** a visitor requests the best-beaches guide route
- **THEN** the page SHALL list sandy beaches on the Italian Riviera near Andora and SHALL link back to the homepage or apartment page

#### Scenario: German excursions guide exists
- **WHEN** a visitor requests the German "Ausflüge Blumenriviera" guide route
- **THEN** the page SHALL describe excursions in the Riviera dei Fiori and SHALL link back to the homepage or apartment page
