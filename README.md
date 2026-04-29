# La Caracola Web

Multilingual vacation-rental website for La Caracola apartment in Marina di Andora (Savona, Liguria).

Production website: https://lacaracolaandora.com

If you need full product and architecture details, use the docs index below.

## What This Project Is

- Public website for apartment presentation and booking lead collection.
- Private admin area to manage bookings, availability, pricing rules, and stay constraints.
- Multilingual content with Italian fallback.

## Tech Stack

| Layer    | Technology                         |
| -------- | ---------------------------------- |
| Backend  | PHP 8.2+ / Laravel                 |
| Database | SQLite (local), MySQL (production) |
| Frontend | TypeScript + PostCSS               |
| Build    | Vite                               |
| Quality  | ESLint + TypeScript checks         |

## First Local Run (Fast Path)

### Prerequisites

- PHP 8.2+ with common Laravel extensions (`openssl`, `pdo_sqlite`, `mbstring`, `fileinfo`, `curl`, `zip`)
- Composer
- Node.js 18+ and npm

### Setup and Start

```bash
# Install PHP and Node dependencies, then create production build assets
npm run restore

# Create local environment file and app key
cp .env.example .env
php artisan key:generate

# Create local SQLite schema
php artisan migrate

# Start Laravel + Vite in local mode
npm run start:local
```

Open http://localhost:8000

Notes:

- `npm run start:local` rebuilds `.env` from `.env.local` every run.
- If `.env.local` is missing, it is generated from `.env.example`.

## Run Local App Against Production DB (Windows/Linux/macOS)

Use this mode only for investigation on real data:

```bash
npm run start:dbprod
```

Behavior:

- Starts an SSH tunnel process from Node and waits for local port readiness.
- Runs Laravel + Vite in the same terminal once tunnel is ready.
- Uses `.env.prod-local` to point local app to remote DB through `127.0.0.1:3307`.
- Forces safe mail mode (`MAIL_MAILER=log`) to avoid sending real emails.

Requirements:

- `ssh` command must be available in PATH (OpenSSH client).
- `.env.prod-local` must exist (it is intentionally gitignored).

Quick start (Windows/Linux/macOS):

1. Create or recover `.env.prod-local` in project root.
2. Verify SSH availability:

```bash
ssh -V
```

3. Start the stack in prod-DB mode:

```bash
npm run start:dbprod
```

Optional overrides (when your SSH settings differ):

- `DBPROD_SSH_HOST`
- `DBPROD_SSH_PORT`
- `DBPROD_SSH_USER`
- `DBPROD_SSH_KEY`
- `DBPROD_TUNNEL_LOCAL_PORT`
- `DBPROD_TUNNEL_REMOTE_HOST`
- `DBPROD_TUNNEL_REMOTE_PORT`

Important safety rules:

- Do not run destructive DB commands in this mode (`migrate`, `migrate:fresh`, `db:seed`, etc.).
- Keep the SSH tunnel terminal open while working.
- Switch back to normal local development with `npm run start:local`.

## Deploy: How and When It Happens

Deploy is automatic on push to `main`.

Flow:

1. GitHub Actions workflow runs.
2. cPanel deploy entrypoint is triggered.
3. Server-side deploy script syncs app and public assets.

Files involved:

- `.github/workflows/deploy.yml`
- `.cpanel.yml`
- `scripts/deploy.sh`

Operational details, environment variables, and fallback procedures are documented in `docs/DEPLOY.md`.

## Project Documentation Index

Documentation in `docs/` is in Italian.

- `docs/project-doc.mdc`: agent-first reading order and doc map.
- `docs/tech-doc.mdc`: cross-project engineering workflow and standards.
- `docs/business-doc.mdc`: product behavior and business intent.
- `docs/specific-tech-doc.mdc`: project-specific technical constraints.
- `docs/specific-tech-backend-doc.mdc`: backend implementation notes.
- `docs/specific-tech-frontend-doc.mdc`: frontend implementation notes.
- `docs/specific-tech-images-doc.md`: image workflow and constraints.
- `docs/specific-data-model.md`: data model details and rules.
- `docs/DEPLOY.md`: production deploy and hosting operations.

## Useful Commands

```bash
npm run build      # TypeScript check + production build
npm run build:fast # Build without TypeScript check
npm run lint       # ESLint check for resources/ts
npm run lint:fix   # ESLint auto-fix
npm run start:local
npm run start:dbprod
```
