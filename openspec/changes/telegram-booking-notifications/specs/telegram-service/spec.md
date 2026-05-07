## ADDED Requirements

### Requirement: Send message via Telegram Bot API
The service SHALL send a plain-text message to a given Telegram `chat_id` using the configured bot token.

#### Scenario: Successful message delivery
- **WHEN** `sendMessage($chatId, $text)` is called with a valid `chat_id` and non-empty text
- **THEN** the service SHALL POST to `https://api.telegram.org/bot{token}/sendMessage` with `chat_id` and `text` in the payload and return `true`

#### Scenario: API returns non-2xx response
- **WHEN** the Telegram API returns a non-2xx HTTP status
- **THEN** the service SHALL log an error entry (channel `telegram`) and return `false`

#### Scenario: Network or timeout exception
- **WHEN** the HTTP request throws a `ConnectionException` or similar
- **THEN** the service SHALL catch the exception, log an error, and return `false` without propagating the exception

### Requirement: Configuration via `config/telegram.php`
The service SHALL read `bot_token` and `api_url` from `config('services.telegram')`. Additional notification settings (recipients, lead days) SHALL be in `config/telegram.php`.

#### Scenario: Bot token read from config
- **WHEN** `TelegramService` is constructed
- **THEN** it SHALL resolve `config('services.telegram.token')` for the bot token and `config('services.telegram.api_url')` for the base URL (default: `https://api.telegram.org/bot`)
