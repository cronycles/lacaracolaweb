# La Caracola Web

Multilingual short-rental website for "La Caracola" apartment in Marina di Andora (Savona, Liguria).
Built with Laravel + MySQL + TypeScript + PostCSS + Vite + ESLint.

## Project Goals

- Showcase the apartment, nearby experiences, and trust elements.
- Collect indirect booking leads outside major portals (Airbnb, Booking).
- Provide a private admin area to manage availability, pricing, minimum stay, guests and bookings.
- Keep the stack fully open source with no paid lock-in.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2+ / Laravel |
| Database | MySQL (production), SQLite (local dev) |
| Frontend JS | TypeScript |
| Frontend CSS | PostCSS |
| Bundler | Vite (transpile + minify) |
| Linting | ESLint |

## Language Policy

- Code comments: **English**
- Documentation in `docs/`: **Italian**
- This `README.md`: **English**
- User-facing content: Laravel lang files (`lang/{it,en,fr,de}/`)

## Project Documentation (`docs/`)

| File | Contents |
|------|----------|
| `docs/requirements.md` | Full product requirements (functional + non-functional) |
| `docs/roadmap.md` | Development phases and priorities |
| `docs/content-model.md` | Config vs database decisions, entity schema |
| `docs/dev-instructions.md` | Developer workflow, links to Copilot instructions |
| `docs/fase2-checklist-test-manuale.md` | Manual test checklist for completed Phase 2 items |

## Copilot Instructions (`.github/`)

| File | Scope |
|------|-------|
| `.github/copilot-instructions.md` | Always-on project standards |
| `.github/instructions/laravel.instructions.md` | PHP/Laravel files |
| `.github/instructions/frontend.instructions.md` | `resources/**` (TS, PostCSS) |
| `.github/instructions/documentation.instructions.md` | `docs/**` |

## Local Development

### Prerequisites
- PHP 8.2+ with extensions: `openssl`, `pdo_sqlite`, `mbstring`, `fileinfo`, `curl`, `zip`
- Composer
- Node.js 18+ / npm

### Setup
```bash
# Install PHP dependencies
php /path/to/composer install

# Install Node dependencies
npm install

# Copy environment file and generate key
cp .env.example .env
php artisan key:generate

# Run database migrations (SQLite, no DB server needed locally)
php artisan migrate

# Start full dev environment (Laravel + Vite hot-reload)
npm run start
```

> `npm run start` runs `php artisan serve` + `vite` concurrently.
> Open http://localhost:8000

### Other commands
```bash
npm run build      # TypeScript check + production build (minified)
npm run lint       # ESLint on resources/ts
npm run lint:fix   # ESLint auto-fix
```

## Project Structure

```
app/
  Http/
    Controllers/
      Public/     # Public-facing pages and booking form
      Admin/      # Private admin area (auth protected)
    Middleware/
      SetLocale   # Browser/session-based locale detection
  Models/         # Person, Booking, AvailabilityBlock, PricingRule
config/
  apartment.php   # Stable apartment content (name, address, amenities, rules, **images**)
lang/
  it/ en/ fr/ de/ # Translation files
resources/
  css/            # PostCSS — tokens, base, layout, components, pages
  ts/             # TypeScript — hero slider, nav, gallery, booking form, map
  views/
    layouts/      # Blade layout (app.blade.php)
    components/   # Reusable Blade components
    public/       # Public pages
    admin/        # Admin area views
routes/
  web.php         # Public routes
  admin.php       # Admin routes (auth middleware)
database/
  migrations/     # people, bookings, availability_blocks, pricing_rules
```

## Adding / Replacing Images

All image paths are centralised in `config/apartment.php` under the `images` key — **no Blade files need to be touched**.

```php
'images' => [
    'hero'    => ['images/hero-1.jpg', 'images/hero-2.jpg', 'images/hero-3.jpg'],
    'gallery' => ['images/apartment-1.jpg', ..., 'images/apartment-6.jpg'],
    'og'      => 'images/og-default.jpg',
],
```

Steps:
1. Copy the image files into `public/images/`.
2. Update the paths in `config/apartment.php` under `images`.
3. Run `php artisan config:clear` (or `config:cache` in production).

**Recommended dimensions:**

| Key | Count | Recommended size | Notes |
|-----|-------|-----------------|-------|
| `hero` | 3 | **1920 × 1080 px** | Full-viewport background, `cover` — wider is better |
| `gallery` | 6 (adjustable) | **1200 × 800 px** | Masonry layout, free aspect ratio |
| `og` | 1 | **1200 × 630 px** | Open Graph standard (Facebook, WhatsApp previews) |

**Gallery smart fallback:** if a file does not exist in `public/`, the gallery automatically shows a `placehold.co` placeholder — no broken images.
You can freely add or remove items from `images.gallery` and the gallery renders accordingly.

## Development Phases

1. **Phase 0** — Documentation and foundations ✅
2. **Phase 1** — MVP public pages + booking request form + admin area ✅
3. **Phase 2** — Booking mode switch (form vs external link toggle)
4. **Phase 3** — Automatic Interhome email parsing
5. **Phase 4** — SEO content expansion + conversion improvements
6. **Phase 5** — CI/CD deploy to cPanel hosting
