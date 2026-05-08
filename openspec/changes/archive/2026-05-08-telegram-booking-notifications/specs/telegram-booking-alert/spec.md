## ADDED Requirements

### Requirement: "Send Telegram notification" button on booking show page
The booking `show` page SHALL display a "Send Telegram notification" button, visible to users with the `manage_bookings` permission. The button SHALL be available at any time (not only after creation), allowing re-sends.

#### Scenario: Button visible to manage_bookings user
- **WHEN** a user with `manage_bookings` permission visits the booking show page
- **THEN** the "Send Telegram notification" button SHALL be rendered on the page

#### Scenario: Button hidden from view-only users
- **WHEN** a user without `manage_bookings` (e.g., host_keeper) visits the booking show page
- **THEN** the button SHALL NOT be rendered

#### Scenario: User confirms the send dialog
- **WHEN** admin clicks the button and confirms the JS confirmation dialog
- **THEN** the system SHALL call `POST /admin/prenotazioni/{id}/notify-telegram` via `fetch` and send a message to all users with a non-null `telegram_chat_id`

#### Scenario: User dismisses the confirmation dialog
- **WHEN** admin clicks the button but cancels the JS confirmation dialog
- **THEN** no request SHALL be sent and no notification SHALL be delivered

#### Scenario: No recipients have a chat_id configured
- **WHEN** no `User` record has a non-null `telegram_chat_id`
- **THEN** the endpoint SHALL return HTTP 200 with `{"sent": false, "reason": "no_recipients"}` and log a warning; no Telegram API call SHALL be made

### Requirement: Notification message content
The notification message SHALL include: guest full name, phone number, check-in date, check-out date, number of adults, children, pets, and internal notes (if present).

#### Scenario: All fields populated
- **WHEN** a booking has all fields filled
- **THEN** the message SHALL follow the format:
  ```
  🏠 New booking – [First Last]
  📞 [phone]
  📅 Check-in: [DD/MM/YYYY]  Check-out: [DD/MM/YYYY]
  👥 Adults: X  Children: X  Pets: X
  📝 Notes: [notes]
  ```

#### Scenario: Optional fields missing
- **WHEN** phone, pets, or notes are null/empty
- **THEN** those lines SHALL be omitted from the message

### Requirement: Booking store redirects to show page
After saving a booking, `BookingController@store` SHALL redirect to `admin.bookings.show` (the new booking's detail page) instead of the index. This is an independent UX improvement.

#### Scenario: Store redirects to show
- **WHEN** a booking is successfully created via `POST /admin/prenotazioni`
- **THEN** the response SHALL be a redirect to `GET /admin/prenotazioni/{id}` with a flash success message
