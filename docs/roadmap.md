# Roadmap di Sviluppo

Ultimo aggiornamento: 2026-03-26

---

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
- [ ] Produzione utente admin iniziale (`php artisan db:seed --class=AdminUserSeeder`).
- [ ] Impostare `ADMIN_PASSWORD` in `.env`.
- [ ] Test end-to-end manuale dell'intero flusso (pubblico + admin).
- [ ] Caricare immagini hero reali in `public/images/`.
- [ ] Configurare dominio e hosting cPanel.
- [ ] `vendor/` **NON** ignorato da git (hosting senza Composer — vedere nota deploy sotto).

---

## Fase 2 — Booking Switch e Automazioni Iniziali

- [ ] Implementazione Flow C (switch in area privata):
  - modalità richiesta disponibilità (attuale),
  - modalità link esterno configurabile (Airbnb / Booking / Interhome).
- [ ] Ingestion semiautomatica prenotazioni da testo email incollato.
- [ ] Migliorie calendario (vista mensile visuale).

---

## Fase 3 — Parsing Email Interhome Automatico

- [ ] Acquisizione email da mailbox dedicata.
- [ ] Parsing template prenotazioni Interhome.
- [ ] Creazione/aggiornamento ospite e soggiorno automatica.
- [ ] Blocco automatico date nel calendario.
- [ ] Log errori parser e gestione eccezioni.

---

## Fase 4 — Crescita Commerciale e Contenuti

- [ ] Espansione contenuti SEO locali multilingua.
- [ ] Migliorie UX su conversione mobile.
- [ ] Gestione recensioni più evoluta (import da piattaforme).
- [ ] Dashboard KPI base (tasso occupazione, provenienza prenotazioni).

---

## Fase 5 — Deploy su Hosting cPanel

> **Nota deploy**: l'hosting non ha Composer. La strategia è:
> - `vendor/` è versionato in git (non ignorato).
> - `public/build/` (assets Vite) è versionato in git.
> - Il deploy consiste nel copiare/pushare i file nella cartella corretta sul server.
> - Niente pipeline CI/CD complessa in questa fase — copia diretta.
> - Migrazioni eseguite manualmente via `php artisan migrate` sul server (se la shell è disponibile) o tramite script SQL generato.

- [ ] Configurazione `.env` di produzione (MySQL, dominio, mail).
- [ ] Prima migrazione su DB MySQL di produzione.
- [ ] Verifica rotte, cache config (`php artisan config:cache`).
- [ ] Redirect HTTP→HTTPS e `www` → no-www.
