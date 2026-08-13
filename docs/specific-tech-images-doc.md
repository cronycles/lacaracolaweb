# Image Guidelines

## Overview

All site images are configured in [config/apartment.php](../config/apartment.php) under the `images` key.
No Blade template change is required if paths are updated correctly.

## Image Categories

### 1. Hero — 1920 x 1080

Use:

- Home hero full-width images.

Format:

- JPG for photos, PNG for graphics.
- No SVG.

Technical targets:

- Color profile: sRGB
- Size: 1-2 MB max per file
- Compression: JPG quality 85-90, PNG optimized with TinyPNG/ImageOptim
- Aspect ratio: exactly 16:9 (1920 x 1080)

Composition:

- Keep key subject centered.
- Keep safe margins on borders because of cover cropping on smaller screens.
- Avoid critical text near edges.

Naming:

- hero-1.jpg

Config example:

```php
'hero'    => 'images/hero-1.jpg',
```

### 2. Gallery — about 1200 x 800

Use:

- Apartment gallery images.

Format:

- JPG preferred for photos, PNG for graphics/icons.
- No SVG.

Technical targets:

- Color profile: sRGB
- Typical size: 300-700 KB
- Compression: JPG quality 80-85, optimized PNG
- Aspect ratio: around 3:2 (1200 x 800 suggested)

Composition:

- Flexible framing, layout adapts automatically.
- Prefer well-lit images.

Naming:

- apartment-1.jpg ... apartment-6.jpg (or more/less as needed)

Config example:

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

### 3. Open Graph — 1200 x 630

Use:

- Social sharing preview image (WhatsApp/Facebook/Telegram/etc.).

Format:

- PNG required.
- No SVG.

Technical targets:

- Color profile: sRGB
- Size: 300-500 KB max
- Compression: TinyPNG or equivalent
- Aspect ratio: exactly 1200 x 630 (~1.91:1)

Composition:

- Blue wordmark plus thematic background.
- Logo should occupy roughly 40-60% of visual area.
- Keep at least 100px safe margins.
- Avoid tiny text details.

Naming:

- og-default.png

Config example:

```php
'og' => 'images/og-default.png',
```

## Workflow

1. Export

- Export with exact dimensions and sRGB profile.

2. Compress

- Optimize with TinyPNG or ImageOptim.

3. Upload

- Copy files to [public/images/](../public/images/).

4. Configure

- Update image paths in [config/apartment.php](../config/apartment.php).

5. Clear config cache

```bash
php artisan config:clear
```

6. Verify

- Reload browser and confirm image rendering.
- Test OG preview with social debugger tools.

## Gallery fallback

If a configured gallery file is missing from `public/images/`, a placeholder from placehold.co is shown.
This allows adding/removing gallery entries without runtime errors.

## Current config template

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

## Quick checklist

- [ ] Hero: 1920 x 1080, JPG/PNG, <=2MB, sRGB
- [ ] Hero composition centered with safe margins
- [ ] Gallery: about 1200 x 800, JPG/PNG, 300-700KB, sRGB
- [ ] OG: 1200 x 630, PNG, <=500KB, sRGB, visible logo
- [ ] Files compressed (TinyPNG/ImageOptim)
- [ ] Files uploaded to public/images
- [ ] config/apartment.php updated
- [ ] php artisan config:clear executed
- [ ] Browser and social preview checks completed
