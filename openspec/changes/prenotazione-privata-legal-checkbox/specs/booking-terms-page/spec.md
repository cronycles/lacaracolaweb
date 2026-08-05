## ADDED Requirements

### Requirement: Public short-term tourist lease agreement page
The system SHALL expose a public page publishing the "Short-Term Tourist Lease Agreement" text, available at a locale-specific route (`condizioni-generali-prenotazione` for Italian, and equivalent translated slugs for English, French and German), following the same routing conventions as the existing House Rules page.

#### Scenario: Guest opens the terms page in their locale
- **WHEN** a visitor navigates to the terms page URL for their current locale
- **THEN** the page renders the full lease agreement text translated into that locale, with the site's standard layout/branding

#### Scenario: Checkbox link points to the correct localized terms page
- **WHEN** the availability request form is rendered in a given locale
- **THEN** the consent checkbox label's "Lease Agreement Terms" link points to the terms page route for that same locale (via `route_locale()`)

### Requirement: Lease agreement content matches the approved legal text
The terms page SHALL render the full legal text (object of contract, booking finalization, AlloggiatiWeb data obligation, deposit/house rules reference, cancellation policy, competent court/privacy) as approved, translated into Italian, English, French and German (no Spanish in this change).

#### Scenario: Cancellation policy visible
- **WHEN** a visitor reads the terms page
- **THEN** the free-cancellation window (14 days before check-in) and the no-show/late-cancellation policy (full amount retained) are clearly stated
