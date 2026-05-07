## Why

The host owner and host keeper currently have no automated way to be notified when a new booking is created, nor when a guest is about to arrive or depart. Notifications must be checked manually in the admin panel. Adding Telegram notifications removes this friction and keeps both stakeholders informed in real time and ahead of key dates.

## What Changes

- Add `App\Services\TelegramService` for sending messages via Telegram Bot API.
- Add `TELEGRAM_BOT_TOKEN` and related env vars; extend `config/services.php` with a `telegram` block.
- Add `config/telegram.php` to hold lead-time days and webhook secret; recipients are stored as `telegram_chat_id` (nullable) on the existing `users` table, editable by super_admin in user management.
- Add a webhook route `POST /api/telegram/webhook` and `TelegramWebhookController` to receive updates and log `chat_id`s for initial configuration.
- Add a "Send Telegram notification" button on the booking `show` page (visible to `manage_bookings` users); triggers a JS confirmation dialog before sending.
- Change `BookingController@store` to redirect to the booking `show` page instead of the index (UX improvement independent of Telegram).
- Add a scheduled console command to send configurable-lead-day arrival and departure reminders daily.

## Capabilities

### New Capabilities

- `telegram-service`: Low-level Telegram Bot API wrapper (`sendMessage`, error logging).
- `telegram-webhook`: Webhook endpoint that logs incoming updates and extracts `chat_id` to ease initial recipient configuration.
- `telegram-booking-alert`: Notification sent when a new booking is saved (triggered by admin UI confirmation popup).
- `telegram-reminder-scheduler`: Scheduled daily job that sends arrival-eve and departure-eve reminders to configured recipients.

### Modified Capabilities

<!-- No existing spec-level behavior changes. -->

## Impact

- **New files**: `app/Services/TelegramService.php`, `app/Http/Controllers/TelegramWebhookController.php`, `app/Console/Commands/SendTelegramBookingReminders.php`, migration for `users.telegram_chat_id`.
- **Modified files**: `config/services.php` (add `telegram` block), `routes/admin.php` + `routes/api.php` (webhook + notify routes), `app/Http/Controllers/Admin/BookingController.php` (`store` redirect + new `notifyTelegram` action), `app/Http/Controllers/Admin/UserController.php` (expose `telegram_chat_id`), booking `show` Blade + user form Blade, `bootstrap/app.php` (scheduler), `.env.example`.
- **DB change**: one nullable column `telegram_chat_id` on `users` table (migration).
- **Dependencies**: No new Composer packages required (uses Laravel `Http` client).
- **No breaking changes**.
