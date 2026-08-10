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
use Illuminate\Support\Collection;

class BookingPaymentReceivedMail extends Mailable
{
    use SerializesModels;

    public readonly string $checkinUrl;

    public readonly string $address;

    public readonly array $mapLinks;

    /** @var Collection<int, User> */
    public readonly Collection $hostKeepers;

    public function __construct(public readonly Booking $booking)
    {
        $this->booking->loadMissing('person');

        $token = $booking->checkin_token ?: $booking->generateCheckinToken();
        $this->checkinUrl = route('checkin.show', $token);

        $address = config('apartment.address', []);
        $this->address = implode(', ', array_filter([
            $address['street'] ?? null,
            trim(implode(' ', array_filter([$address['zip'] ?? null, $address['city'] ?? null]))),
            $address['province'] ?? null,
            $address['country'] ?? null,
        ]));

        $encodedAddress = rawurlencode($this->address);
        $this->mapLinks = [
            'google' => 'https://www.google.com/maps/search/?api=1&query='.$encodedAddress,
            'apple' => 'https://maps.apple.com/?address='.$encodedAddress,
            'openstreetmap' => 'https://www.openstreetmap.org/search?query='.$encodedAddress,
        ];

        $this->hostKeepers = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'host_keeper'))
            ->whereNotNull('email')
            ->orderBy('name')
            ->get();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pagamento ricevuto — La Caracola',
            bcc: [new Address(config('apartment.email'))],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-payment-received',
        );
    }
}
