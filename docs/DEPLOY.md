# Production Deploy Guide - SupportHost cPanel

This guide documents the production deploy setup for lacaracolaweb on SupportHost, with main domain lacaracolaandora.com.

## Current production context

- Domain: lacaracolaandora.com
- Hosting: SupportHost (cPanel)
- cPanel user: lacaraco
- Server IP: 65.108.143.244
- Deploy trigger: push to main
- SSL: AutoSSL / Let's Encrypt on apex and www
- Cloudflare: proxy on apex/www after SSL verification, technical records on DNS only
- MySQL DB: lacaraco_lacaracolaweb
- MySQL user: lacaraco_cronycles

## Deploy architecture

Because the main domain document root is public_html, Laravel app files are deployed outside web root.

- Full Laravel app: /home/lacaraco/lacaracola-app
- Public assets copied to: /home/lacaraco/public_html
- public_html/index.php bootstraps the external app
- Storage remains outside public_html
- Symlink: public_html/storage -> /home/lacaraco/lacaracola-app/storage/app/public

## Repository deploy files

- .github/workflows/deploy.yml
- .cpanel.yml
- scripts/deploy.sh

## Required setup in Cloudflare and cPanel

### Cloudflare

- Point domain nameservers to Cloudflare.
- A record for apex (@) to 65.108.143.244.
- CNAME for www to @.
- Start with DNS only during first SSL setup.

### PHP in cPanel

- Use PHP 8.4.
- Enable required extensions:
    - pdo_mysql
    - mysqli
    - mbstring
    - openssl
    - fileinfo
    - tokenizer
    - xml
    - ctype
    - json
    - bcmath

### MySQL in cPanel

- Create DB: lacaraco_lacaracolaweb
- Create user: lacaraco_cronycles
- Assign all privileges to the DB user.

### Git repository in cPanel

- Use Git Version Control and clone:
    - https://github.com/cronycles/lacaracolaweb.git
- Suggested repository path:
    - /home/lacaraco/repositories/lacaracolaweb
- Run first update from remote.

## Important paths

- Repository on server: /home/lacaraco/repositories/lacaracolaweb
- Deployed Laravel app: /home/lacaraco/lacaracola-app
- Web root: /home/lacaraco/public_html

## Production environment file

The production .env is not in Git. Keep it at:

- /home/lacaraco/lacaracola-app/.env

Minimum recommended keys:

```env
APP_NAME="La Caracola"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATED_KEY
APP_URL=https://lacaracolaandora.com

APP_LOCALE=it
APP_FALLBACK_LOCALE=it

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=lacaraco_lacaracolaweb
DB_USERNAME=lacaraco_cronycles
DB_PASSWORD=YOUR_DB_PASSWORD

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=YOUR_SMTP_HOST
MAIL_PORT=587
MAIL_USERNAME=YOUR_SMTP_USER
MAIL_PASSWORD=YOUR_SMTP_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@lacaracolaandora.com
MAIL_FROM_NAME="La Caracola"

ADMIN_EMAIL=YOUR_ADMIN_EMAIL
ADMIN_PASSWORD=YOUR_ADMIN_PASSWORD
```

## APP_KEY generation

With cPanel terminal:

```bash
cd /home/lacaraco/repositories/lacaracolaweb
php artisan key:generate --show
```

Or generate locally if needed.

## GitHub Actions secrets

Configure these in repository Actions secrets:

- CPANEL_HOST (expected: lacaracolaandora.com, verify on port 2083)
- CPANEL_USER (lacaraco)
- CPANEL_TOKEN (API token from cPanel)
- CPANEL_REPOSITORY_ROOT (/home/lacaraco/repositories/lacaracolaweb)

## cPanel API token creation

- Open Manage API Tokens in cPanel.
- Create token (for example: github-deploy).
- Save token value in GitHub as CPANEL_TOKEN.

## First deploy checklist

Before first automatic deploy, ensure these exist:

- /home/lacaraco/lacaracola-app
- /home/lacaraco/lacaracola-app/storage/\*
- /home/lacaraco/lacaracola-app/bootstrap/cache
- /home/lacaraco/lacaracola-app/.env

Then push to main. The workflow calls cPanel and runs scripts/deploy.sh via .cpanel.yml.

Default behavior is copy-only deploy (no artisan tasks) unless server enables:

```bash
CPANEL_RUN_ARTISAN=1
```

## Current operational note

In current production, server environment is configured so artisan tasks can run during deploy when CPANEL_RUN_ARTISAN=1 is active.
That means migrations may run automatically on push.

If deploy falls back to copy-only, check CPANEL_RUN_ARTISAN before running manual migrations.

## Migrations

In this setup, you do not generate SQL files manually.

If automatic migrations are not applied, fallback manual flow:

```bash
cd ~/lacaracola-app
php artisan migrate --force
```

Admin seeder fallback:

```bash
php artisan db:seed --class=AdminUserSeeder --force
```

## SSL and HTTPS

After DNS points to SupportHost:

1. Open SSL/TLS Status in cPanel.
2. Run AutoSSL.
3. Confirm certificates for apex and www.
4. Re-enable Cloudflare proxy after SSL confirmation.

## SMTP production example

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.lacaracolaandora.com
MAIL_PORT=465
MAIL_USERNAME=info@lacaracolaandora.com
MAIL_PASSWORD=YOUR_MAILBOX_PASSWORD
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=info@lacaracolaandora.com
MAIL_FROM_NAME="La Caracola"
```

Quick terminal test:

```bash
php -r "require '/home/lacaraco/lacaracola-app/vendor/autoload.php'; $app = require '/home/lacaraco/lacaracola-app/bootstrap/app.php'; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); Illuminate\Support\Facades\Mail::raw('Test SMTP La Caracola', function ($message) { $message->to('info@lacaracolaandora.com')->subject('Test SMTP La Caracola'); }); echo 'MAIL SENT';"
```

## Post-deploy checks

1. Open https://lacaracolaandora.com
2. Confirm CSS/JS assets load
3. Test admin login
4. Test booking form
5. Check storage/logs/laravel.log for errors

## Troubleshooting

### 500 Internal Server Error

Check:

- .env exists at /home/lacaraco/lacaracola-app/.env
- MySQL credentials are correct
- DB_CONNECTION=mysql is set explicitly
- Required PHP extensions are enabled
- Laravel logs in storage/logs/laravel.log

### GitHub Actions fails with 401

- cPanel token is wrong or expired.

### route_locale not found

- Ensure global function is defined in app/helpers.php without namespace.
- Keep helper class in app/Support/RouteHelper.php.
- Ensure composer autoload.files includes app/helpers.php.

### NameError null in workflow

- Fix JSON parsing flow in deploy workflow: avoid stdin collision between heredoc blocks.
- Use environment variable body passing approach.

### MySQL access denied

- Verify DB user is attached to DB in cPanel.
- Verify DB password formatting in .env.
- Use DB_HOST=localhost on shared hosting.

### App uses SQLite unexpectedly

- Ensure DB_CONNECTION=mysql is set in .env.

### SSH key rotation after setup sharing

If a private key or passphrase was exposed during setup:

- Treat the key as compromised.
- Generate a new key and authorize it in cPanel.
- Verify access with the new key.
- Remove old key from cPanel and local machine.

### Deploy updates repo but site does not change

- Verify CPANEL_REPOSITORY_ROOT points to the expected repository.
- Verify .cpanel.yml exists on server repository copy.
- Verify GitHub Actions run completed successfully.

## Booking import status

IMAP/email automatic ingestion was decommissioned on 2026-04-01.

- Do not configure IMAP\_\* variables in .env.
- No emails:parse-bookings command is required.
- Admin sections for email ingestion/logs were removed.

Current integration path is manual Interhome PDF import in admin.

### Manual Interhome PDF import flow

1. Open admin/prenotazioni/import-pdf
2. Upload exported Interhome PDF
3. Review dry-run preview (new vs duplicate)
4. Confirm import to create only new bookings
