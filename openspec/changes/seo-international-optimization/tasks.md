## 1. Phase 1 — Homepage localized copy (hero + meta + SEO text block)

- [x] 1.1 Rewrite `hero_eyebrow`/`hero_title`/`hero_subtitle` in `lang/de/app.php` with DACH geo-intent keywords ("Andora", "Ligurien"/"Ligurische Riviera"), no AC mention.
- [x] 1.2 Rewrite `hero_eyebrow`/`hero_title`/`hero_subtitle` in `lang/en/app.php` with "Andora" + "Italian Riviera", no AC mention.
- [x] 1.3 Rewrite `hero_eyebrow`/`hero_title`/`hero_subtitle` in `lang/fr/app.php` consistent with the new positioning.
- [x] 1.4 Rewrite `hero_eyebrow`/`hero_title`/`hero_subtitle` in `lang/it/app.php` consistent with the new positioning.
- [x] 1.5 Update `seo_home_h2`/`seo_home_p1`/`seo_home_h3`/`seo_home_p2` in `lang/de/app.php` to include "Blumenriviera" and "Ferienwohnung Andora".
- [x] 1.6 Update `seo_home_h2`/`seo_home_p1`/`seo_home_h3`/`seo_home_p2` in `lang/en/app.php` to include "self-catering" and "Italian Riviera".
- [x] 1.7 Update `seo_home_h2`/`seo_home_p1`/`seo_home_h3`/`seo_home_p2` in `lang/fr/app.php` and `lang/it/app.php` for consistency with the new keyword set.
- [x] 1.8 Update `config('apartment.seo.*.title')` and `.description` for all 4 locales in `config/apartment.php` to match the new hero keywords.
- [x] 1.9 Manually verify rendered `<title>`, meta description, and H1 for `/it/`, `/en/`, `/fr/`, `/de/` in a local browser check.

## 2. Phase 2 — Trust badges section

- [x] 2.1 Add `trust_badges` array to `config/apartment.php` (icon + lang key, same shape as `apartment.features`).
- [x] 2.2 Add trust-badge translation keys to `lang/{it,en,fr,de}/app.php` (best price guarantee, direct host communication, transparent costs, secure booking).
- [x] 2.3 Render the trust-badges section in `resources/views/public/home.blade.php` as its own section, shown only when the internal direct-booking form is active (`@unless ($bookingMode === 'external' && $bookingExternalUrl)`).
- [x] 2.4 Verify the "costs" badge communicates transparency without claiming "no fees" (paid parking exists).

## 3. Phase 3 — Pain-point sections (parking, WiFi, distances)

- [ ] 3.1 Add `pain_points` array to `config/apartment.php`, reusing real distance data already present in `apartment.useful_places`.
- [ ] 3.2 Add pain-point translation keys to `lang/{it,en,fr,de}/app.php` for parking (paid, no fixed price stated), WiFi availability, and precise proximity to beach/station.
- [ ] 3.3 Add a heat/comfort reassurance referencing ceiling fans and/or mosquito nets (no AC mention) to the relevant pain-point copy.
- [ ] 3.4 Render the pain-point sections in `resources/views/public/home.blade.php`.

## 4. Phase 4 — Guide pages

- [ ] 4.1 Add a `guide` slug entry per locale in `config/routes.php` (e.g. `guide` for EN, `ratgeber` for DE).
- [ ] 4.2 Register `{locale}.guide.*` routes in `routes/web.php` following the existing locale-prefixed group pattern.
- [ ] 4.3 Create `App\Http\Controllers\Public\GuideController` (or equivalent) and Blade views under `resources/views/public/guide/`.
- [ ] 4.4 Write the "how to reach Andora from Nice airport" article (EN, plus DE if in scope) with a link back to home/apartment.
- [ ] 4.5 Write the "best sandy beaches on the Italian Riviera" article (EN) with a link back to home/apartment.
- [ ] 4.6 Write the "Ausflüge Blumenriviera" article (DE) with a link back to home/apartment.
- [ ] 4.7 Verify new guide routes appear automatically in `/sitemap.xml` and in hreflang alternates where a translated counterpart exists.

## 5. Phase 5 — Technical enrichment

- [ ] 5.1 Add `<lastmod>` and `<priority>` to `App\Http\Controllers\SitemapController`, prioritizing homepage/apartment over secondary pages.
- [ ] 5.2 Investigate whether `App\Models\Review`/`ReviewsController` expose real, publicly displayed ratings.
- [ ] 5.3 If real review data exists, add `AggregateRating`/`Review` to `components/schema-vacation-rental.blade.php`; otherwise leave as a documented open item.

## 6. Documentation

- [x] 6.1 Update `docs/business-doc.mdc` and `docs/specific-tech-frontend-doc.mdc` for Phases 1–2 (hero copy pattern, trust-badges conditional visibility, feature-highlights heading, `.home-features` grid convention).
- [ ] 6.2 Update the same docs for Phases 3–5 (pain-point sections, guide pages, sitemap/schema enrichment) once implemented.
