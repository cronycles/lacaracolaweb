## ADDED Requirements

### Requirement: Webhook endpoint accepts Telegram updates
The system SHALL expose `POST /api/telegram/webhook/{secret}` where `{secret}` is matched against `config('telegram.webhook_secret')`. Requests with a mismatched secret SHALL return HTTP 403.

#### Scenario: Valid webhook update received
- **WHEN** Telegram sends a POST to the webhook URL with the correct secret and a JSON body containing a `message`
- **THEN** the controller SHALL log the full update (level `info`, channel `telegram`) and return HTTP 200 with an empty body

#### Scenario: Update contains a message
- **WHEN** the update body contains `message.chat.id` and optionally `message.text`
- **THEN** the controller SHALL log `chat_id` and `text` explicitly (level `info`, channel `telegram`, key `telegram_message`) so the admin can copy the `chat_id` from the log

#### Scenario: Secret mismatch
- **WHEN** the path segment `{secret}` does not match `config('telegram.webhook_secret')`
- **THEN** the controller SHALL return HTTP 403 and not process the update

#### Scenario: Update without message field
- **WHEN** the update body does not contain a `message` key (e.g., edited_message, callback_query)
- **THEN** the controller SHALL log the raw update and return HTTP 200 without error

### Requirement: Webhook route registered outside CSRF middleware
The webhook route SHALL be registered in `routes/api.php` under the `api` middleware group (stateless). It SHALL NOT be wrapped in the `web` middleware group.

#### Scenario: Webhook receives unauthenticated POST
- **WHEN** Telegram sends a POST without a session or CSRF token
- **THEN** the request SHALL be accepted (HTTP 200) if the secret is correct, not rejected with HTTP 419
