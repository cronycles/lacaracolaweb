## Context

La Caracola is a Laravel monolith (Blade + no public API). The admin panel is used by the host owner (super_admin) and a host keeper (host_keeper role, view-only). Currently no push notifications exist: both users must open the admin panel to check new bookings or upcoming arrivals/departures.

The chosen notification channel is **Telegram** via a dedicated bot (`LaCaracolaAndoraBot`). Recipient `chat_id`s are discovered once per user: they send any message to the bot, the webhook logs it, and the admin copies the `chat_id` into config. After that, the system is fully automated.

**Constraints:**
- No new Composer packages (use Laravel `Http` client).
- Recipients stored as `telegram_chat_id` (nullable `VARCHAR`) on the existing `users` table — one migration, no new tables.
- The project has no `routes/api.php` yet (only `routes/web.php` and `routes/admin.php`); a new `api.php` file must be created and registered.
- Scheduler: Laravel's built-in scheduler via `php artisan schedule:run` (already in production deploy cron).
- Notification button lives permanently on the booking `show` page; it is not tied to a flash session.

## Goals / Non-Goals

**Goals:**
- `TelegramService`: thin wrapper around Telegram `sendMessage` API.
- `TelegramWebhookController`: log incoming updates and extract `chat_id` to ease initial setup.
- New booking notification: a "Send Telegram notification" button on the booking `show` page, guarded by `manage_bookings` permission; confirmed with a JS dialog before sending.
- Scheduled reminders: one daily command sends arrival-eve and departure-eve messages to all recipients with a configured `telegram_chat_id`.
- Recipients stored as `telegram_chat_id` on `users` table; editable by super_admin in user management.
- Configurable lead days before check-in and check-out (defaults: 1 day each).

**Non-Goals:**
- Cancellation or modification notifications (out of scope for this change).
- Telegram inline buttons or rich message formatting beyond plain text.
- Notification history or delivery receipts stored in DB.
- PDF import auto-notification (the PDF import flow uses `BookingCreationService`, which is a batch-import path — separate from the interactive admin form; notifications should not fire automatically there).

## Decisions

### D1 — Recipient storage: `users.telegram_chat_id` nullable column

**Decision:** Add a nullable `telegram_chat_id` column (VARCHAR 64) to the `users` table via migration. `TelegramService` resolves recipients at runtime with `User::whereNotNull('telegram_chat_id')->pluck('telegram_chat_id')`. The field is exposed in the user create/edit form, visible only to users with `manage_users` (i.e., super_admin).

**Rationale:** Recipients are admin users already in the system. Tying the `chat_id` to the `User` record is semantically correct, avoids a separate config key per user, and allows the super_admin to manage it through the existing user management UI without touching `.env` or re-deploying.

**Alternative considered (original):** Comma-separated `TELEGRAM_RECIPIENT_CHAT_IDS` env var. Rejected because it requires SSH/deploy access to change and is disconnected from the user model.

**Alternative considered:** `settings` table. Rejected — the project doc limits `settings` to `booking_mode` and `booking_external_url`.

---

### D2 — Notification trigger: permanent "Send Telegram" button on booking `show` page

**Decision:** A "Send Telegram notification" button is rendered permanently on the booking `show` page, visible to any user with the `manage_bookings` permission. Clicking it shows a `window.confirm()` dialog (or an Alpine.js inline confirm prompt). On confirmation, it fires `POST /admin/prenotazioni/{id}/notify-telegram` via `fetch`; a toast shows success or failure. The button is stateless — it can be clicked at any time, not only immediately after creation.

**Rationale:** A button always available on the show page is simpler to discover and use than a flash-based modal that appears only once after store. It also covers cases where the admin wants to re-send the notification (e.g., after a date change) or missed the initial popup.

**Store redirect (independent UX change):** `BookingController@store` is changed to redirect to `admin.bookings.show` instead of the index. This is a standalone UX improvement confirmed by the owner and is not coupled to Telegram logic.

**Alternative considered (original):** Flash `ask_telegram_notify=1` after store, show modal once. Rejected — single-use modal is less discoverable and cannot be re-triggered without recreating the booking.

---

### D3 — Scheduled reminders: single Artisan command, daily

**Decision:** One command `SendTelegramBookingReminders` runs daily. It queries bookings with `checkin = today + N` days and `checkout = today + M` days. Both N and M are configurable: `TELEGRAM_CHECKIN_LEAD_DAYS` (default 1) and `TELEGRAM_CHECKOUT_LEAD_DAYS` (default 1). Recipients are resolved from `users.telegram_chat_id`.

**Rationale:** A single command is simpler to schedule and maintain than two separate commands. Making both lead times configurable is symmetric and consistent.

**Alternative considered:** Laravel notifications with a `notifiable` model. Rejected because recipients are not `User` models attached to bookings (they are fixed admin contacts), and the overhead of a Notification class adds complexity without benefit here.

---

### D4 — Webhook route: `routes/api.php` (new file, no CSRF)

**Decision:** Create `routes/api.php` registered in `bootstrap/app.php` under the `api` middleware group (stateless, no CSRF). The webhook route is `POST /api/telegram/webhook`.

**Rationale:** Telegram's webhook POST cannot carry a CSRF token. The `web` middleware group enforces CSRF, so the webhook must live outside it. Laravel's `api` group is the correct placement.

**Security note:** Telegram does not sign webhook payloads with a shared secret by default. To prevent unauthorized calls, the webhook URL should include a secret path component (e.g., `/api/telegram/webhook/{secret}`), where `{secret}` is stored in `.env` as `TELEGRAM_WEBHOOK_SECRET`. The controller validates the path param against config before processing.

---

### D5 — No Composer packages

**Decision:** Use `Illuminate\Support\Facades\Http` (already in Laravel 11) for all Telegram API calls. No `telegram-bot-sdk` or similar.

**Rationale:** The only operation needed is `sendMessage`. The full Bot SDK is overkill and adds a maintenance dependency.

## Risks / Trade-offs

- **Telegram API downtime** → `TelegramService::sendMessage` catches failed HTTP responses, logs the error, and returns `false`. The booking is never blocked by notification failure.
- **`telegram_chat_id` not set** → If a user has no `chat_id` configured, they simply receive no messages. No error is raised. The webhook log makes re-discovery easy at any time.
- **`store` redirect change** → Changing the redirect target from index to show is a visible UX change; considered an improvement and confirmed by the owner.
- **Webhook secret exposure** → The secret is in `.env` only; never committed. The webhook URL itself is not linked anywhere in the UI.
- **PDF import path** → `BookingCreationService` is used for batch import. Notification is intentionally NOT wired there to avoid flooding recipients during multi-booking imports.
