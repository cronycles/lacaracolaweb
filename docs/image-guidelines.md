# Linee Guida Immagini

## Panoramica

Tutte le immagini del sito vengono gestite da [config/apartment.php](../config/apartment.php) sotto la chiave `images`. Non è necessario modificare i template Blade: bastano i path config.

## Tre Categorie di Immagini

### 1. Hero — 1920 × 1080

**Uso:** Immagini principali della home, a pieno schermo.

**Formato:**
- PNG o JPG (preferibilmente JPG per foto, PNG per grafica).
- NO SVG.

**Specifiche tecniche:**
- Colore: **sRGB**.
- Peso massimo: **1-2 MB** per file.
- Compressione: JPG qualità 85-90; PNG compresso con TinyPNG/ImageOptim.
- Rapporto: esatto **16:9** (1920×1080).

**Composizione:**
- Soggetto importante al centro.
- Margini di sicurezza sui bordi (l'immagine verrà croppata con `background-size: cover` su schermi piccoli).
- Evita testo o dettagli critici ai bordi.

**Naming:**
- `hero-1.jpg`, `hero-2.jpg`, `hero-3.jpg` (il sito supporta un array).

**Esempio in config/apartment.php:**
```php
'hero' => [
    'images/hero-1.jpg',
    'images/hero-2.jpg',
    'images/hero-3.jpg',
],
```

---

### 2. Gallery — ~1200 × 800

**Uso:** Foto della sezione "L'appartamento", galleria interna.

**Formato:**
- JPG (preferibilmente, per foto) o PNG (per grafica/icone).
- NO SVG.

**Specifiche tecniche:**
- Colore: **sRGB**.
- Peso: **300-700 KB** per file idealmente.
- Compressione: JPG qualità 80-85; PNG compresso.
- Rapporto: circa **3:2** (1200×800 è suggerito, ma non rigido — il sito scala automaticamente).

**Composizione:**
- Composizione libera, il sito si adatta automaticamente.
- Foto ben illuminate e ben composte.

**Naming:**
- `apartment-1.jpg`, `apartment-2.jpg`, `apartment-3.jpg`, ..., `apartment-6.jpg` (o più/meno a seconda delle foto).

**Esempio in config/apartment.php:**
```php
'gallery' => [
    'images/apartment-1.jpg',
    'images/apartment-2.jpg',
    'images/apartment-3.jpg',
    'images/apartment-4.jpg',
    'images/apartment-5.jpg',
    'images/apartment-6.jpg',
],
```

---

### 3. OG — 1200 × 630

**Uso:** Immagine Open Graph. Appare in anteprima quando il link è condiviso su WhatsApp, Facebook, Telegram, ecc. (NON visibile come blocco del sito).

**Formato:**
- **PNG** (obbligatorio — NO SVG, evita JPG puro).
- Consigliato per preservare nitidezza di loghi/grafica.

**Specifiche tecniche:**
- Colore: **sRGB**.
- Peso massimo: **300-500 KB** (i crawler social sono esigenti).
- Compressione: PNG compresso con TinyPNG.
- Rapporto: esatto **1200 × 630** (~1.91:1).

**Composizione:**
- Wordmark blu + sfondo a tema (colore brand, pattern leggero, sfondo solido).
- Il logo deve occupare **almeno 40-60% dello spazio** — non minuscolo su uno sfondo bianco.
- Margini: almeno **100px di respiro dai bordi**.
- Non inserire testo piccolo o dettagli che potrebbero diventare illeggibili.

**Naming:**
- `og-default.png` (il sito ne supporta uno solo).

**Esempio in config/apartment.php:**
```php
'og' => 'images/og-default.png',
```

---

## Flusso di Lavoro

### 1. Esportazione
- Usa Photoshop, Figma, o qualsiasi editor.
- **Esporta con le dimensioni esatte** + profilo colore **sRGB**.

### 2. Compressione
- Carica i file su **TinyPNG** (online gratuitamente) per ridurre il peso.
- Oppure usa **ImageOptim** su macOS per batch compress.
- Verifica che il peso risultante sia entro i limiti consigliati.

### 3. Upload
- Copia tutti i file nella cartella **[public/images/](../public/images/)**.

### 4. Configurazione
- Apri **[config/apartment.php](../config/apartment.php)**.
- Aggiorna la chiave `'images'` con i nuovi nomi file e path relativi.

### 5. Pulizia Cache
- Esegui nel terminale:
  ```bash
  php artisan config:clear
  ```

### 6. Verifica
- Refresh il browser (`F5` o `Cmd+R`).
- Verifica che tutte le immagini carichino correttamente.
- Testa la preview su link social (es. con https://www.opengraphprotocol.org/).

---

## Fallback Automatico (Gallery)

Se un file di galleria non esiste in `public/images/`, il sito mostra automaticamente un placeholder da **placehold.co**. Questo permette di aggiungere/rimuovere item dalla gallery senza errori.

Puoi quindi aggiungere o togliere voci da `'gallery'` dinamicamente senza toccaredel codice.

---

## Template Corrente in config/apartment.php

```php
'images' => [
    'hero'    => [
        'images/hero-1.jpg',
        'images/hero-2.jpg',
        'images/hero-3.jpg',
    ],
    'gallery' => [
        'images/apartment-1.jpg',
        'images/apartment-2.jpg',
        'images/apartment-3.jpg',
        'images/apartment-4.jpg',
        'images/apartment-5.jpg',
        'images/apartment-6.jpg',
    ],
    'og'      => 'images/og-default.png',
],
```

---

## Checklist Rapida

- [ ] Hero: 1920×1080, JPG/PNG, ≤2MB, sRGB.
- [ ] Hero: soggetto al centro, margini di sicurezza.
- [ ] Gallery: ~1200×800, JPG/PNG, 300-700KB, sRGB.
- [ ] OG: 1200×630, **PNG**, ≤500KB, sRGB, logo visibile (40-60%).
- [ ] Tutti i file compressi con TinyPNG o ImageOptim.
- [ ] File copiati in `public/images/`.
- [ ] `config/apartment.php` aggiornato.
- [ ] `php artisan config:clear` eseguito.
- [ ] Refresh browser e test preview social.
