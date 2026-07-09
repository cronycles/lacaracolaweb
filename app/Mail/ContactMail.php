<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactMail extends Mailable
{
    /**
     * @param array{
     *   name: string,
     *   email: string,
     *   subject: string|null,
     *   message: string,
     * } $data
     */
    public function __construct(public readonly array $data) {}

    public function envelope(): Envelope
    {
        $subject = ! empty($this->data['subject'])
            ? 'Contatto: ' . $this->data['subject'] . ' — La Caracola'
            : 'Nuovo messaggio di contatto — La Caracola';

        return new Envelope(
            subject: $subject,
            replyTo: [
                new Address($this->data['email'], $this->data['name']),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
        );
    }
}
