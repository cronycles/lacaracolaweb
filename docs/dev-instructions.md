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
