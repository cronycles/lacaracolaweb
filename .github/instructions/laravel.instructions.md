---
description: "Use when writing or modifying PHP, Laravel controllers, models, migrations, routes, services, views, config or lang files. Covers coding conventions, architecture patterns, multilingual setup, and SEO practices for this project."
applyTo: "**/*.php"
---

# Laravel Conventions — La Caracola Web

## Architecture
- **Thin controllers**: only handle HTTP input/output. Move logic to `app/Services/` or `app/Actions/`.
- **Repository pattern** for complex DB queries (guests, bookings, availability).
- **Shared Blade components** in `resources/views/components/` — never duplicate layout or UI fragments.
- Feature-grouped routes: `routes/web.php` (public), `routes/admin.php` (private area, auth middleware).

## Multilingual (i18n)
- All user-facing strings via `__('key')` or `@lang('key')` — never hardcoded in views.
- Lang files in `lang/{it,en,fr,de}/` — Italian is the fallback locale.
- Browser locale detection in middleware, stored in session.
- SEO metadata (title, description, og:tags) must have locale-specific values in lang files.

## Database / Eloquent
- Migrations for every schema change — always reversible (`up` and `down`).
- Use Eloquent relationships; avoid raw queries unless performance-critical.
- Soft deletes on `guests` and `people` tables.
- See [`docs/content-model.md`](../../docs/content-model.md) for the Person / Guest / Newsletter distinction.

## Configuration
- Stable content (address, brand colors, apartment features, rules, useful places) in `config/apartment.php`.
- Environment-specific values only in `.env` — never commit secrets.

## SEO
- Every public route has a corresponding `<title>`, `<meta description>`, `<link rel="canonical">` and Open Graph tags, localised.
- Use structured data (JSON-LD) on Home and Apartment pages.
- Slugs and URL structure must be clean and language-prefixed (e.g. `/it/appartamento`, `/en/apartment`).

## Performance
- Cache availability and pricing queries (Laravel Cache, tagged).
- Eager-load relationships to avoid N+1.
- Optimise images via storage with responsive sizes.

## Code Style
- PSR-12 formatting.
- Comments in English.
- No TODO comments left in committed code — open a task instead.

## Clean Code (strictly enforced)
- **Meaningful names**: variables, methods, and classes must reveal intent. No `$data`, `$arr`, `$tmp`, `$result` as final names.
- **Small functions**: each function does one thing only. If a method needs a comment to explain what it does, extract it.
- **No magic numbers/strings**: use named constants or config values.
- **Don't repeat yourself**: if the same logic appears twice, extract it into a method or service.
- **Fail fast**: validate and return/throw early; avoid deep nesting.
- **No dead code**: remove commented-out code, unused variables, unused imports.
- **Avoid flag arguments**: a boolean parameter that changes a function's behaviour is a sign it should be two methods.
- **Expressive conditionals**: prefer `if ($booking->isPast())` over `if ($booking->checkout < now())` — use model methods for domain logic.
- **Law of Demeter**: controllers should not know the internals of nested objects; delegate to methods on the immediate object.
- **Single Responsibility**: one class/model = one responsibility. Controllers only do HTTP; models only do data; services do business logic.
