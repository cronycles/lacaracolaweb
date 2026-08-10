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
use Illuminate\Support\Collection;

class CheckinCompletedMail extends Mailable
{
    use SerializesModels;

    public readonly string $address;

    public readonly array $mapLinks;

    public readonly Carbon $freeCancellationDate;

    /** @var Collection<int, User> */
    public readonly Collection $hostKeepers;

    public function __construct(public readonly Booking $booking)
    {
        $this->booking->loadMissing('person', 'bookingRequest');

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

        $this->freeCancellationDate = $booking->checkin->copy()->subDays((int) config('apartment.payment.free_cancellation_days', 14));

        $this->hostKeepers = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'host_keeper'))
            ->whereNotNull('email')
            ->orderBy('name')
            ->get();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Check-in online completato — La Caracola',
            bcc: [config('apartment.email')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.checkin-completed',
        );
    }
}
