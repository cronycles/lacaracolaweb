## Context

The application uses Laravel routes prefixed by locale, with localized section slugs in `config/routes.php`. `App\Support\RouteHelper::alternates()` derives hreflang links from equivalent localized route names, and `SitemapController` includes eligible localized GET routes automatically. There are currently no guide routes or guide views.

The initial content scope is intentionally small: two English guides and one German guide. Their factual details must be checked before publication; the change does not authorize inventing transport schedules, fares, travel times, or beach characteristics.

## Goals / Non-Goals

**Goals:**
- Add the guide section to the existing localized route and slug conventions.
- Publish the English Nice-airport transfer and Italian-Riviera beaches guides, plus the German Blumenriviera excursions guide.
- Link each article to the relevant home or apartment page.
- Reuse automatic sitemap inclusion and emit hreflang only for genuinely equivalent translated pages.

**Non-Goals:**
- No CMS, database tables, or admin editing workflow.
- No Italian or French article translations in the initial batch.
- No airport-transfer translation into German in the initial batch.
- No changes to unrelated pages' SEO metadata or structured data.

## Decisions

- **Use the existing locale route map.** Add a `guide` section slug for every supported locale and register article routes inside the existing locale-prefixed group. This keeps route names compatible with sitemap discovery and `RouteHelper::alternates()`.
- **Keep article content static and page-specific.** Use the existing controller/Blade architecture rather than introducing a CMS or generalized content model; each article remains easy to review for factual accuracy.
- **Register only locales that have an article.** The route segment is localized for all supported locales, but the first release contains only EN and DE article routes. Do not create hreflang links between different articles just because they share a topic category; an alternate requires an equivalent translation.
- **Verify travel facts before writing final copy.** Avoid unsupported exact durations, prices, schedules, or amenity claims. Link each article to the home or apartment route using the existing localized route helper.

## Risks / Trade-offs

- **Unverified travel details can mislead visitors.** Confirm details against authoritative sources before publication; omit specifics that cannot be confirmed.
- **Only EN and DE guide routes ship initially.** Italian and French visitors will not see equivalent guide pages; hreflang must not imply otherwise.
- **Static articles require code changes to update.** This is accepted for a small initial set with infrequent edits; a CMS remains out of scope.

## Migration Plan

1. Add the localized guide section slugs, article routes, controller, and Blade views.
2. Verify article content, navigation links, sitemap inclusion, and hreflang behavior.
3. Roll back by removing the added routes, views, controller, and slug entries; no data migration is required.

## Open Questions

- Confirm authoritative sources and final wording for airport connections, nearby sandy beaches, and Blumenriviera excursions before publication.