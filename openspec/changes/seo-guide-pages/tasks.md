## 1. Localized routing

- [ ] 1.1 Add a localized `guide` section slug for each supported locale in `config/routes.php`.
- [ ] 1.2 Register named English and German article routes inside the existing locale-prefixed route groups.
- [ ] 1.3 Add a public guide controller and article Blade views under `resources/views/public/guide/` using existing page and layout conventions.

## 2. Search discovery and documentation

- [ ] 2.1 Verify published guide routes appear in `/sitemap.xml` and that hreflang links appear only for equivalent translated routes.
- [ ] 2.2 Add focused feature tests for guide routes, sitemap inclusion, and hreflang behavior.
- [ ] 2.3 Update the business and frontend documentation for the new public guide pages.