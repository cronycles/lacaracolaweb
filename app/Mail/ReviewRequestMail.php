<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewRequestMail extends Mailable
{
    use SerializesModels;

    public readonly string $reviewUrl;

    public function __construct(public readonly Booking $booking)
    {
        $this->locale($booking->locale ?: config('routes.fallback', 'it'));
        $token = $booking->review_token ?: $booking->generateReviewToken();
        $this->reviewUrl = route('review.show', $token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('app.review_request_mail_subject'),
            bcc: [config('apartment.email')],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.review-request');
    }
}
