<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use SerializesModels;

    public readonly Carbon $paymentDeadline;

    public readonly Carbon $freeCancellationDate;

    public readonly bool $cancellationStillFree;

    public readonly string $checkinUrl;

    public readonly ?User $paymentOwner;

    public function __construct(public readonly Booking $booking)
    {
        $this->paymentDeadline = now()->addHours((int) config('apartment.payment.deadline_hours', 48));
        $this->freeCancellationDate = $booking->checkin->copy()->subDays((int) config('apartment.payment.free_cancellation_days', 14));
        $this->cancellationStillFree = $this->freeCancellationDate->isFuture();

        $token = $booking->checkin_token ?: $booking->generateCheckinToken();
        $this->checkinUrl = route('checkin.show', $token);
        $this->paymentOwner = User::paymentOwner();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('app.booking_confirmed_mail_subject'),
            bcc: [config('apartment.email')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmed',
        );
    }
}
