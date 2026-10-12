## Why

The site has no informational pages targeting high-intent travel searches around Andora, leaving useful organic search demand to larger booking platforms. A small, fact-checked first set of localized guides can serve those queries and link readers to the apartment pages.

## What Changes

- Add a localized guide section to the existing locale-prefixed route and slug system.
- Publish an English guide to reaching Andora from Nice airport and an English guide to nearby sandy beaches on the Italian Riviera.
- Publish a German guide to excursions on the Blumenriviera.
- Ensure guide routes are discoverable through the existing sitemap and generate hreflang links only for equivalent translated pages.
- Keep article content in the existing Blade and translation/config patterns; no CMS or database content model is introduced.

## Capabilities

### New Capabilities
- `seo-guide-pages`: Localized informational guide pages with dedicated routes, accurate travel content, and links to the apartment pages.

### Modified Capabilities

None.

## Impact

- Routing configuration: `config/routes.php` and `routes/web.php`.
- New public guide controller and Blade views under `resources/views/public/guide/`.
- Localized guide content and page metadata.
- Existing sitemap, hreflang generation, and their feature tests; no special-case SEO plumbing is expected.
- Business and frontend documentation for the new public pages.