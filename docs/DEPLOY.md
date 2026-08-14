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
- Cloudflare may prepend a managed robots.txt section; after robots.txt changes, purge the
  Cloudflare cache for `/robots.txt` or disable the managed robots.txt feature so the origin
  file is served unchanged.
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

## Read-only access to the production database (local analysis/debugging)

The production MySQL DB is not reachable directly; it's only reachable through an SSH tunnel. There is no local Docker DB mirroring production — `.env` (local, mysql driver) is preconfigured to point at the tunnel's local port.

- Print the exact tunnel/app commands: `npm run start:dbprod -- --print`
- Tunnel only (no local app/vite, e.g. to run `php artisan tinker` against production data):
  ```
  ssh -o ExitOnForwardFailure=yes -i ~/.ssh/id_rsa_supporthost -p 2299 -L 3307:localhost:3306 lacaraco@65.108.143.244 -N
  ```
- With the tunnel open, `php artisan tinker` / `php artisan migrate` in this repo operate on the **production** DB (`.env` → `DB_HOST=127.0.0.1`, `DB_PORT=3307`, `DB_DATABASE=lacaraco_lacaracolaweb`).
- Treat this as production: read-only queries are safe to run freely; **always confirm with the project owner before writing data, changing schema, or running migrations** through this tunnel.
- Close the SSH tunnel (kill the terminal) when done.
- For local development without touching production data, use `npm run start:local` instead (`.env.local`, SQLite).

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

# Telegram Bot (optional — omit if not using notifications)
TELEGRAM_BOT_TOKEN=YOUR_BOT_TOKEN
TELEGRAM_WEBHOOK_SECRET=YOUR_RANDOM_SECRET
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

- CPANEL_HOST (direct cPanel hostname or server IP, for example `65.108.143.244`; do not use the Cloudflare-proxied public domain)
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

## Laravel Scheduler — cPanel Cron Job

The Laravel scheduler must be triggered every minute by a system cron job.
Without this, scheduled commands (including Telegram reminders) never run.

In cPanel → **Cron Jobs**, add the following entry (every minute):

```
* * * * * /opt/cpanel/ea-php84/root/usr/bin/php /home/lacaraco/lacaracola-app/artisan schedule:run >> /dev/null 2>&1
```

To verify the scheduler is working, run manually from the cPanel terminal:

```bash
cd /home/lacaraco/lacaracola-app
php artisan schedule:run --verbose
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
5. Open https://lacaracolaandora.com/robots.txt and confirm it contains the sitemap directive and no global `Disallow: /`
6. Open https://lacaracolaandora.com/sitemap.xml and confirm it returns HTTP 200 with valid XML and localized public URLs
7. Check storage/logs/laravel.log for errors

## Telegram Bot setup

After first deploy with `TELEGRAM_BOT_TOKEN` configured:

1. **Register the webhook** with Telegram (once per environment):

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_TOKEN>/setWebhook" \
  -d "url=https://lacaracolaandora.com/api/telegram/webhook/<YOUR_WEBHOOK_SECRET>"
```

2. **Discover recipient `chat_id`s**: Share the bot link `https://t.me/LaCaracolaAndoraBot` with each user. Ask them to send any message to the bot.

3. **Read the log**: After each message, check `storage/logs/telegram.log` for a `Telegram message` entry containing the `chat_id`.

4. **Save the `chat_id`**: In the admin panel under **Gestione Utenti**, edit the user and paste their `chat_id` in the **Telegram Chat ID** field. Save.

From that point the user will receive all Telegram notifications automatically.

### Scheduled reminders

The daily reminder command (`telegram:send-reminders`) runs via the Laravel scheduler — no separate cron entry needed. It's covered by the single every-minute cron job already set up in [Laravel Scheduler — cPanel Cron Job](#laravel-scheduler--cpanel-cron-job) above. Do not add a second `schedule:run` cron entry (e.g. a once-daily one): it would be redundant and could cause `dailyAt()`/`yearlyOn()` jobs to be missed if it doesn't run at the exact due minute.

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
