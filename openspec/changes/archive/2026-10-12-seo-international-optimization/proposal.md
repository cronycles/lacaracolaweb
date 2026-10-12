## Why

The site (lacaracolaandora.com) has solid technical SEO foundations (hreflang, localized slugs, per-locale meta, JSON-LD `VacationRental`, dynamic sitemap) but the on-page copy does not target the actual search intent of international guests, and there is no content strategy to rank for low-competition informational queries. A codebase audit found concrete gaps against the target markets (priority order: DE/CH, EN, FR, IT):

- The homepage H1/hero copy is generic ("Direkt am Meer" / "Steps from the Sea") and contains no geo-intent keywords (e.g. "Andora", "Ligurien", "Italian Riviera", "Blumenriviera") that DACH and English searchers actually type.
- There is no dedicated trust-signal section addressing why to book direct instead of via an OTA (best price, direct host contact, transparent costs, secure booking).
- Real, granular distance data already exists in `config/apartment.php` (`useful_places`) but never surfaces on the homepage as reassurance for pain points DACH guests care about (parking, WiFi, ventilation, proximity to beach/station).

## What Changes

- **Phase 1 — Homepage copy**: rewrite `hero_eyebrow`/`hero_title`/`hero_subtitle` and the `seo_home_h2/p1/h3/p2` block for all 4 locales (`lang/it,en,fr,de/app.php`) to include the target keywords per market, and align `config('apartment.seo.*')` title/description with the new H1 wording. No AC is mentioned anywhere; ceiling fans + mosquito nets are used as the indirect heat reassurance instead.
- **Phase 2 — Trust badges**: add a new homepage section (data-driven, same array+lang-key pattern as `apartment.features`) with 4 trust signals (best price guarantee, direct host communication, transparent costs, secure booking), localized in all 4 locales.
- **Phase 3 — Pain-point sections**: add homepage H2/H3 sections for parking (private, paid, disclosed plainly — no exact price in copy), WiFi, and precise walking/driving distances (reusing real `useful_places` data), localized in all 4 locales.
- **Phase 5 — Technical enrichment**: add `lastmod`/`priority` to the sitemap and (if review data exists) an `AggregateRating`/`Review` block to the `VacationRental` JSON-LD.

This proposal covers the localized homepage content and technical SEO enrichment tracked in the accompanying `tasks.md`.

## Capabilities

### New Capabilities
- `homepage-localized-copy`: Keyword-targeted hero (H1/eyebrow/subtitle) and SEO text-block copy per locale, plus matching meta title/description.
- `homepage-trust-badges`: A homepage section communicating direct-booking trust signals, localized per locale.
- `homepage-pain-point-sections`: Homepage sections addressing parking, WiFi, and precise distances using real data, localized per locale.
- `seo-technical-enrichment`: Sitemap metadata (`lastmod`/`priority`) and structured-data enrichment (reviews/rating) for the vacation rental schema.

### Modified Capabilities
(none — no existing `openspec/specs/` capabilities to modify; this is the first spec-driven change in this repo)

## Impact

- **Content/i18n**: `lang/it/app.php`, `lang/en/app.php`, `lang/fr/app.php`, `lang/de/app.php` (hero + SEO text + new badge/pain-point keys), `config/apartment.php` (`seo.*` titles/descriptions, new `trust_badges` and pain-point data arrays).
- **Views**: `resources/views/public/home.blade.php` (new sections), `resources/views/layouts/app.blade.php` (unaffected — hreflang/meta plumbing already generic).
- **Structured data (Phase 5)**: `resources/views/components/schema-vacation-rental.blade.php`, `app/Http/Controllers/SitemapController.php`.
- **Docs**: update the impacted business, backend, and frontend references for homepage content and technical SEO behavior.
