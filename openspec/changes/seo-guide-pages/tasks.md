## 1. Localized routing

- [ ] 1.1 Add a localized `guide` section slug for each supported locale in `config/routes.php`.
- [ ] 1.2 Register named English and German article routes inside the existing locale-prefixed route groups.
- [ ] 1.3 Add a public guide controller and article Blade views under `resources/views/public/guide/` using existing page and layout conventions.

## 2. Initial guide content

- [ ] 2.1 Write the English guide to reaching Andora from Nice airport, using verified transport information and linking to the home or apartment page.
- [ ] 2.2 Write the English guide to sandy beaches near Andora on the Italian Riviera, using verified information and linking to the home or apartment page.
- [ ] 2.3 Write the German `Ausflüge Blumenriviera` guide, using verified excursion information and linking to the home or apartment page.

## 3. Search discovery and documentation

- [ ] 3.1 Verify published guide routes appear in `/sitemap.xml` and that hreflang links appear only for equivalent translated routes.
- [ ] 3.2 Add focused feature tests for guide routes, sitemap inclusion, and hreflang behavior.
- [ ] 3.3 Update the business and frontend documentation for the new public guide pages.