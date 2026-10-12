## Context

`lacaracolaandora.com` is a Laravel-based direct-booking site for a single vacation apartment in Andora (Liguria, Italy), targeting DE/CH, EN, FR, IT visitors in that priority order. The technical SEO layer is already solid: locale-prefixed routes with per-locale slugs (`config/routes.php`), correct `hreflang`/canonical generation (`App\Support\RouteHelper`), per-locale meta title/description (`config('apartment.seo.*')`), a `VacationRental` JSON-LD block, and a dynamically generated sitemap (`App\Http\Controllers\SitemapController`) built from all routes matching `^(it|en|fr|de)\.`.

The gap is on-page copy and content depth: the hero H1 carries no geo-intent keywords, there is no direct-booking trust section, real proximity data (`config('apartment.useful_places')`) never surfaces on the homepage, and there are no informational pages to capture long-tail organic traffic while the domain builds authority.

Property facts confirmed for this change: WiFi is available; there is no air conditioning (must never be mentioned, positively or negatively); ceiling fans exist (usable as an indirect heat/comfort reassurance alongside the existing `amenity_mosquito_nets`); private parking exists but is paid — the fee is shown later in the booking form total, so copy should disclose parking as paid without stating a fixed price.

## Goals / Non-Goals

**Goals:**
- Ground every H1/meta/content change in real property data and the existing i18n/config architecture — no new content management system.
- Keep DE/CH copy and EN copy as the highest-fidelity, most keyword-precise (top priority markets), while keeping FR/IT consistent and accurate.
- Reuse existing patterns: array-of-`['icon' => ..., 'key' => ...]` in `config/apartment.php` + translation strings in `lang/{locale}/app.php`, exactly like `apartment.features` and `apartment.amenities` already do.

**Non-Goals:**
- No redesign of the visual system/CSS framework — new sections reuse existing section/container/card CSS classes already used elsewhere in `home.blade.php`.
- No paid parking price disclosure in static copy (Phase 3) — the exact fee stays a booking-form/quote concern, not a marketing-copy concern.
- Phase 5 review/`AggregateRating` schema is only implemented if real review data already exists in the database; otherwise it's descoped to a follow-up task with an explicit open question (see below).

## Decisions

- **Content lives in `lang/*/app.php` + `config/apartment.php`, not a new table.** Consistent with the rest of the site (rules, amenities, useful places are all config-driven). Avoids introducing a CMS for a single-property site with infrequent content changes.
- **Trust badges and pain-point sections are new arrays in `config/apartment.php`** (`trust_badges`, `pain_points`), rendered with `@foreach` exactly like `apartment.features`, so no new Blade partial architecture is introduced.
- **Trust badges only render in internal direct-booking form mode.** The section is wrapped in `@unless ($bookingMode === 'external' && $bookingExternalUrl)` — the exact condition already used lower on the page to pick between the external-platform CTA and the internal booking form — because the "book direct with us" messaging (best price, direct host contact, transparent costs, secure booking) does not hold when bookings are redirected to a third-party platform. It is therefore its own standalone `<section>`, not merged with the (always-shown) feature-highlights section, so the two can be shown/hidden independently.
- **No AC amenity is added or implied.** Comfort reassurance for summer heat is conveyed via a new `feature_ceiling_fans` (or extending existing `amenity_mosquito_nets` copy) rather than inventing an amenity that doesn't exist.
- **Parking copy stays price-free.** Homepage/pain-point copy says private parking is available on-site for a fee ("a pagamento" / "gegen Gebühr" / "with a fee" / "avec supplément"), consistent with the trust badge being reframed as "transparent costs" rather than "no hidden fees" (the literal English phrase from the original brief would contradict a paid-parking disclosure).

## Risks / Trade-offs

- **Keyword-heavy H1s can read as unnatural** if over-optimized → Mitigation: keep H1 to one natural sentence per locale (e.g. "Ferienwohnung in Andora, Ligurien – Direkt am Meer"), push secondary keywords (Blumenriviera, Italian Riviera, self-catering) into the eyebrow/subtitle and the existing `seo_home_h2/p1` block instead of stuffing the H1.
- **Reframing "No Hidden Fees" as "transparent costs"** slightly weakens the trust-badge wording suggested in the original brief → Mitigation: still communicates the same reassurance (no surprises) without contradicting the paid-parking fact; copy explicitly says parking is paid.
- **Phase 5 `AggregateRating` schema risk of being flagged by Google** if not backed by genuinely displayed reviews on the page → Mitigation: only add if the existing `reviews` page already renders real review content/ratings publicly.

## Migration Plan

1. **Phase 1 (done)**: updated `lang/*/app.php` hero + SEO-text keys and `config/apartment.php` `seo.*` title/description for all 4 locales; no schema/route changes, fully reversible via git.
2. **Phase 2 (done)**: added `trust_badges` config array + lang keys + a standalone homepage section, conditionally rendered only in internal direct-booking form mode; also added a heading (`home_features_title`) to the previously headless feature-highlights section and gave both sections a consistent, mobile-friendly `.home-features` grid (2 columns from the smallest viewport, 4 from 1024px) instead of the oversized default `.section` padding.
3. **Phase 3**: add `pain_points` config array (parking/wifi/distances) + lang keys + new homepage section, reusing `useful_places` distance data.
4. **Phase 5**: add sitemap `lastmod`/`priority` and expose rating/review schema only from real reviews displayed publicly.

Each scope is independently reversible; no data migrations are required.

## Open Questions

- Phase 5: resolved. `ReviewsController` exposes active reviews linked to bookings, and the public page displays their ratings and localized text. Production contains real review rows; the `VacationRental` JSON-LD adds aggregate and individual review data only on that page and only from its displayed collection. An empty local database emits no review-rating schema.
