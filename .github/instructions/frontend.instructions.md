---
description: "Use when writing or modifying TypeScript, JavaScript, PostCSS, CSS or any file inside resources/ directory. Covers frontend toolchain, styling conventions, mobile-first design, UX and SEO asset requirements."
applyTo: "resources/**"
---

# Frontend Conventions — La Caracola Web

## Toolchain
- **Vite** for bundling, transpilation and minification.
- **TypeScript** for all JS logic — no plain `.js` files in `resources/ts/`.
- **PostCSS** for all styles — no plain CSS without PostCSS processing.
- **ESLint** must pass before any commit.

## Design System
- Brand palette:
  - Primary: `#30596C` (blue)
  - Accent: `#c7b772` (gold)
  - Base: white / light backgrounds
- Typography: clean, readable, optimised for mobile.
- Component styles live in `resources/css/components/` — one file per component.

## Mobile-First
- All layouts start from mobile breakpoint and scale up.
- Tap targets minimum 44×44px.
- Hero images must load a smaller version on mobile (responsive `srcset`).

## UX Principles
- User-visible text: short and clear — no walls of text.
- SEO content: placed in dedicated sections (e.g. collapsed, below the fold), not in hero.
- Smooth scroll, lazy-load images below the fold.
- Avoid layout shifts (CLS must be low): reserve space for images.

## Performance
- Lazy-load all images below the fold (`loading="lazy"`).
- Prefer CSS animations over JS where possible.
- Keep JS bundle lean — split code per route if needed.

## Hero Component
- Home hero: rotating/fade image carousel (inspired by Luviana theme).
- Each slide has a short headline and a single CTA button.
- Autoplay with pause on user interaction; respect `prefers-reduced-motion`.

## Accessibility
- All images have meaningful `alt` attributes.
- Focusable elements reachable by keyboard.
- Colour contrast ratio ≥ 4.5:1 for body text.

## Comments
- Comments in English only.
