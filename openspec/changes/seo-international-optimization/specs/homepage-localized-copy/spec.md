## ADDED Requirements

### Requirement: Locale-specific hero copy with geo-intent keywords
The homepage hero (`hero_eyebrow`, `hero_title`, `hero_subtitle` in `lang/{locale}/app.php`) SHALL include the property's core geo-intent keywords for that locale's target market, without mentioning air conditioning in any locale.

#### Scenario: German/Swiss visitor reads the hero
- **WHEN** a visitor loads `/de/` with the German locale active
- **THEN** the rendered H1 SHALL contain both "Andora" and "Ligurien" (or "Ligurische Riviera")

#### Scenario: English visitor reads the hero
- **WHEN** a visitor loads `/en/` with the English locale active
- **THEN** the rendered H1 SHALL contain "Andora" and "Italian Riviera"

#### Scenario: No air conditioning claim anywhere
- **WHEN** the hero or SEO text block is rendered in any of the 4 locales
- **THEN** the text SHALL NOT mention air conditioning, cooling, or its absence

### Requirement: SEO text block reinforces market-specific keyword variants
The existing `seo_home_h2`/`seo_home_p1`/`seo_home_h3`/`seo_home_p2` translation keys SHALL include secondary target keywords per market (e.g. German "Blumenriviera" and "Ferienwohnung Andora"; English "self-catering" and "Italian Riviera") in addition to the geo terms already present.

#### Scenario: German SEO text includes "Blumenriviera"
- **WHEN** the homepage SEO text section is rendered with the German locale active
- **THEN** the text SHALL contain the term "Blumenriviera" at least once

#### Scenario: English SEO text includes "self-catering"
- **WHEN** the homepage SEO text section is rendered with the English locale active
- **THEN** the text SHALL contain the term "self-catering" at least once

### Requirement: Meta title and description match the hero keywords
`config('apartment.seo.{locale}.title')` and `.description` SHALL reflect the same primary geo-intent keywords used in that locale's H1, for all 4 locales.

#### Scenario: Title/H1 keyword consistency
- **WHEN** the `<title>` tag and the rendered H1 are compared for a given locale
- **THEN** both SHALL contain the same primary location keyword (e.g. "Andora" and the locale's term for "Liguria"/"Italian Riviera")
