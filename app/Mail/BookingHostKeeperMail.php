<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingHostKeeperMail extends Mailable
{
    use SerializesModels;

    public readonly ?User $paymentOwner;

    public function __construct(public readonly Booking $booking)
    {
        $this->paymentOwner = User::paymentOwner();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuova prenotazione confermata — La Caracola',
            bcc: [new Address(config('apartment.email'))],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-host-keeper',
        );
    }
}
