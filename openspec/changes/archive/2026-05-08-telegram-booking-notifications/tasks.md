## 1. Configuration & Environment

- [x] 1.1 Add `telegram` block to `config/services.php`: `token` (from `TELEGRAM_BOT_TOKEN`), `api_url` (default `https://api.telegram.org/bot`)
- [x] 1.2 Create `config/telegram.php` with: `checkin_lead_days` (from `TELEGRAM_CHECKIN_LEAD_DAYS`, default 1), `checkout_lead_days` (from `TELEGRAM_CHECKOUT_LEAD_DAYS`, default 1), `webhook_secret` (from `TELEGRAM_WEBHOOK_SECRET`)
- [x] 1.3 Add `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHECKIN_LEAD_DAYS`, `TELEGRAM_CHECKOUT_LEAD_DAYS`, `TELEGRAM_WEBHOOK_SECRET` to `.env.example` with placeholder values

## 2. DB Migration — `users.telegram_chat_id`

- [x] 2.1 Create migration `add_telegram_chat_id_to_users_table`: add nullable `telegram_chat_id` string column to `users`
- [x] 2.2 Add `telegram_chat_id` to `$fillable` in the `User` model

## 3. User Management UI — `telegram_chat_id` field

- [x] 3.1 Add `telegram_chat_id` input field to the user create/edit Blade form (visible only if `auth()->user()->hasPermission('manage_users')` / super_admin)
- [x] 3.2 Add `telegram_chat_id` to the validated fields in `UserController@store` and `UserController@update`
- [x] 3.3 Display `telegram_chat_id` (read-only) on the user show/detail page for super_admin

## 4. TelegramService

- [x] 4.1 Create `app/Services/TelegramService.php` with `sendMessage(int|string $chatId, string $text): bool`
- [x] 4.2 Implement `sendMessage`: POST to `{api_url}{token}/sendMessage` with `chat_id` and `text` using `Http::post()`
- [x] 4.3 Add error handling: catch failed HTTP responses and `ConnectionException`, log errors to `telegram` channel, return `false`
- [x] 4.4 Add `sendToAllRecipients(string $text): void` helper that resolves `User::whereNotNull('telegram_chat_id')->pluck('telegram_chat_id')` and calls `sendMessage` for each

## 5. Telegram Log Channel

- [x] 5.1 Add a `telegram` log channel in `config/logging.php` (daily, `storage/logs/telegram.log`) so Telegram-related log entries are easily discoverable

## 6. Webhook

- [x] 6.1 Create `routes/api.php` with `Route::post('/telegram/webhook/{secret}', ...)` pointing to `TelegramWebhookController@handle`
- [x] 6.2 Register `routes/api.php` in `bootstrap/app.php` under the `api` middleware group
- [x] 6.3 Create `app/Http/Controllers/TelegramWebhookController.php` with `handle(Request $request, string $secret): Response`
- [x] 6.4 Implement secret validation: compare `$secret` to `config('telegram.webhook_secret')`; return HTTP 403 on mismatch
- [x] 6.5 Log the full update (`Log::channel('telegram')->info(...)`)
- [x] 6.6 Extract and log `chat_id` and `text` when the update contains a `message` key

## 7. New Booking Notification Flow

- [x] 7.1 Change `BookingController@store` redirect target from `admin.bookings.index` to `admin.bookings.show` (UX improvement, no flash needed)
- [x] 7.2 Add route `POST /admin/prenotazioni/{prenotazioni}/notify-telegram` in `routes/admin.php` under `permission:manage_bookings`, pointing to `BookingController@notifyTelegram`
- [x] 7.3 Implement `BookingController@notifyTelegram(Booking $prenotazioni): JsonResponse` — build message text, call `TelegramService::sendToAllRecipients()`, return JSON `{"sent": true/false}`
- [x] 7.4 Implement `buildBookingMessage(Booking $booking): string` (private method in `BookingController` or `TelegramService`) formatting: guest name, phone, check-in, check-out, adults, children, pets, notes (omit empty optional fields)
- [x] 7.5 In the booking `show` Blade view, render a "Send Telegram notification" button guarded by `@if(auth()->user()->hasPermission('manage_bookings'))`
- [x] 7.6 Wire the button to a `window.confirm()` prompt followed by `fetch('POST', .../notify-telegram)`; show a success/failure toast on response

## 8. Scheduled Reminders

- [x] 8.1 Create `app/Console/Commands/SendTelegramBookingReminders.php` with signature `telegram:send-reminders`
- [x] 8.2 Implement checkin query: active bookings with `checkin = Carbon::today()->addDays(config('telegram.checkin_lead_days', 1))`
- [x] 8.3 Implement checkout query: active bookings with `checkout = Carbon::today()->addDays(config('telegram.checkout_lead_days', 1))`
- [x] 8.4 Build arrival and departure message texts (guest name, phone, date, adults, children, pets); call `TelegramService::sendToAllRecipients()` for each match
- [x] 8.5 Register the command in the Laravel scheduler (in `bootstrap/app.php` or `routes/console.php`): `->daily()`

## 9. Documentation Updates

- [x] 9.1 Update `docs/specific-tech-backend-doc.mdc`: add `TelegramService` to the service map; document webhook route, notify endpoint, and `users.telegram_chat_id`
- [x] 9.2 Update `docs/specific-data-model.md`: add `telegram_chat_id` column to the `users` table definition
- [x] 9.3 Update `docs/DEPLOY.md`: add note about required env vars and `chat_id` discovery procedure (share bot link → user writes → copy `chat_id` from `storage/logs/telegram.log` → paste into user management UI)
