# Requisiti Iniziali - Progetto La Caracola Web

## 1. Visione e Obiettivi

### 1.1 Obiettivi principali
- Prenotazioni indirette: permettere agli utenti di richiedere disponibilita o seguire un link esterno.
- Vetrina: valorizzare appartamento e territorio.
- Fiducia: contatti chiari, recensioni, informazioni trasparenti.

### 1.2 Obiettivi di business
- Ridurre dipendenza da portali terzi.
- Incrementare contatti diretti qualificati.
- Costruire una base dati utile per comunicazioni future manuali (newsletter/sconti).

## 2. Ambito Funzionale

### 2.1 Frontend pubblico (MVP)
- Home con hero visivo e messaggio chiaro.
- Pagina appartamento con caratteristiche e servizi.
- Pagina dove siamo con mappa OpenStreetMap.
- Pagina esperienze/dintorni per SEO locale.
- Pagina recensioni (inizialmente manuali).
- Pagina regole della casa (anche per uso QR in appartamento).
- Pagina useful places (anche per uso QR in appartamento).
- Modulo richiesta disponibilita (flow B).

### 2.2 Area privata (MVP)
- Accesso proprietario via email/password.
- Configurazione disponibilita calendario.
- Configurazione prezzi per periodo.
- Configurazione soggiorno minimo (default 3 notti).
- Gestione dati ospiti e soggiorni associati.
- Consultazione ospiti per periodo/date.
- Sezione newsletter (solo consultazione iscritti; invio non implementato).

### 2.3 Funzionalita post-MVP
- Flow C: toggle da area privata tra booking form e link esterno.
- Parsing automatico email prenotazione Interhome.
- Blocco date automatico da prenotazioni importate.
- Cambio password account admin direttamente da area privata (con verifica password attuale).
- Estensioni CMS contenuti/foto più avanzate.

## 3. Requisiti Tecnici

- Stack principale: Laravel + MySQL.
- Frontend assets: TypeScript + PostCSS + minificazione/transpilazione.
- Linting: ESLint.
- Approccio mobile-first e responsive.
- Performance elevate: caching immagini e dati strategici.
- Codice modulare e senza duplicazioni.
- Prediligere open source e assenza di lock-in a pagamento.

## 4. Requisiti Multilingua

- Lingue supportate: italiano, inglese, francese, tedesco.
- Default da browser utente.
- Fallback: italiano.
- Nel MVP traduzioni gestite da file di configurazione.

## 5. SEO (fondamentale)

- Architettura pagine orientata SEO locale.
- Metadati per pagina e lingua.
- Contenuti strategici su Andora, Savona, Liguria e dintorni.
- Keyword strategy multilingua su affitto breve/casa vacanza.
- Struttura pronta a evolvere con contenuti editoriali geolocalizzati.

## 6. Branding e UX

- Brand: La Caracola.
- Palette base: bianco + blu #30596C + accenti oro #c7b772.
- UX orientata a chiarezza, foto impattanti, call-to-action semplici.
- Testo visibile all'utente breve e diretto; contenuto SEO distribuito in sezioni dedicate.

## 7. Dati immobile di riferimento

- Posizione: Via Aurelia 64, Edificio A (Eufrosine) int. 3, 17051 Andora (SV).
- Plus principali: due passi dal mare, balcone vivibile, giardino perimetrale, 2 camere matrimoniali, soggiorno/cucina con divano letto.
- Capacita: fino a 6 posti letto.

## 8. Vincoli e Preferenze operative

- Niente WordPress/plugin premium.
- Hosting target con cPanel e MySQL.
- Deploy automatico da branch main previsto in fase successiva.
- Crescita iterativa: MVP prima, funzionalita avanzate dopo.

## 9. Regole redazionali e documentazione

- Commenti nel codice: inglese.
- Documenti in `docs/`: italiano.
- `README.md`: inglese.
- Ogni modifica tecnica significativa deve aggiornare anche documentazione e istruzioni.
