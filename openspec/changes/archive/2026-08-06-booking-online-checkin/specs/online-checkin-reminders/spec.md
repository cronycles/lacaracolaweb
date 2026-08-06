## ADDED Requirements

### Requirement: Check-in link included in the booking-confirmed email
The "booking confirmed" email (`BookingConfirmedMail`) SHALL include the check-in link for the booking, generating the token if it doesn't exist yet.

#### Scenario: Confirmation email contains the check-in link
- **WHEN** the owner sends the booking-confirmed email
- **THEN** the email body includes a working check-in URL for that booking

### Requirement: Scheduled reminder if check-in is not completed
The system SHALL send a reminder email to the guest a configurable number of days before check-in (default 7) if the check-in has not been completed for that booking.

#### Scenario: Reminder sent when incomplete
- **WHEN** the scheduled reminder job runs and finds a non-canceled booking whose check-in date is exactly the configured lead time away, with `checkin_completed_at` still null
- **THEN** the system sends that guest a reminder email with the check-in link

#### Scenario: No reminder when already completed
- **WHEN** the scheduled reminder job runs and finds a booking matching the lead-time date but `checkin_completed_at` is already set
- **THEN** no reminder email is sent for that booking

#### Scenario: No reminder for canceled bookings
- **WHEN** the scheduled reminder job runs and finds a booking matching the lead-time date that has been canceled
- **THEN** no reminder email is sent for that booking
