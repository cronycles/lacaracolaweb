# Fase 2 - Checklist test manuale

Questa checklist serve per validare manualmente le feature completate nella Fase 2.

## Ambito

- SEO multilingua home
- Calendario visuale admin (3 mesi)

## Preparazione ambiente

1. Avvia il progetto in locale con `npm run start`.
2. Verifica che esista almeno una prenotazione futura in admin.
3. Verifica che esista almeno un blocco `owner` e almeno un blocco `maintenance` o `cleaning`.
4. Esegui `php artisan view:clear` se hai dubbi sulla cache Blade.

## 1) SEO multilingua home

### 1.1 Blocco SEO visibile in tutte le lingue

1. Apri la home in IT, EN, FR, DE cambiando lingua dal selettore UI (o con `?lang=it|en|fr|de`).
2. Vai alla sezione SEO in fondo pagina.

Esito atteso:
- La sezione esiste in tutte e 4 le lingue.
- Titoli e paragrafi cambiano in base alla lingua corrente.
- I tag `<strong>` dentro il testo sono renderizzati correttamente.

### 1.2 Hreflang in head

1. Apri la home.
2. Ispeziona il sorgente HTML nella sezione `<head>`.

Esito atteso:
- Sono presenti i link `rel="alternate"` per `it`, `en`, `fr`, `de`.
- E presente anche `hreflang="x-default"`.

### 1.3 Regressione SEO base

1. Verifica che `<title>` e `<meta name="description">` siano ancora valorizzati.
2. Verifica presenza di canonical e tag Open Graph principali.

Esito atteso:
- Nessuna regressione sui metadati SEO esistenti.

## 2) Calendario visuale admin

### 2.1 Rendering griglie

1. Apri area admin > Calendario.

Esito atteso:
- In alto sono visibili 3 mesi (mese corrente + 2 successivi).
- Ogni mese ha intestazione giorni da Lun a Dom.

### 2.2 Colori celle

Confronta i dati reali con la griglia.

Esito atteso:
- Prenotazioni: celle blu scuro `#30596C`.
- Blocchi owner: celle viola `#9333ea`.
- Blocchi maintenance/cleaning: celle gialle `#f59e0b`.
- La legenda colori e coerente con la griglia.

### 2.3 Regole intervalli date

1. Scegli una prenotazione con check-in/check-out noti.
2. Verifica che il giorno di check-in sia colorato e il giorno di check-out non sia colorato (notti).
3. Scegli un blocco manuale e verifica inclusione sia di data inizio sia di data fine.

Esito atteso:
- Prenotazioni trattate come intervallo `[checkin, checkout)`.
- Blocchi trattati come intervallo chiuso `[start_date, end_date]`.

### 2.4 Responsive base

1. Riduci viewport a larghezza mobile (es. 390px).

Esito atteso:
- Le griglie restano leggibili e non rompono layout/admin sidebar.
- La lista eventi sotto la griglia continua a funzionare.

## 3) Smoke finale

1. Esegui `npm run build`.
2. Esegui `php artisan view:clear`.
3. Naviga velocemente home pubblica e calendario admin.

Esito atteso:
- Build OK.
- Nessun errore a schermo o rendering bloccante.

## Report suggerito (da compilare)

- Data test:
- Ambiente testato (locale/staging):
- Browser/dispositivo:
- Esito SEO multilingua home: PASS/FAIL
- Esito calendario visuale admin: PASS/FAIL
- Note bug aperti:
