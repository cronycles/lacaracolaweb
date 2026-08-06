## ADDED Requirements

### Requirement: Token-based public access to a booking's check-in page
The system SHALL generate a high-entropy, unguessable token per `Booking` (`checkin_token`) with an expiry (`checkin_token_expires_at`, set to the booking's checkout date), used to build a public check-in URL requiring no guest authentication.

#### Scenario: Token generated on first need
- **WHEN** the owner sends the "booking confirmed" email for a `Booking` without an existing `checkin_token`
- **THEN** the system generates a new random token and expiry before including the check-in link in the email

#### Scenario: Valid token grants access
- **WHEN** a visitor opens the check-in URL with a valid, non-expired token
- **THEN** the system shows the check-in form for that booking

#### Scenario: Expired or invalid token is rejected
- **WHEN** a visitor opens the check-in URL with an expired token, or a token that doesn't match any booking
- **THEN** the system shows an error/expired page and does not reveal any booking or guest data

### Requirement: Check-in page locale follows the booking, with a manual switcher
The check-in page SHALL default to the locale saved on the `Booking` (falling back to the application's default locale), while allowing the visitor to switch language on the page itself without changing the stored value.

#### Scenario: Page opens in the booking's saved locale
- **WHEN** a visitor opens a valid check-in link for a booking with `locale = fr`
- **THEN** the check-in page renders in French

#### Scenario: Visitor switches language on the page
- **WHEN** a visitor selects a different language from the check-in page's language switcher
- **THEN** the page reloads in the selected language without altering the booking's stored locale
