# Guida Workflow Sviluppatore

## Istruzioni automatiche per Copilot (`.github/`)
Le regole di progetto sono distribuite in file `.instructions.md` che Copilot carica automaticamente:

| File | ApplyTo | Contenuto |
|------|---------|-----------|
| `.github/copilot-instructions.md` | sempre attivo | Panoramica progetto, regole lingua, architettura, aggiornamento docs |
| `.github/instructions/laravel.instructions.md` | `**/*.php` | Convenzioni Laravel, i18n, DB, SEO, performance |
| `.github/instructions/frontend.instructions.md` | `resources/**` | TypeScript, PostCSS, Vite, mobile-first, UX, accessibilita |
| `.github/instructions/documentation.instructions.md` | `docs/**` | Lingua, struttura, cross-reference, obbligo aggiornamento |

## Documenti di riferimento (consultare prima di ogni feature)
- `docs/requirements.md` — requisiti funzionali e non funzionali
- `docs/roadmap.md` — fasi e priorita
- `docs/content-model.md` — cosa sta in config vs DB

## Sequenza di lavoro
1. Leggere fase attuale in `docs/roadmap.md`.
2. Verificare impatto su `docs/requirements.md` e `docs/content-model.md`.
3. Implementare con le convenzioni definite nelle instructions.
4. Aggiornare docs pertinenti.
5. Eseguire lint e build prima di committare.

## Avvio locale con database di produzione

Per verifiche rapide su dati reali e aggiornati e disponibile uno script dedicato:

```bash
npm run start:dbprod
```

Lo script e pensato per macOS e apre due finestre di `Terminal.app`:

- una finestra mantiene aperto il tunnel SSH verso il server di produzione
- una seconda finestra avvia Laravel in locale solo dopo che la porta `127.0.0.1:3307` risponde

Dettagli operativi:

- salva l'ambiente locale corrente in `.env.local`
- usa il file `.env.prod-local` come sorgente della configurazione temporanea
- copia `.env.prod-local` su `.env`
- esegue `php artisan config:clear`
- avvia `php artisan serve`

Ritorno al workflow locale standard:

- `npm run start:local` ripristina `.env` a partire da `.env.local`
- se `.env.local` non esiste ancora, prova a crearlo da `.env`, poi da `.env.example`
- dopo il ripristino esegue `php artisan config:clear` e avvia Laravel + Vite

Vincoli e sicurezza:

- non inserire credenziali reali nella documentazione o nei file tracciati da Git
- usare questo workflow solo per letture, debug e verifiche manuali
- non eseguire `php artisan migrate`, `db:seed`, `migrate:fresh` o altri comandi distruttivi mentre si punta al DB di produzione
- il mailer locale deve restare su `log` per evitare invii email reali
- quando si termina la sessione, ripristinare il proprio `.env` locale e rieseguire `php artisan config:clear`
