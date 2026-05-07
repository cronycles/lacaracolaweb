## 1. Configuration & Environment

- [ ] 1.1 Add `telegram` block to `config/services.php`: `token` (from `TELEGRAM_BOT_TOKEN`), `api_url` (default `https://api.telegram.org/bot`)
- [ ] 1.2 Create `config/telegram.php` with: `checkin_lead_days` (from `TELEGRAM_CHECKIN_LEAD_DAYS`, default 1), `checkout_lead_days` (from `TELEGRAM_CHECKOUT_LEAD_DAYS`, default 1), `webhook_secret` (from `TELEGRAM_WEBHOOK_SECRET`)
- [ ] 1.3 Add `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHECKIN_LEAD_DAYS`, `TELEGRAM_CHECKOUT_LEAD_DAYS`, `TELEGRAM_WEBHOOK_SECRET` to `.env.example` with placeholder values

## 2. DB Migration — `users.telegram_chat_id`

- [ ] 2.1 Create migration `add_telegram_chat_id_to_users_table`: add nullable `telegram_chat_id` string column to `users`
- [ ] 2.2 Add `telegram_chat_id` to `$fillable` in the `User` model

## 3. User Management UI — `telegram_chat_id` field

- [ ] 3.1 Add `telegram_chat_id` input field to the user create/edit Blade form (visible only if `auth()->user()->hasPermission('manage_users')` / super_admin)
- [ ] 3.2 Add `telegram_chat_id` to the validated fields in `UserController@store` and `UserController@update`
- [ ] 3.3 Display `telegram_chat_id` (read-only) on the user show/detail page for super_admin

## 4. TelegramService

- [ ] 4.1 Create `app/Services/TelegramService.php` with `sendMessage(int|string $chatId, string $text): bool`
- [ ] 4.2 Implement `sendMessage`: POST to `{api_url}{token}/sendMessage` with `chat_id` and `text` using `Http::post()`
- [ ] 4.3 Add error handling: catch failed HTTP responses and `ConnectionException`, log errors to `telegram` channel, return `false`
- [ ] 4.4 Add `sendToAllRecipients(string $text): void` helper that resolves `User::whereNotNull('telegram_chat_id')->pluck('telegram_chat_id')` and calls `sendMessage` for each

## 5. Telegram Log Channel

- [ ] 5.1 Add a `telegram` log channel in `config/logging.php` (daily, `storage/logs/telegram.log`) so Telegram-related log entries are easily discoverable

## 6. Webhook

- [ ] 6.1 Create `routes/api.php` with `Route::post('/telegram/webhook/{secret}', ...)` pointing to `TelegramWebhookController@handle`
- [ ] 6.2 Register `routes/api.php` in `bootstrap/app.php` under the `api` middleware group
- [ ] 6.3 Create `app/Http/Controllers/TelegramWebhookController.php` with `handle(Request $request, string $secret): Response`
- [ ] 6.4 Implement secret validation: compare `$secret` to `config('telegram.webhook_secret')`; return HTTP 403 on mismatch
- [ ] 6.5 Log the full update (`Log::channel('telegram')->info(...)`)
- [ ] 6.6 Extract and log `chat_id` and `text` when the update contains a `message` key

## 7. New Booking Notification Flow

- [ ] 7.1 Change `BookingController@store` redirect target from `admin.bookings.index` to `admin.bookings.show` (UX improvement, no flash needed)
- [ ] 7.2 Add route `POST /admin/prenotazioni/{prenotazioni}/notify-telegram` in `routes/admin.php` under `permission:manage_bookings`, pointing to `BookingController@notifyTelegram`
- [ ] 7.3 Implement `BookingController@notifyTelegram(Booking $prenotazioni): JsonResponse` — build message text, call `TelegramService::sendToAllRecipients()`, return JSON `{"sent": true/false}`
- [ ] 7.4 Implement `buildBookingMessage(Booking $booking): string` (private method in `BookingController` or `TelegramService`) formatting: guest name, phone, check-in, check-out, adults, children, pets, notes (omit empty optional fields)
- [ ] 7.5 In the booking `show` Blade view, render a "Send Telegram notification" button guarded by `@if(auth()->user()->hasPermission('manage_bookings'))`
- [ ] 7.6 Wire the button to a `window.confirm()` prompt followed by `fetch('POST', .../notify-telegram)`; show a success/failure toast on response

## 8. Scheduled Reminders

- [ ] 8.1 Create `app/Console/Commands/SendTelegramBookingReminders.php` with signature `telegram:send-reminders`
- [ ] 8.2 Implement checkin query: active bookings with `checkin = Carbon::today()->addDays(config('telegram.checkin_lead_days', 1))`
- [ ] 8.3 Implement checkout query: active bookings with `checkout = Carbon::today()->addDays(config('telegram.checkout_lead_days', 1))`
- [ ] 8.4 Build arrival and departure message texts (guest name, phone, date, adults, children, pets); call `TelegramService::sendToAllRecipients()` for each match
- [ ] 8.5 Register the command in the Laravel scheduler (in `bootstrap/app.php` or `routes/console.php`): `->daily()`

## 9. Documentation Updates

- [ ] 9.1 Update `docs/specific-tech-backend-doc.mdc`: add `TelegramService` to the service map; document webhook route, notify endpoint, and `users.telegram_chat_id`
- [ ] 9.2 Update `docs/specific-data-model.md`: add `telegram_chat_id` column to the `users` table definition
- [ ] 9.3 Update `docs/DEPLOY.md`: add note about required env vars and `chat_id` discovery procedure (share bot link → user writes → copy `chat_id` from `storage/logs/telegram.log` → paste into user management UI)
