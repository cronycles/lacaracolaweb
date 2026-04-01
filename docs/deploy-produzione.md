# Deploy Produzione — SupportHost cPanel

Questa guida descrive il deploy automatico del progetto `lacaracolaweb` su hosting SupportHost con dominio principale `lacaracolaandora.com`.

## Scenario scelto

- Dominio: `lacaracolaandora.com`
- Tipo: dominio principale dell'hosting
- DNS: gestiti da Cloudflare
- Hosting: SupportHost con cPanel
- cPanel user: `lacaraco`
- IP server: `65.108.143.244`
- Deploy: automatico a ogni push su `main`
- SSL: attivo via AutoSSL / Let's Encrypt su dominio principale e `www`
- Cloudflare: proxy attivo su `@` e `www`, record tecnici (`mail`, `cpanel`, `ftp`, `webmail`, `webdisk`, ecc.) lasciati su `DNS only`
- Database MySQL:
  - DB: `lacaraco_lacaracolaweb`
  - utente: `lacaraco_cronycles`

## Architettura deploy

Poiché il dominio principale usa `public_html` come document root, il deploy non copia tutta l'app Laravel dentro la web root.

La strategia sicura e usata in questo progetto e:

- app Laravel completa fuori dalla web root: `/home/lacaraco/lacaracola-app`
- contenuto di `public/` copiato in: `/home/lacaraco/public_html`
- `public_html/index.php` riscritto per bootstrap dell'app esterna
- `storage` mantenuto fuori da `public_html`
- symlink `public_html/storage -> /home/lacaraco/lacaracola-app/storage/app/public`

## File di deploy presenti nel repo

- `.github/workflows/deploy.yml` — GitHub Actions che chiama le API cPanel
- `.cpanel.yml` — entrypoint del deploy cPanel
- `scripts/deploy.sh` — script server-side che aggiorna repo, copia file e ricrea il symlink storage (modalita default copy-only)

## Cosa devi fare tu in cPanel / Cloudflare

### 1. Cloudflare

Configura il dominio su Cloudflare prima del deploy:

- nameserver del dominio puntati a Cloudflare
- record `A` per `@` verso `65.108.143.244`
- record `CNAME` per `www` verso `@`
- proxy Cloudflare inizialmente su **DNS only** (nuvola grigia)

### 2. PHP su cPanel

Nel pannello SupportHost:

- imposta PHP `8.4`
- abilita estensioni:
  - `pdo_mysql`
  - `mysqli`
  - `mbstring`
  - `openssl`
  - `fileinfo`
  - `tokenizer`
  - `xml`
  - `ctype`
  - `json`
  - `bcmath`

### 3. Database MySQL

Crea in cPanel:

- database `lacaraco_lacaracolaweb`
- utente `lacaraco_cronycles`
- password forte dedicata
- assegna **tutti i privilegi** dell'utente al database

### 4. Repository Git su cPanel

In cPanel -> `Git Version Control`:

1. Crea un nuovo repository clonando GitHub
2. Repository URL: `https://github.com/cronycles/lacaracolaweb.git`
3. Repository path consigliato:
  - `/home/lacaraco/repositories/lacaracolaweb`
4. Dopo il clone, fai un primo `Update from Remote`

## Variabili e path da usare

Path principali consigliati:

- repo server: `/home/lacaraco/repositories/lacaracolaweb`
- app Laravel deployata: `/home/lacaraco/lacaracola-app`
- web root dominio principale: `/home/lacaraco/public_html`

## File `.env` di produzione

Il file `.env` **non** deve stare nel repo Git. Deve stare sul server in:

- `/home/lacaraco/lacaracola-app/.env`

Contenuto minimo consigliato:

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

## Come generare `APP_KEY`

Se hai SSH/Terminale cPanel:

```bash
cd /home/lacaraco/repositories/lacaracolaweb
php artisan key:generate --show
```

Poi incolla il valore nel file `.env` di produzione.

Se non puoi eseguire comandi sul server, puoi generarlo in locale e copiarlo nel `.env` di produzione.

Esempio in locale:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

## Segreti GitHub Actions da creare

Nel repo GitHub -> `Settings` -> `Secrets and variables` -> `Actions`:

- `CPANEL_HOST` = probabilmente `lacaracolaandora.com`, ma va verificato appena il pannello e il dominio rispondono su `:2083`
- `CPANEL_USER` = `lacaraco`
- `CPANEL_TOKEN` = token API creato in cPanel
- `CPANEL_REPOSITORY_ROOT` = `/home/lacaraco/repositories/lacaracolaweb`

Valori attesi oggi:

```text
CPANEL_HOST=lacaracolaandora.com   # da confermare
CPANEL_USER=lacaraco
CPANEL_REPOSITORY_ROOT=/home/lacaraco/repositories/lacaracolaweb
```

> Nota: il deploy di questo progetto usa i secret `CPANEL_*` e non richiede SSH da GitHub Actions.

## Come creare il token cPanel

In cPanel:

1. apri `Manage API Tokens`
2. crea un nuovo token, per esempio `github-deploy`
3. copia il token e salvalo come secret GitHub `CPANEL_TOKEN`

## Primo deploy

Prima del primo deploy automatico, assicurati che esistano:

- `/home/lacaraco/lacaracola-app`
- `/home/lacaraco/lacaracola-app/storage/...`
- `/home/lacaraco/lacaracola-app/bootstrap/cache`
- `/home/lacaraco/lacaracola-app/.env`

Lo script di deploy crea automaticamente le cartelle standard di storage e cache, ma il `.env` va creato da te.

Una volta fatto:

1. fai push su `main`
2. GitHub Actions chiama cPanel
3. cPanel esegue `.cpanel.yml`
4. `.cpanel.yml` lancia `scripts/deploy.sh`
5. lo script:
   - aggiorna il repo server
   - copia l'app fuori da `public_html`
   - copia `public/` dentro `public_html`
   - ricrea il symlink `storage`

Per default lo script non esegue comandi artisan (deploy copy-only).

Se in futuro vuoi riattivare migrate/cache nel deploy automatico, imposta la variabile ambiente server:

```bash
CPANEL_RUN_ARTISAN=1
```

## Migrazioni SQL: cosa devi fare davvero

In questo setup **non devi generare manualmente file SQL**.

Serve solo:

- creare il database MySQL vuoto in cPanel
- configurare correttamente il `.env`

Con deploy copy-only, le migration non vengono eseguite automaticamente.

**Procedura raccomandata** (verificata in produzione):

1. Apri cPanel -> Terminal.
2. Entra nella cartella app: `cd ~/lacaracola-app`
3. Lancia: `php artisan migrate --force`

> Attenzione: se il `.env` ha `DB_PASSWORD` con caratteri speciali come `#`, la password va racchiusa tra virgolette oppure usare una password senza caratteri speciali per evitare errori di accesso MySQL.

Per creare l'utente admin iniziale in produzione:

1. Imposta nel `.env`:
  - `ADMIN_EMAIL=...`
  - `ADMIN_PASSWORD=...`
2. Esegui dal cPanel Terminal:
  - `php artisan db:seed --class=AdminUserSeeder --force`

In alternativa, riattiva i task artisan nel deploy automatico con `CPANEL_RUN_ARTISAN=1`.

## SSL e HTTPS

Dopo aver puntato i DNS a SupportHost:

1. apri cPanel -> `SSL/TLS Status`
2. esegui `Run AutoSSL`
3. attendi il certificato per `lacaracolaandora.com` e `www.lacaracolaandora.com`

Quando HTTPS funziona correttamente, si puo aggiungere o mantenere il redirect a HTTPS lato hosting.

Configurazione verificata in produzione:

- certificato attivo su `lacaracolaandora.com`
- certificato attivo su `www.lacaracolaandora.com`
- redirect `http -> https` attivo
- redirect `www -> no-www` gestito da `public/.htaccess`
- proxy Cloudflare riattivato solo dopo la conferma SSL

## SMTP produzione

Configurazione verificata con invio email reale verso la mailbox del dominio:

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

Test rapido dal cPanel Terminal:

```bash
php -r "require '/home/lacaraco/lacaracola-app/vendor/autoload.php'; \$app = require '/home/lacaraco/lacaracola-app/bootstrap/app.php'; \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class); \$kernel->bootstrap(); Illuminate\Support\Facades\Mail::raw('Test SMTP La Caracola', function (\$message) { \$message->to('info@lacaracolaandora.com')->subject('Test SMTP La Caracola'); }); echo 'MAIL SENT';"
```

## Verifiche dopo il deploy

Controlli minimi:

1. apri `https://lacaracolaandora.com`
2. verifica che home e assets CSS/JS si carichino
3. prova login admin
4. controlla che il form booking funzioni
5. controlla `storage/logs/laravel.log` in caso di 500

## Troubleshooting rapido

### 500 Internal Server Error

Controlla:

- `.env` presente in `/home/lacaraco/lacaracola-app/.env`
- credenziali MySQL corrette (password senza caratteri speciali come `#` nel `.env`)
- `DB_CONNECTION=mysql` esplicitamente presente nel `.env` (senza questo, Laravel usa SQLite)
- estensioni PHP abilitate
- log Laravel in `storage/logs/laravel.log`

### GitHub Actions fallisce con 401

- token cPanel scaduto o errato

### `Call to undefined function route_locale()`

Succede se la funzione globale viene definita dentro un file con `namespace`. In questo progetto la funzione deve stare in `app/helpers.php` senza namespace, mentre la classe helper resta in `app/Support/RouteHelper.php`. Controlla anche che `composer.json` registri `app/helpers.php` nella sezione `autoload.files`.

### `NameError: name 'null' is not defined` nel workflow

Errore di parsing JSON nel blocco Python del workflow. Il problema è un conflitto di stdin tra heredoc Python e herestring bash. Soluzione: passare il body JSON via variabile d'ambiente invece di stdin (vedi `deploy.yml` attuale).

### Accesso negato MySQL (`Access denied for user`)

- Verifica che l'utente sia associato al database in cPanel -> MySQL Databases
- Verifica che la password nel `.env` non contenga caratteri speciali non escapati
- Il `DB_HOST` deve essere `localhost` (non `127.0.0.1` né hostname remoto su hosting condiviso)

### Il sito usa SQLite invece di MySQL

- Nel `.env` manca `DB_CONNECTION=mysql`
- Se `DB_CONNECTION` non è valorizzato esplicitamente, Laravel può ricadere sulla configurazione di default

### Rotazione chiavi SSH dopo setup guidato

- Se una chiave privata o passphrase viene condivisa durante una sessione di setup, considerala compromessa
- Genera una nuova chiave dedicata, importala in cPanel e autorizzala
- Verifica l'accesso con la nuova chiave
- Solo dopo il test positivo, rimuovi la chiave vecchia da cPanel e dal computer locale

### Il deploy aggiorna il repo ma il sito non cambia

- verifica che `CPANEL_REPOSITORY_ROOT` punti al repo giusto
- verifica che `.cpanel.yml` sia presente nel repo sul server
- verifica che il workflow GitHub Actions venga eseguito in verde

## Import prenotazioni (stato attuale)

Il flusso automatico IMAP/email è stato dismesso il 2026-04-01.

- Non configurare variabili `IMAP_*` nel `.env`.
- Non è richiesto alcun comando `emails:parse-bookings`.
- Le sezioni admin legate a ingestion email e log email sono state rimosse.

La prossima integrazione prevista è l'import da PDF Interhome (manuale in area privata, poi eventuale automazione).

### Utilizzo import PDF manuale (area admin)

1. Apri `admin/prenotazioni/import-pdf`.
2. Carica il PDF esportato da Interhome.
3. Verifica l'anteprima dry-run (nuove vs duplicate).
4. Conferma l'import per creare solo le prenotazioni nuove.

