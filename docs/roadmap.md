# Roadmap di Sviluppo

Ultimo aggiornamento: 2026-04-01


## Fase 0 — Fondazioni ✅ COMPLETATA

- [x] Definizione requisiti e policy progetto.
- [x] Setup documentazione (`docs/`) e istruzioni Copilot (`.github/instructions/`).
- [x] Scaffold Laravel, installazione dipendenze Composer e npm.
- [x] Abilitazione estensioni PHP (openssl, pdo_sqlite, mbstring, fileinfo, curl, zip).
- [x] Toolchain frontend: TypeScript + PostCSS + Vite + ESLint.
- [x] Build confermata (8 moduli, CSS + JS minificati).

---

## Fase 1 — MVP Pubblico + Area Privata Base ✅ COMPLETATA

### Obiettivo
Rilasciare un sito pubblicabile con lead generation indiretta e gestione base operativa.

### Fatto
- [x] Pagine pubbliche: Home, Appartamento, Dove Siamo, Esperienze, Recensioni, Regole, Posti Utili.
- [x] Multilingua da file: IT (completo), EN, FR, DE (completi e validati).
- [x] Form richiesta disponibilità (Flow B) con validazione min. notti lato server e client.
- [x] Middleware `SetLocale` (sessione → Accept-Language → IT fallback).
- [x] `config/apartment.php` completo (indirizzo, amenità, regole, posti utili, SEO, piattaforme).
- [x] Layout Blade pubblico con SEO completo (meta, OG, JSON-LD, canonical).
- [x] Design system CSS (16 file PostCSS, token, base, layout, componenti, pagine).
- [x] Componenti TypeScript: hero slider, nav mobile, lang switcher, gallery lightbox, booking form, mappa Leaflet.
- [x] Migrazioni DB: `people`, `bookings`, `availability_blocks`, `pricing_rules` — eseguite su SQLite.
- [x] Modelli Eloquent con relazioni, soft deletes, accessor.
- [x] Area admin: layout sidebar, dashboard KPI, calendario blocchi, prezzi CRUD, prenotazioni CRUD, ospiti CRUD + soggiorni, newsletter.
- [x] Login admin (`/admin/login`) — nessun link pubblico visibile.
- [x] Seeder `AdminUserSeeder` con credenziali da `.env`.
- [x] 45 rotte registrate e validate.

### Prossimi passi prima del primo deploy
- [x] Produzione utente admin iniziale (`php artisan db:seed --class=AdminUserSeeder`).
- [x] Impostare `ADMIN_PASSWORD` in `.env`.
- [x] Test end-to-end manuale dell'intero flusso (pubblico + admin).
- [x] `vendor/` **NON** ignorato da git (hosting senza Composer — vedere nota deploy sotto).
- [ ] Caricare immagini reali in `public/images/` (path centralizzati in `config/apartment.php` → `images.hero`, `images.gallery`, `images.og`).

---

## Fase 2 — Booking Switch, Form UX e Automazioni Iniziali ✅ COMPLETATA

- [x] Implementazione Flow C (switch in area privata):
  - modalità richiesta disponibilità,
  - modalità link esterno configurabile (Airbnb / Booking / Interhome).
- [x] **Form richiesta disponibilità — UX completa**:
  - invio AJAX (nessun reload di pagina),
  - errori inline per campo,
  - stato loading sul bottone submit + success message in-page,
  - messaggi di errore localizzati (IT/EN/FR/DE),
  - protezione anti-spam honeypot,
  - date picker visuale e toggle newsletter migliorato.
- [x] **Sezione SEO home page per lingua**.
- [x] Ingestion semiautomatica prenotazioni da testo email incollato.
- [x] Migliorie calendario (vista mensile visuale).


## Fase 3 — Crescita Commerciale e Contenuti ✅ COMPLETATA

- [x] **URL localizzati per SEO** — es. `/en/apartment`, `/fr/appartement` invece di `/appartamento?lang=fr`.
- [x] Traduzione testi hardcoded in italiano nelle view pubbliche principali.
- [x] Espansione contenuti SEO locali multilingua.
- [x] Migliorie UX su conversione mobile.
- [x] Gestione recensioni più evoluta.
- [x] Dashboard KPI base (tasso occupazione, provenienza prenotazioni).


## Fase 4 — Deploy su Hosting cPanel ✅ COMPLETATA

> **Nota deploy**: l'hosting non ha Composer. La strategia è:
> - `vendor/` è versionato in git (non ignorato).
> - `public/build/` (assets Vite) è versionato in git.
> - Il deploy consiste nel copiare i file con pipeline GitHub Actions → cPanel API.
> - Script `scripts/deploy.sh` in modalità copy-only (niente artisan/composer sul server).
> - Migrazioni eseguite manualmente via `php artisan migrate` dal cPanel Terminal.

- [x] Configurazione DNS dominio su Cloudflare (tutti i record su DNS only).
- [x] Configurazione PHP 8.3+ e estensioni su cPanel.
- [x] Creazione database MySQL `lacaraco_lacaracolaweb` con utente `lacaraco_cronycles`.
- [x] Repo Git su cPanel (Git Version Control).
- [x] Token API cPanel creato (`github-deploy`) e secrets GitHub Actions impostati.
- [x] Pipeline deploy GitHub Actions funzionante (push → deploy automatico su `main`).
- [x] File `.env` di produzione creato in `/home/lacaraco/lacaracola-app/.env`.
- [x] Prima migrazione su DB MySQL di produzione via cPanel Terminal.
- [x] Sito live su `https://lacaracolaandora.com`.
- [x] SSL/TLS con Let's Encrypt attivato su cPanel.
- [x] Proxy Cloudflare attivato (nuvola arancione) su `@` e `www` dopo SSL ok.
- [x] Redirect HTTP→HTTPS e `www` → no-www.
- [x] Rotazione chiave SSH `id_rsa_supporthost` completata con nuova coppia dedicata.
- [ ] Caricamento immagini reali in `public/images/`.

### Bug risolti durante il deploy iniziale

- **YAML syntax error** nel workflow `.github/workflows/deploy.yml` (blocco Python heredoc mal indentato).
- **Errore `NameError: name 'null'`** nel parsing risposta cPanel: stdin redirect conflitto con heredoc Python — risolto passando `$BODY` via variabile d'ambiente.
- **`DB_CONNECTION` mancante** nel `.env` produzione: Laravel usava SQLite di default — aggiunto `DB_CONNECTION=mysql`.
- **`route_locale()` undefined**: funzione globale definita dentro un file con `namespace App\Support` — spostata in `app/helpers.php` senza namespace.

---

## Fase 5 — Contenuti Finali e QA Produzione ← FASE CORRENTE

- [x] SSL/TLS completato e HTTPS verificato sul dominio principale.
- [x] Proxy Cloudflare attivato (nuvola arancione) su dominio e `www`.
- [x] Redirect HTTP→HTTPS e `www` → no-www verificato.
- [x] Login admin funzionante in produzione (seed `AdminUserSeeder` eseguito).
- [ ] Caricamento immagini reali in `public/images/` (path in `config/apartment.php`).
- [ ] Test end-to-end completo in produzione (form booking, lang switcher, admin CRUD).
- [x] Rotazione chiave SSH `id_rsa_supporthost`.
- [x] Verifica invio email (SMTP produzione configurato in `.env` e testato).

---

## Fase 6 — Parsing Email Interhome Automatico ✅ COMPLETATA

- [x] Acquisizione email da mailbox dedicata (IMAP via `webklex/laravel-imap`).
- [x] Parsing template prenotazioni Interhome (e Airbnb, Booking, generico).
- [x] Creazione/aggiornamento ospite e soggiorno automatica (`BookingCreationService`).
- [x] Blocco automatico date nel calendario (`AvailabilityBlock`).
- [x] Log errori parser e gestione eccezioni (tabella `email_parse_logs`).
- [x] Comando artisan `emails:parse-bookings` con opzione `--dry-run`.
- [x] Scheduling orario via `routes/console.php` (`Schedule::command(...)->hourly()`).
- [x] Pagina admin "Log email auto" con stato e link alla prenotazione creata.
- [x] Variabili `IMAP_*` configurate in produzione (mailbox `booking@lacaracolaandora.com`) e cron cPanel attivo.

---

## Fase 7 — Sicurezza Account Admin (PROSSIMA)

### Obiettivo
Permettere al proprietario di cambiare password direttamente dall'area privata, senza accesso manuale al database o al seeder.

### Implementazione prevista

- [ ] Aggiungere sezione "Sicurezza account" in `admin/impostazioni`.
- [ ] Form cambio password con campi: password attuale, nuova password, conferma nuova password.
- [ ] Validazione server-side robusta (password attuale corretta, lunghezza minima, conferma obbligatoria).
- [ ] Salvataggio hash con `Hash::make()` e messaggio di conferma in UI.
- [ ] Invalidare le altre sessioni dopo il cambio password (best practice sicurezza).
- [ ] Test manuale: login con nuova password OK, vecchia password KO.
