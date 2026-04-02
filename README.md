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
| `docs/deploy-produzione.md` | Production deploy guide for SupportHost cPanel + Cloudflare |
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
npm run start:local
```

> `npm run start:local` always recreates `.env` from `.env.local`, clears Laravel config, then runs `php artisan serve` + `vite`.
> If `.env.local` does not exist, it is bootstrapped from `.env.example`.
> Open http://localhost:8000

### Other commands
```bash
npm run build      # TypeScript check + production build (minified)
npm run lint       # ESLint on resources/ts
npm run lint:fix   # ESLint auto-fix
npm run start:local   # One-command local stack against local DB
npm run start:dbprod  # macOS: open SSH tunnel + local app against production DB
```

### Local App Against Production DB

For quick inspection against real production data, the project includes a dedicated macOS launcher:

```bash
npm run start:dbprod
```

What it does:
- Opens one Terminal.app window for the SSH tunnel to the production server
- Opens a second Terminal.app window for the local Laravel app
- Waits until local port `3307` is reachable before starting Laravel
- Always recreates `.env` from `.env.prod-local`
- If `.env.local` does not exist yet, creates it from `.env.example` to keep a local profile file available
- Clears config cache, then runs `php artisan serve` + `vite` in watch mode

Notes:
- This workflow is intended for macOS only (`osascript` + Terminal.app)
- The SSH tunnel stays in the foreground by design; keep that terminal open
- Local MySQL traffic goes through `127.0.0.1:3307` to the remote production database
- `npm run start:local` always loads `.env.local` into `.env`
- Outgoing mail is forced to `MAIL_MAILER=log` in `.env.prod-local` to avoid sending real emails
- Do **not** run destructive commands such as `php artisan migrate`, `db:seed`, or `migrate:fresh` while connected to production data
- When you want to go back to normal local development, run `npm run start:local`

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
3. **Phase 2** — Booking switch, form UX and initial automations ✅
4. **Phase 3** — Growth, multilingual SEO and content expansion ✅
5. **Phase 4** — Direct deploy to SupportHost cPanel hosting
6. **Phase 5** — Final production QA and content completion
7. **Phase 6** — Interhome PDF booking import (planned)

## Production Deploy

Production deploy is handled by GitHub Actions + cPanel Git deployment.

- Workflow: `.github/workflows/deploy.yml`
- cPanel entrypoint: `.cpanel.yml`
- Server-side script: `scripts/deploy.sh`
- Full guide: `docs/deploy-produzione.md`
