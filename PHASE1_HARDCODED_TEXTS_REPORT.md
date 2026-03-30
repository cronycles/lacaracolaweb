# La Caracola Phase 1 — Hardcoded Italian Texts Report

**Data:** 30 marzo 2026  
**Analisi:** Ricerca di testi hardcoded in italiano non tradotti nei lang file  
**Scope:** Key view della Fase 1

---

## Executive Summary

| Categoria | Trovati | Senza Translation Key | Con Key ma da completare |
|-----------|---------|----------------------|--------------------------|
| apartment.blade.php | 10 | 10 | 0 |
| experiences.blade.php | 15+ | 15+ | 0 |
| reviews.blade.php | 3 | 3 | 0 |
| rules.blade.php | 0 | 0 | 0 |
| useful-places.blade.php | 0 | 0 | 0 |
| map.blade.php | 0 | 0 | 0 |
| **TOTALE** | **28+** | **28+** | **0** |

---

## Dettagli per File

### 1. **resources/views/public/apartment.blade.php**

#### Testo 1: Riga 15
```blade
<span>🛏️ <strong>{{ config('apartment.specs.beds') }}</strong> posti letto</span>
```
- **Testo hardcoded:** `posti letto`
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Soluzione:** Creare chiave `apartment_beds_label` nei lang file

#### Testo 2: Riga 16
```blade
<span>🚪 <strong>{{ config('apartment.specs.bedrooms') }}</strong> camere</span>
```
- **Testo hardcoded:** `camere`
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Soluzione:** Creare chiave `apartment_bedrooms_label` nei lang file

#### Testo 3: Riga 17
```blade
<span>🚿 <strong>{{ config('apartment.specs.bathrooms') }}</strong> bagno</span>
```
- **Testo hardcoded:** `bagno`
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Soluzione:** Creare chiave `apartment_bathrooms_label` nei lang file

#### Testo 4: Riga 18
```blade
<span>🏡 Piano <strong>{{ config('apartment.specs.floor') }}</strong></span>
```
- **Testo hardcoded:** `Piano`
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Soluzione:** Creare chiave `apartment_floor_label` nei lang file

#### Testo 5: Riga 22
```blade
<h2 class="section-title" style="font-size:1.4rem">Servizi inclusi</h2>
```
- **Testo hardcoded:** `Servizi inclusi`
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Soluzione:** Creare chiave `apartment_amenities_title` nei lang file

#### Testo 6: Riga 33
```blade
<h2 class="section-title" style="font-size:1.4rem;margin-top:3rem">Galleria</h2>
```
- **Testo hardcoded:** `Galleria`
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Soluzione:** Creare chiave `apartment_gallery_title` nei lang file

#### Testo 7: Riga 58
```blade
<h2 class="section-title">Interesse? Richiedi la disponibilità</h2>
```
- **Testo hardcoded:** `Interesse? Richiedi la disponibilità`
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Soluzione:** Creare chiave `apartment_cta_title` nei lang file

#### Testo 8: Riga 59
```blade
<p class="section-subtitle" style="margin-inline:auto">Rispondiamo entro 24 ore.</p>
```
- **Testo hardcoded:** `Rispondiamo entro 24 ore.`
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Soluzione:** Creare chiave `apartment_cta_subtitle` nei lang file

#### Testo 9: Riga 48
```blade
<div class="lightbox" role="dialog" aria-label="Foto a schermo intero" aria-modal="true">
```
- **Testo hardcoded:** `Foto a schermo intero` (in aria-label)
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto (accessibility attribute)
- **Soluzione:** Creare chiave `apartment_lightbox_label` nei lang file

#### Testo 10: Riga 50
```blade
<button class="lightbox__close" aria-label="Chiudi">×</button>
```
- **Testo hardcoded:** `Chiudi` (in aria-label)
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto (accessibility attribute)
- **Soluzione:** Creare chiave `apartment_lightbox_close` nei lang file

---

### 2. **resources/views/public/experiences.blade.php**

#### Testo 1-9: Card Label & Titles (Righe 16-82)
```blade
<p class="card__label">🏖️ A 2 minuti</p>
<h3 class="card__title">Le Spiagge di Andora</h3>
```
- **Testi hardcoded:** 
  - `A 2 minuti`, `20 min`, `25 min`, `45 min`, `30 min`, `1h15`, `1h30`, ecc. (time labels)
  - `Le Spiagge di Andora`, `Cervo`, `Alassio`, `Finalborgo`, `Le Caravelle`, `Montecarlo`, `Sanremo`, `Nizza & Mentone`, `Borghi dell'Entroterra` (card titles)
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Nota:** Questi titoli e label potrebbero vs dovrebbero essere centralizati in config/apartment.php con translation key anziché hardcoded

#### Testo 2-9: Card Descriptions (Righe 18-82)
```blade
<p class="card__text">Spiagge sabbiose e ghiaiose direttamente raggiungibili a piedi. Stabilimenti balneari, acque cristalline.</p>
```
- **Testi hardcoded:** 9 descrizioni di esperienze/destinazioni
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Esempio:**
  - "Spiagge sabbiose e ghiaiose direttamente raggiungibili a piedi. Stabilimenti balneari, acque cristalline."
  - "Uno dei borghi medievali più belli della Liguria. Vista panoramica sul mare, centro storico affascinante."
  - "La regina della Riviera ligure. Spiaggia lunga e sabbiosa, shopping, vita notturna, ottima cucina."
  - "Borgo medievale patrimonio UNESCO. Mura cinquecentesche, carruggi caratteristici e paradiso dell'arrampicata sportiva."
  - "Il più grande parco acquatico della Liguria, perfetto per famiglie con bambini."
  - "Il Principato di Monaco con il famoso casinò, il Grand Prix e il porto degli yacht di lusso."
  - "La città dei fiori, famosa per il Festival della Canzone Italiana e il mercato dei fiori. Centro storico medievale 'La Pigna'."
  - "La Francia è a soli 90 minuti. Nizza con la Promenade des Anglais, Mentone con i suoi giardini di agrumi."
  - "Triora (il paese delle streghe), Apricale, Seborga, Bussana Vecchia e Diano Castello: arte, storia e natura."

#### Testo 10: SEO Section H2 (Riga 93)
```blade
<h2>Cosa fare ad Andora e nella Riviera Ligure</h2>
```
- **Testo hardcoded:** `Cosa fare ad Andora e nella Riviera Ligure`
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Nota:** È in una sezione SEO — dovrebbe avere una translation key

#### Testo 11: SEO Section P1 (Riga 95)
```blade
<p>
    Andora si trova nella <strong>Riviera Ligure di Ponente</strong>, a metà strada tra Savona e la Costa Azzurra.
    È il punto di partenza ideale per esplorare le eccellenze del territorio: borghi medievali, parchi naturali,
    spiagge incontaminate e città d'arte come <strong>Genova</strong>, <strong>Sanremo</strong> e <strong>Nizza</strong>.
</p>
```
- **Testo hardcoded:** Intero paragrafo SEO
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Nota:** È testo critico per SEO — deve essere tradotto e gestito via lang file

#### Testo 12: SEO Section H3 (Riga 99)
```blade
<h3>Arrampicata sportiva in Liguria</h3>
```
- **Testo hardcoded:** `Arrampicata sportiva in Liguria`
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto

#### Testo 13: SEO Section P2 (Riga 100-102)
```blade
<p>
    La zona di <strong>Finalborgo</strong> e la Val di Ponci sono riconosciuti a livello internazionale come
    paradiso dell'arrampicata su falesia, con oltre 1000 vie di tutti i livelli.
</p>
```
- **Testo hardcoded:** Intero paragrafo SEO
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Nota:** È testo critico per SEO — deve essere tradotto

---

### 3. **resources/views/public/reviews.blade.php**

#### Testo 1: Review 1 (Righe 19-20)
```blade
<p class="review-card__text">
    "Appartamento perfetto, pulito e a due passi dalla spiaggia. Il balcone con vista mare
    è semplicemente meraviglioso. Torneremo senz'altro!"
</p>
```
- **Testo hardcoded:** Intero testo di review
- **Lingua:** Italiano
- **Translation key:** ❌ Non esiste
- **Stato:** Completamente non tradotto
- **Nota:** Reviews sono gestite manualmente. Possono rimanere hardcoded, ma l'autore (Marco R.) deve essere sempre in italiano mentre la struttura dovrebbe essere multi-lingua. Considerare di usare translation key per ogni review.

#### Testo 2: Review 2 (Righe 29-31)
```blade
<p class="review-card__text">
    "Superb location, everything you need is within walking distance.
    The apartment has a lovely sea view and a spacious garden."
</p>
```
- **Testo hardcoded:** Testo review in inglese
- **Lingua:** Inglese (non italiano — ECCEZIONE)
- **Stato:** Correttamente gestito (è in inglese, coerente con i dati)
- **Nota:** Questa review è già in inglese, quindi no.

#### Testo 3: Review 3 (Righe 46-48)
```blade
<p class="review-card__text">
    "Magnifique appartement face à la mer. La terrasse est idéale pour les repas en famille.
    Andora est un village adorable, loin du tourisme de masse."
</p>
```
- **Testo hardcoded:** Testo review in francese
- **Lingua:** Francese (non italiano — ECCEZIONE)
- **Stato:** Correttamente gestito (è in francese, coerente con i dati)
- **Nota:** Review in francese pura — OK.

---

### 4. **resources/views/public/rules.blade.php**

✅ **Status:** NESSUN testo hardcoded trovato

Tutti i testi utilizzano translation key tramite `__($rule['title_key'])` e `__($rule['text_key'])`.

---

### 5. **resources/views/public/useful-places.blade.php**

✅ **Status:** NESSUN testo hardcoded trovato

Tutti gli heading utilizzano correttamente `{{ __('app.places_supermarkets') }}` e `{{ __('app.places_restaurants') }}`.

---

### 6. **resources/views/public/map.blade.php**

✅ **Status:** NESSUN testo hardcodo trovato

Tutti i testi utilizzano translation key correttamente (`__('app.map_title')`, `__('app.map_subtitle')`, ecc).

---

## Riepilogo delle Azioni Necessarie

### Priorità 1: Critical (SEO & Accessibility)

1. **experiences.blade.php** — SEO section text  
   - "Cosa fare ad Andora e nella Riviera Ligure"
   - Intere paragrafi SEO (Riga 93-102)
   - ⚠️ **Impact:** Alto (SEO, multilingual site)
   - ✅ **Fix:** Aggiungere translation key `experiences_seo_h2`, `experiences_seo_p1`, `experiences_seo_h3`, `experiences_seo_p2` nei lang file

2. **apartment.blade.php** — Accessibility Labels  
   - "Foto a schermo intero" (aria-label)
   - "Chiudi" (aria-label)
   - ⚠️ **Impact:** Medio (accessibility)
   - ✅ **Fix:** Aggiungere translation key `apartment_lightbox_label`, `apartment_lightbox_close`

### Priorità 2: High (User-Facing Content)

3. **apartment.blade.php** — Specs Labels  
   - "posti letto", "camere", "bagno", "Piano"
   - ⚠️ **Impact:** Alto (visibilità utente)
   - ✅ **Fix:** Aggiungere translation key `apartment_beds_label`, `apartment_bedrooms_label`, `apartment_bathrooms_label`, `apartment_floor_label`

4. **apartment.blade.php** — Section Titles  
   - "Servizi inclusi", "Galleria"
   - ⚠️ **Impact:** Alto (visibilità)
   - ✅ **Fix:** Aggiungere translation key `apartment_amenities_title`, `apartment_gallery_title`

5. **apartment.blade.php** — CTA Section  
   - "Interesse? Richiedi la disponibilità"
   - "Rispondiamo entro 24 ore."
   - ⚠️ **Impact:** Alto (CTA critica)
   - ✅ **Fix:** Aggiungere translation key `apartment_cta_title`, `apartment_cta_subtitle`

### Priorità 3: Medium (Experiences - Consider Architecture)

6. **experiences.blade.php** — Card Titles & Descriptions  
   - 9 card titles + 9 card descriptions (hardcoded)
   - ⚠️ **Impact:** Medio (è contenuto statico ma dovrebbe essere gestito centralmente)
   - ✅ **Fix Option A:** Aggiungere translation key per ogni card (es. `experience_1_title`, `experience_1_desc`, ecc.)
   - ✅ **Fix Option B:** Centralizzare in `config/apartment.php` con chiavi di traduzione

### Priorità 4: Low (Review Content - Manage as Content)

7. **reviews.blade.php** — Review Text  
   - "Appartamento perfetto, pulito..." (Review Marco R.)
   - ⚠️ **Impact:** Basso (reviews sono gestite manualmente in MVP)
   - ✅ **Fix:** Considerare translation key per ogni review oppure gestire come contenuto statico in config/apartment.php

---

## Raccomandazioni Globali

### 1. Review Patterns
Tutte le view di Fase 1 accettate utilizzano correttamente `__()` e `@lang()` tranne per gli elementi hardcoded identificati sopra. **Nessun codice passa text direttamente via config senza translation key.**

### 2. SEO Content Management
Il testo SEO in `experiences.blade.php` è hardcoded ma dovrebbe seguire lo stesso pattern di `home.blade.php`:
```php
// home.blade.php (CORRETTO)
{{ __('app.seo_home_h2') }}
{!! __('app.seo_home_p1') !!}

// experiences.blade.php (SBAGLIATO)
<h2>Cosa fare ad Andora e nella Riviera Ligure</h2> // hardcoded
```

### 3. Accessibility
Tutte le aria-label in italiano devono avere translation key, non essere hardcoded.

### 4. Next Steps
1. Estendere `lang/{locale}/app.php` con chiavi mancanti
2. Aggiornare `apartment.blade.php` e `experiences.blade.php`
3. Testare multilingua in tutte e 4 le lingue (it, en, fr, de)
4. Verificare SEO rendering in tutte le lingue

---

## Appendice: Translation Keys da Creare

```php
// lang/it/app.php (AGGIUNGERE)
'apartment_beds_label' => 'posti letto',
'apartment_bedrooms_label' => 'camere',
'apartment_bathrooms_label' => 'bagno',
'apartment_floor_label' => 'Piano',
'apartment_amenities_title' => 'Servizi inclusi',
'apartment_gallery_title' => 'Galleria',
'apartment_cta_title' => 'Interesse? Richiedi la disponibilità',
'apartment_cta_subtitle' => 'Rispondiamo entro 24 ore.',
'apartment_lightbox_label' => 'Foto a schermo intero',
'apartment_lightbox_close' => 'Chiudi',

// Experiences — SEO section
'experiences_seo_h2' => 'Cosa fare ad Andora e nella Riviera Ligure',
'experiences_seo_p1' => 'Andora si trova nella <strong>Riviera Ligure di Ponente</strong>, a metà strada tra Savona e la Costa Azzurra. È il punto di partenza ideale per esplorare le eccellenze del territorio: borghi medievali, parchi naturali, spiagge incontaminate e città d\'arte come <strong>Genova</strong>, <strong>Sanremo</strong> e <strong>Nizza</strong>.',
'experiences_seo_h3' => 'Arrampicata sportiva in Liguria',
'experiences_seo_p2' => 'La zona di <strong>Finalborgo</strong> e la Val di Ponci sono riconosciuti a livello internazionale come paradiso dell\'arrampicata su falesia, con oltre 1000 vie di tutti i livelli.',

// Experiences — Card content (optional — dipende da architettura decisa)
'experience_1_title' => 'Le Spiagge di Andora',
'experience_1_desc' => 'Spiagge sabbiose e ghiaiose direttamente raggiungibili a piedi. Stabilimenti balneari, acque cristalline.',
// ... ecc per altre card
```

---

**Report Generated:** 2026-03-30  
**Status:** ✅ Complete
