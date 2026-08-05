<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\BookingRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingRequestMail extends Mailable
{
    /**
     * @param array{
     *   checkin: string,
     *   checkout: string,
     *   adults: int,
     *   children: int|null,
     *   name: string,
     *   email: string,
     *   phone: string|null,
     *   message: string|null,
     *   newsletter: bool|null,
     * } $requestData
     */
    public function __construct(
        public readonly array $requestData,
        public readonly ?BookingRequest $bookingRequest = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuova richiesta disponibilità — La Caracola',
            replyTo: [
                new Address($this->requestData['email'], $this->requestData['name']),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-request',
            with: [
                'bookingRequest' => $this->bookingRequest,
            ],
        );
    }
}
