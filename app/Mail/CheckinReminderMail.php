<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CheckinReminderMail extends Mailable
{
    use SerializesModels;

    public readonly string $checkinUrl;

    public function __construct(public readonly Booking $booking)
    {
        $token = $booking->checkin_token ?: $booking->generateCheckinToken();
        $this->checkinUrl = route('checkin.show', $token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('app.checkin_reminder_mail_subject'),
            bcc: [config('apartment.email')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.checkin-reminder',
        );
    }
}
