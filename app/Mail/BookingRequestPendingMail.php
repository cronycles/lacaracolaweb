<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\BookingRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRequestPendingMail extends Mailable
{
    use SerializesModels;

    public function __construct(public readonly BookingRequest $bookingRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('app.booking_pending_mail_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-request-pending',
            with: [
                'bookingRequest' => $this->bookingRequest,
            ],
        );
    }
}
