## ADDED Requirements

### Requirement: Daily command sends arrival-eve and departure-eve reminders
The system SHALL provide an Artisan command `telegram:send-reminders` that, when run, queries active (non-canceled, non-deleted) bookings and sends Telegram reminders to all users with a non-null `telegram_chat_id`.

#### Scenario: Arrival reminder sent N days before check-in
- **WHEN** the command runs and a booking has `checkin = today + N` (where N = `config('telegram.checkin_lead_days', 1)`)
- **THEN** the command SHALL send a message to each recipient with the arrival reminder text and the booking summary

#### Scenario: Departure reminder sent M days before check-out
- **WHEN** the command runs and a booking has `checkout = today + M` (where M = `config('telegram.checkout_lead_days', 1)`)
- **THEN** the command SHALL send a message to each recipient with the departure reminder text and the booking summary

#### Scenario: No matching bookings
- **WHEN** no bookings match the arrival or departure criteria
- **THEN** the command SHALL complete silently without sending any messages

#### Scenario: Booking is canceled
- **WHEN** a booking with a matching date has `canceled_at != null`
- **THEN** it SHALL be excluded from reminders

### Requirement: Reminder message content
Arrival and departure reminders SHALL include guest name, phone, check-in or check-out date, adults, children, and pets.

#### Scenario: Arrival reminder format
- **WHEN** an arrival reminder is sent
- **THEN** the message SHALL start with a line such as: `🔔 Arrival tomorrow – [First Last]` followed by booking summary fields

#### Scenario: Departure reminder format
- **WHEN** a departure reminder is sent
- **THEN** the message SHALL start with a line such as: `🔔 Departure tomorrow – [First Last]` followed by booking summary fields

### Requirement: Command scheduled to run daily
The `telegram:send-reminders` command SHALL be registered in the Laravel scheduler to run once daily.

#### Scenario: Scheduler registration
- **WHEN** `php artisan schedule:run` is executed
- **THEN** `telegram:send-reminders` SHALL appear in the scheduled command list and execute once per day

### Requirement: Lead days configurable for both check-in and check-out
The number of days before check-in at which the arrival reminder is sent SHALL be configurable via `TELEGRAM_CHECKIN_LEAD_DAYS` (default: 1). The number of days before check-out at which the departure reminder is sent SHALL be configurable via `TELEGRAM_CHECKOUT_LEAD_DAYS` (default: 1).

#### Scenario: Custom checkin lead days
- **WHEN** `TELEGRAM_CHECKIN_LEAD_DAYS=2` is set in `.env`
- **THEN** the command SHALL match bookings with `checkin = today + 2` for arrival reminders

#### Scenario: Custom checkout lead days
- **WHEN** `TELEGRAM_CHECKOUT_LEAD_DAYS=2` is set in `.env`
- **THEN** the command SHALL match bookings with `checkout = today + 2` for departure reminders
