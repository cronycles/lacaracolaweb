<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Person;

/**
 * Creates a booking (+ person + availability block) from normalized imported data.
 */
class BookingCreationService
{
    /**
     * Find or create the guest and persist a booking + availability block.
     *
     * @param  array{
     *     first_name: string,
     *     last_name: string,
     *     email: string|null,
     *     phone: string|null,
     *     checkin: string,
     *     checkout: string,
     *     adults: int,
     *     children: int,
     *     babies: int,
     *     pets: int,
     *     source: string,
     *     external_ref: string|null,
     *     notes: string|null,
     * } $data
     */
    public function createFromParsed(array $data): Booking
    {
        $person = null;

        if (!empty($data['email'])) {
            $person = Person::where('email', $data['email'])->first();
        }

        if (!$person && !empty($data['phone'])) {
            $person = Person::where('phone', $data['phone'])->first();
        }

        if (!$person) {
            $person = Person::where('first_name', $data['first_name'])
                ->where('last_name', $data['last_name'])
                ->first();
        }

        if (!$person) {
            $person = Person::create([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'] ?? null,
                'phone'      => $data['phone'] ?? null,
            ]);
        } else {
            // Enrich existing guest when import brings missing contacts.
            if (empty($person->email) && !empty($data['email'])) {
                $person->email = $data['email'];
            }
            if (empty($person->phone) && !empty($data['phone'])) {
                $person->phone = $data['phone'];
            }
            $person->save();
        }

        $booking = Booking::create([
            'person_id'    => $person->id,
            'checkin'      => $data['checkin'],
            'checkout'     => $data['checkout'],
            'adults'       => $data['adults'],
            'children'     => $data['children'] ?? 0,
            'babies'       => $data['babies'] ?? 0,
            'pets'         => $data['pets'] ?? 0,
            'source'       => $data['source'],
            'external_ref' => $data['external_ref'] ?? null,
            'notes'        => $data['notes'] ?? null,
        ]);

        AvailabilityBlock::create([
            'start_date' => $data['checkin'],
            'end_date'   => $data['checkout'],
            'reason'     => 'booked',
            'booking_id' => $booking->id,
        ]);

        return $booking;
    }
}
