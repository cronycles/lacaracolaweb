# La Caracola Web — Project Instructions

## Project Overview
Short-rental vacation apartment website for "La Caracola" in Marina di Andora (Savona, Liguria).
Stack: Laravel + MySQL + TypeScript + PostCSS + Vite + ESLint.

## Documentation Index
Before implementing any feature, read the relevant docs:
- [`docs/requirements.md`](../docs/requirements.md) — Full product requirements and scope
- [`docs/roadmap.md`](../docs/roadmap.md) — Development phases and priorities
- [`docs/content-model.md`](../docs/content-model.md) — What goes in config files vs database
- [`docs/dev-instructions.md`](../docs/dev-instructions.md) — Developer workflow guide

## Language Rules (strictly enforced)
- **Code comments**: always in English
- **Documentation in `docs/`**: Italian
- **`README.md`**: English
- **User-facing content**: managed via Laravel lang files (it/en/fr/de)

## Architecture Principles
- Mobile-first, performance-oriented (image/data caching, Vite build)
- DRY: shared components and services, no code duplication
- Thin controllers: business logic in Services / Actions
- Multilingual: browser default, Italian fallback, lang files in `lang/{locale}/`
- Configuration over hardcoding: stable content in config files; dynamic data in DB
- SEO is a first-class concern on every public-facing route

## Documentation Update Rule
After every significant change to code, architecture, or features:
1. Update the relevant file in `docs/`
2. Update `README.md` if setup steps or project structure changed
3. Keep cross-references between docs accurate
