# Fase 2 — Lavori in sospeso

Questo file traccia le feature rimanenti della Fase 2 ancora da implementare.
Aggiornato il 26/03/2026 dopo il commit `016b42f` (ingestion).

## Stato attuale

| # | Feature | Stato |
|---|---------|-------|
| 1 | Date picker + newsletter toggle | ✅ Completato (`fa59484`, `eb60b88`) |
| 2 | Flow C — booking mode switch | ✅ Completato (`c9b633f`) |
| 3 | SEO multilingua home | 🔲 Da fare |
| 4 | Ingestion email semi-automatica | ✅ Completato (`016b42f`) |
| 5 | Calendario visuale admin | 🔲 Da fare |
| — | Checklist test manuale | ⏳ Da produrre dopo i punti 3 e 5 |

---

## 3. SEO multilingua home

**Obiettivo**: la sezione SEO in fondo a `resources/views/public/home.blade.php` è attualmente visibile solo in italiano (`@if(app()->getLocale() === 'it')`). Va resa multilingua per tutte e 4 le lingue.

### A) Chiavi da aggiungere a tutti e 4 i file lang

In `lang/{it,en,fr,de}/app.php`, dopo il gruppo `booking_external_*`, aggiungere:

```php
// --- SEO text blocks (bottom of home page) ---
'seo_home_h2' => '...',
'seo_home_p1' => '...',   // può contenere <strong>, usare {!! ... !!}
'seo_home_h3' => '...',
'seo_home_p2' => '...',
```

Contenuti per lingua:

**IT:**
- `seo_home_h2`: `Appartamento in affitto ad Andora — La Caracola`
- `seo_home_p1`: testo con La Caracola, Marina di Andora, Riviera Ligure di Ponente, 6 posti letto
- `seo_home_h3`: `Affitto breve Andora e dintorni`
- `seo_home_p2`: testo con affitto breve Andora, casa vacanze Liguria, vicino Alassio

**EN:**
- `seo_home_h2`: `Holiday apartment for rent in Andora — La Caracola`
- `seo_home_h3`: `Short-term rental near Alassio, Liguria`

**FR:**
- `seo_home_h2`: `Appartement de vacances à Andora — La Caracola`
- `seo_home_h3`: `Location courte durée près d'Alassio, Ligurie`

**DE:**
- `seo_home_h2`: `Ferienwohnung in Andora — La Caracola`
- `seo_home_h3`: `Kurzzeitvermietung nahe Alassio, Ligurien`

### B) `resources/views/public/home.blade.php`

Sostituire il blocco `@if(app()->getLocale() === 'it') ... @endif` con:

```blade
{{-- SEO text block — localised for all 4 languages --}}
<section class="seo-section" aria-label="{{ __('app.seo_home_h2') }}">
    <div class="container seo-section__content">
        <h2>{{ __('app.seo_home_h2') }}</h2>
        <p>{!! __('app.seo_home_p1') !!}</p>
        <h3>{{ __('app.seo_home_h3') }}</h3>
        <p>{!! __('app.seo_home_p2') !!}</p>
    </div>
</section>
```

### C) `resources/views/layouts/app.blade.php`

Aggiungere hreflang nella `<head>` dopo i tag canonical/OG esistenti:

```blade
{{-- hreflang alternate links for multilingual SEO --}}
@foreach (['it', 'en', 'fr', 'de'] as $loc)
    <link rel="alternate" hreflang="{{ $loc }}" href="{{ url('/') }}?lang={{ $loc }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ url('/') }}">
```

---

## 5. Calendario visuale admin

**Obiettivo**: aggiungere una griglia mensile (3 mesi: corrente + 2 successivi) in cima a `resources/views/admin/calendar.blade.php`, sopra la lista esistente.

**Il `CalendarController::index()`** passa già:
- `$bookings` — collection con `checkin`, `checkout` (Carbon o stringa), relazione `person`
- `$blocks` — collection con `start_date`, `end_date`, `type` (`owner`/`maintenance`/`cleaning`)

### Colorazione celle
| Colore | Significato |
|--------|-------------|
| `#30596C` (blu scuro) | Prenotazione ospite |
| `#9333ea` (viola) | Blocco proprietario (`owner`) |
| `#f59e0b` (giallo) | Manutenzione/pulizie (`maintenance`, `cleaning`) |

### Struttura Blade da aggiungere

```blade
@php
    use Carbon\Carbon;
    $months = [
        Carbon::now()->startOfMonth(),
        Carbon::now()->addMonth()->startOfMonth(),
        Carbon::now()->addMonths(2)->startOfMonth(),
    ];

    // Build lookup sets
    $bookedDays = [];
    foreach ($bookings as $b) {
        $d = Carbon::parse($b->checkin)->copy();
        $end = Carbon::parse($b->checkout);
        while ($d->lt($end)) {
            $bookedDays[$d->format('Y-m-d')] = true;
            $d->addDay();
        }
    }
    $ownerDays = [];
    $maintDays = [];
    foreach ($blocks as $bl) {
        $d = Carbon::parse($bl->start_date)->copy();
        $end = Carbon::parse($bl->end_date);
        $target = &($bl->type === 'owner' ? $ownerDays : $maintDays);
        while ($d->lte($end)) {
            $target[$d->format('Y-m-d')] = true;
            $d->addDay();
        }
    }
    unset($target);
@endphp
```

Poi per ogni mese, griglia CSS a 7 colonne con header Lun…Dom + celle colorate via `background`.

Aggiungere anche legenda colori sotto le griglie.

---

## Note tecniche

- Carbon è disponibile (già incluso in Laravel)
- `$blocks->type` può essere: `owner`, `maintenance`, `cleaning`
- La sezione SEO in `app.blade.php` ha già canonical + OG + meta description locale-aware via `config('apartment.seo.' . app()->getLocale())`
- Nessuna migration necessaria per i punti 3 e 5
- Dopo le modifiche: `npm run build` + `php artisan view:clear`
