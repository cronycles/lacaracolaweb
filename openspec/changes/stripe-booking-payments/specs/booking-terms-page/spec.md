## MODIFIED Requirements

### Requirement: Lease agreement content matches the approved legal text
The terms page SHALL render the full approved legal text, including owner-approved and immediate-payment booking finalization, indicative payment periods and manual cancellation of approved requests, AlloggiatiWeb obligations, deposit and house rules, cancellation, competent court and privacy, in Italian, English, French and German. The current cancellation policy SHALL remain the default.

#### Scenario: Cancellation policy visible
- **WHEN** a visitor reads the terms page with the default policy
- **THEN** the free-cancellation window of 14 days before check-in and the no-show or late-cancellation policy of retaining the full amount are clearly stated

#### Scenario: Both booking paths are described
- **WHEN** a visitor reads the localized terms
- **THEN** the terms distinguish owner approval followed by payment from immediate payment and do not claim approved requests are automatically cancelled after the indicative period

#### Scenario: Agreed policy remains traceable
- **WHEN** the administrator changes a configurable payment or cancellation policy after guest consent
- **THEN** the accepted terms and applicable communicated period for the existing booking remain traceable and unchanged
