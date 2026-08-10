# Alloggiati Web — Documentazione Integrazione

> File di riferimento tecnico completo per l'integrazione con il servizio SOAP
> della Polizia di Stato per la comunicazione degli ospiti.
> Tutti i codici ufficiali sono nelle tabelle CSV di questa stessa cartella.

---

## 1. Panoramica del servizio

Il **Portale Alloggiati Web** è il sistema del Ministero dell'Interno per la
comunicazione obbligatoria dei dati degli ospiti da parte delle strutture
ricettive. La comunicazione avviene tramite web service SOAP 1.1.

- **Endpoint SOAP**: `https://alloggiatiweb.poliziadistato.it/service/service.asmx`
- **WSDL**: `https://alloggiatiweb.poliziadistato.it/service/service.asmx?wsdl`
- **Protocollo**: SOAP 1.1 (non 1.2)
- **Credenziali** (env): `GUEST_REPORTING_UTENTE`, `GUEST_REPORTING_PASSWORD`, `GUEST_REPORTING_WS_KEY`

---

## 2. Operazioni SOAP disponibili

### `GenerateToken`
Genera il token di sessione (valido per N chiamate/ore — non documentato).

```
Request:  Utente, Password, WsKey
Response: GenerateTokenResult → { token: string, ... }
```

**GOTCHA**: il token è in `$result->GenerateTokenResult->token`, NON in
`$result->token`.

### `Test`
Valida le schedine senza inviarle. Usare sempre prima di `Send`.

```
Request:  Utente, token, ElencoSchedine (ArrayOfString)
Response: TestResult → { esito: bool, result: { SchedineValide: int,
          Dettaglio: { EsitoOperazioneServizio: { ErroreDettaglio: string } } } }
```

### `Send`
Invia le schedine definitivamente.

```
Request:  Utente, token, ElencoSchedine (ArrayOfString)
Response: SendResult → stessa struttura di TestResult
```

### `GestioneAppartamenti_Test` / `GestioneAppartamenti_Send`
Varianti per strutture multi-appartamento — richiedono `IdAppartamento` (string, 6 chars).
Non ancora in uso in questo progetto.

---

## 3. Struttura `ElencoSchedine`

**GOTCHA CRITICO**: il WSDL dichiara `ElencoSchedine` come `ArrayOfString`.
PHP `SoapClient` lo deve ricevere come:

```php
$elenco = ['string' => [$record1, $record2, ...]];
```

Se si passa una stringa concatenata, il client invia `<ns1:ElencoSchedine/>`
(tag vuoto) → risposta: *"Elenco Schedine Vuoto"*.

---

## 4. Formato Record (Tracciato — da `TracciatoRecord.png`)

Ogni schedina contiene **168 caratteri di dati**. Il servizio richiede inoltre
`CR+LF` dopo ogni riga tranne l'ultima, per una lunghezza di 170 caratteri sulle
righe intermedie. `ArrayOfString` separa gli elementi SOAP, ma non sostituisce
il terminatore previsto dal tracciato.
Tutti i campi non valorizzati devono essere riempiti con **spazi**.

| Campo | DA | A | Len | Tipo 16-17-18 | Tipo 19-20 | Note |
|---|---|---|---|---|---|---|
| Tipo Alloggiato | 0 | 1 | 2 | Obbligatorio | Obbligatorio | Tabella `tipo_alloggiato.csv` |
| Data Arrivo | 2 | 11 | 10 | Obbligatorio | Obbligatorio | `gg/mm/aaaa` (con slash) |
| Giorni Permanenza | 12 | 13 | 2 | Obbligatorio | Obbligatorio | Max 30, zero-padded (`02`) |
| Cognome | 14 | 63 | 50 | Obbligatorio | Obbligatorio | Uppercase, spazio-padded |
| Nome | 64 | 93 | 30 | Obbligatorio | Obbligatorio | Uppercase, spazio-padded |
| Sesso | 94 | 94 | 1 | Obbligatorio | Obbligatorio | `1`=M, `2`=F |
| Data Nascita | 95 | 104 | 10 | Obbligatorio | Obbligatorio | `gg/mm/aaaa` (con slash) |
| Comune Nascita | 105 | 113 | 9 | Solo se IT | Solo se IT | Codice 9-digit da `comuni.csv` |
| Provincia Nascita | 114 | 115 | 2 | Solo se IT | Solo se IT | Sigla 2 char es. `GE` |
| Stato Nascita | 116 | 124 | 9 | Obbligatorio | Obbligatorio | Codice 9-digit da `stati.csv` |
| Cittadinanza | 125 | 133 | 9 | Obbligatorio | Obbligatorio | Codice 9-digit da `stati.csv` |
| Tipo Documento | 134 | 138 | 5 | Obbligatorio | **Blank** | Codice 5-char da `documenti.csv` |
| Numero Documento | 139 | 158 | 20 | Obbligatorio | **Blank** | Uppercase |
| Luogo Rilascio Doc | 159 | 167 | 9 | Obbligatorio | **Blank** | Codice da `stati.csv` o `comuni.csv` |

**Totale: 168 char.** Nascita estera → Comune/Provincia restano spazi; Stato Nascita obbligatorio.

---

## 5. Tabelle codici ufficiali

Le tabelle CSV in questa cartella sono la fonte autoritativa per i codici.

### `tipo_alloggiato.csv`
| Codice | Descrizione |
|---|---|
| 16 | OSPITE SINGOLO |
| 17 | CAPO FAMIGLIA |
| 18 | CAPO GRUPPO |
| 19 | FAMILIARE (doc → blank) |
| 20 | MEMBRO GRUPPO (doc → blank) |

### `stati.csv` — Paesi principali
| ISO-2 | Codice 9-digit | Paese |
|---|---|---|
| IT | 100000100 | ITALIA |
| DE | 100000216 | GERMANIA |
| FR | 100000215 | FRANCIA |
| GB | 100000219 | REGNO UNITO |
| ES | 100000239 | SPAGNA |
| CH | 100000241 | SVIZZERA |
| AT | 100000203 | AUSTRIA |
| NL | 100000232 | PAESI BASSI |
| US | 100000536 | STATI UNITI D'AMERICA |
| RU | 100000245 | FEDERAZIONE RUSSA |
| CN | 100000314 | CINA |
| *(altri)* | *(vedi stati.csv)* | — |

Il mapping completo ISO-2 → 9-digit è in `PoliziaStatoAlloggiatiDriver::countryIsoToAlloggiati()`.

### `documenti.csv` — Codici usati
| Codice | Descrizione | Mapping interno |
|---|---|---|
| PASOR | PASSAPORTO ORDINARIO | `passport` |
| IDENT | CARTA DI IDENTITA' | `id_card` |
| PATEN | PATENTE DI GUIDA | `driving_license` |
| CERID | CERTIFICATO D'IDENTITA' | *(fallback)* |

### `comuni.csv`
11 295 righe. Formato: `Codice,Descrizione,Provincia,DataFineVal`
- `DataFineVal` vuoto = comune attualmente valido
- `DataFineVal` non vuoto = comune storico (fuso/rinominato) — usato per luoghi di nascita storici
- Lookup: `ItalianMunicipalities::findCode(string $name, ?string $province)` carica il CSV una volta
  con cache statica, normalizza a lowercase, preferisce voci non scadute, usa la provincia per disambiguare

---

## 6. Mappa del codice (codebase)

```
app/Services/GuestReporting/
├── PoliziaStatoAlloggiatiDriver.php   ← UNICO punto che conosce SOAP + record format
│                                        Country lookup: Country::where('iso2', ...)->value('alloggiati_code')
│                                        Municipality lookup: delegato a ItalianMunicipalities
├── GuestRecord.php                    ← DTO immutabile con i dati di un ospite
├── GuestReportingServiceInterface.php ← Interfaccia che il driver implementa
└── Data/
    └── ItalianMunicipalities.php      ← Lookup nome comune → codice 9-digit (da DB municipalities)

app/Models/
├── Country.php                        ← Tabella countries (seeded da stati.csv)
├── Municipality.php                   ← Tabella municipalities (seeded da comuni.csv)
└── GuestType.php                      ← Tabella guest_types (seeded da tipo_alloggiato.csv)

app/Http/Controllers/Admin/
└── GuestReportingController.php       ← Valida input form, costruisce GuestRecord[], chiama driver
                                         Passa $countries e $guestTypes alla view

resources/views/admin/guest-reporting/
└── show.blade.php                     ← Form con $guestTypes (tipo alloggiato), $countries (stati),
                                         dati ospite, doc

database/seeders/
├── CountriesSeeder.php                ← Popola countries da stati.csv (236 righe)
├── MunicipalitiesSeeder.php           ← Popola municipalities da comuni.csv (11 294 righe)
└── GuestTypesSeeder.php               ← Popola guest_types (5 righe hardcoded)

docs/AlloggiatiWeb/                    ← Tabelle ufficiali + questa documentazione
├── INTEGRATION.md                     ← Questo file
├── TracciatoRecord.png                ← Spec ufficiale campi del record (fonte primaria)
├── stati.csv   → DB countries         (fonte per il seeder)
├── comuni.csv  → DB municipalities    (fonte per il seeder)
├── tipo_alloggiato.csv → DB guest_types (fonte per il seeder)
├── documenti.csv                      ← Codici documenti (mapping hardcoded nel driver)
├── MANUALEALBERGHI.pdf
└── MANUALEWS.pdf

scripts/
└── test-soap5.php                     ← Script di test SOAP standalone (fuori da Laravel)
```

### `PoliziaStatoAlloggiatiDriver` — responsabilità interne
- Token: `getToken()` → `GenerateTokenResult->token`
- Record: `buildRecord(GuestRecord)` → stringa 168 char
- Elenco: `buildElenco(array $records)` → `['string' => [...]]`
- Risposta: `parseResponse(object)` → `bool` (controlla `esito`)
- Mapping: `countryIsoToAlloggiati()`, `documentTypeToAlloggiati()`, `genderToAlloggiati()`

---

## 7. `GuestRecord` — campi obbligatori

```php
new GuestRecord(
    tipoAlloggiato:           '16',              // '16'–'20'
    arrivalDate:              '01/07/2026',       // d/m/Y — da $booking->checkin->format('d/m/Y')
    stayNights:               2,                  // int — da $booking->nights (max 30)
    lastName:                 'ROSSI',
    firstName:                'MARIO',
    gender:                   'M',                // 'M' o 'F'
    birthDate:                '1985-06-15',        // Y-m-d
    birthMunicipality:        'GENOVA',            // nome comune (per italiani)
    birthProvince:            'GE',                // sigla 2-char (per italiani)
    birthCountryCode:         'IT',               // ISO-2
    nationalityCode:          'IT',               // ISO-2
    documentType:             'id_card',           // 'passport'|'id_card'|'driving_license'
    documentNumber:           'AB1234567',
    documentIssuePlace:       'GENOVA',            // comune (IT) o country name (estero)
    documentIssueCountryCode: 'IT',               // ISO-2
);
```

Per tipo 19/20: i campi doc vengono ignorati (il driver scrive spazi).  
Per nascita estera: `birthMunicipality`/`birthProvince` vengono ignorati.

---

## 8. Gotcha e problemi noti

| Problema | Causa | Soluzione |
|---|---|---|
| *"Elenco Schedine Vuoto"* | `ElencoSchedine` passato come stringa invece di `ArrayOfString` | `['string' => [$rec1, ...]]` |
| *"Data di Arrivo Non Valida"* | Formato data sbagliato (`Ymd`, `dmY`) | Usare `gg/mm/aaaa` con slash (10 char) |
| *"Giorni di Permanenza Errati"* | Campo mancante o a zero | Pos 12-13, zero-padded, min 1, max 30 |
| Token in campo sbagliato | `$result->token` non esiste | È in `$result->GenerateTokenResult->token` |
| Record troppo corto/lungo | Posizioni calcolate male o terminatore assente | Dati: 168 caratteri; righe non finali: 168 + `CRLF` |
| Comune non trovato in IT | Case mismatch o comune storico | `ItalianMunicipalities::findCode()` normalizza lowercase; usa `$province` per disambiguare |
| Paese non mappato | ISO-2 non in `countryIsoToAlloggiati()` | Aggiungere da `stati.csv` (colonna `Codice`) |

---

## 9. Come disaccoppiare il servizio dall'applicazione

Se in futuro si vuole estrarre GuestReporting come microservizio o pacchetto autonomo:

### Cosa isolare
Tutto ciò che è in `app/Services/GuestReporting/` è già ben separato:
- `GuestReportingServiceInterface` è l'unico punto di contatto con il resto dell'app
- Il driver non dipende da modelli Eloquent — riceve solo `GuestRecord[]`
- Le tabelle CSV in `docs/AlloggiatiWeb/` sono dati di configurazione puri

### Passi per estrarlo come pacchetto Composer
1. Creare `packages/alloggiatiweb/src/` con `PoliziaStatoAlloggiatiDriver`, `GuestRecord`, `GuestReportingServiceInterface`, `ItalianMunicipalities`
2. Spostare i CSV in `packages/alloggiatiweb/data/`
3. Aggiornare `ItalianMunicipalities::loadIndex()` per accettare il path CSV come parametro (o via config)
4. Registrare il service provider nel `composer.json` della main app
5. Fare binding del driver nell'`AppServiceProvider` (`$this->app->bind(GuestReportingServiceInterface::class, PoliziaStatoAlloggiatiDriver::class)`)

### Passi per estrarlo come microservizio HTTP
1. Creare una Laravel mini-app (o Lumen) con le sole classi del driver
2. Esporre una route `POST /report` che riceve `GuestRecord[]` in JSON e chiama `Send()`
3. Nella main app, sostituire il driver con un `HttpAlloggiatiDriver` che chiama quella route
4. Gestire autenticazione inter-servizio con API key condivisa (environment variable)

### Cosa NON spostare fuori
- `GuestReportingController` rimane nella main app (conosce `Booking`, `Person`, autenticazione)
- La view Blade rimane nella main app
- La logica di costruzione di `GuestRecord[]` da `$booking` rimane nel controller

---

## 10. Test manuale

Script standalone in `scripts/test-soap5.php` — non richiede Laravel avviato, legge `.env` direttamente.

```bash
# Dalla root del progetto:
php scripts/test-soap5.php
```

Risposta attesa per ogni record:
```
esito=true valid=1 error=
```

Il script include già un test per ospite italiano (tipo 16, nato a Genova) e
uno per ospite straniero (tipo 16, tedesco, PASOR, luogo rilascio = stato DE).
