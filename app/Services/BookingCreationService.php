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
        $person = $this->findOrCreatePerson([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        $booking = Booking::create([
            'person_id' => $person->id,
            'checkin' => $data['checkin'],
            'checkout' => $data['checkout'],
            'adults' => $data['adults'],
            'children' => $data['children'] ?? 0,
            'babies' => $data['babies'] ?? 0,
            'pets' => $data['pets'] ?? 0,
            'source' => $data['source'],
            'external_ref' => $data['external_ref'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        AvailabilityBlock::create([
            'start_date' => $data['checkin'],
            'end_date' => $data['checkout'],
            'reason' => 'booked',
            'booking_id' => $booking->id,
        ]);

        return $booking;
    }

    /**
     * Find an existing guest by email, then phone, then exact first+last
     * name; create a new `Person` if none match. Enriches a matched
     * person's missing email/phone from the given data, but never
     * overwrites existing values.
     *
     * Shared by the Interhome PDF import flow (`createFromParsed`) and the
     * admin booking-request confirmation flow, so both stay in sync.
     *
     * @param  array{first_name: string, last_name: string, email: string|null, phone: string|null}  $data
     */
    public function findOrCreatePerson(array $data): Person
    {
        $person = $this->matchExistingPerson($data);

        if (! $person) {
            $person = Person::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);
        } else {
            // Matching includes soft-deleted people (the email/phone unique
            // constraints aren't scoped to non-deleted rows), so restore it
            // rather than leaving it trashed while we reuse it.
            if ($person->trashed()) {
                $person->restore();
            }

            // Enrich existing guest when import brings missing contacts.
            if (empty($person->email) && ! empty($data['email'])) {
                $person->email = $data['email'];
            }
            if (empty($person->phone) && ! empty($data['phone'])) {
                $person->phone = $data['phone'];
            }
            $person->save();
        }

        $person->autoSubscribeToNewsletter();

        return $person;
    }

    /**
     * Read-only preview of what `findOrCreatePerson()` would resolve to,
     * without creating, saving, or enriching anything. Returns `null` when
     * no existing `Person` matches (meaning a new one would be created).
     *
     * @param  array{first_name: string, last_name: string, email: string|null, phone: string|null}  $data
     */
    public function previewMatch(array $data): ?Person
    {
        return $this->matchExistingPerson($data);
    }

    /**
     * Look up an existing guest by email, then phone, then exact first+last
     * name. Returns `null` if none match.
     *
     * @param  array{first_name: string, last_name: string, email: string|null, phone: string|null}  $data
     */
    private function matchExistingPerson(array $data): ?Person
    {
        // Include soft-deleted rows: `email` and `phone` have plain unique
        // DB constraints that aren't relaxed by soft-deletes, so a trashed
        // Person still blocks (and should be reused/restored for) the same
        // email/phone rather than causing a duplicate-entry SQL error.
        if (! empty($data['email'])) {
            $person = Person::withTrashed()->where('email', $data['email'])->first();
            if ($person) {
                return $person;
            }
        }

        if (! empty($data['phone'])) {
            $person = Person::withTrashed()->where('phone', $data['phone'])->first();
            if ($person) {
                return $person;
            }
        }

        return Person::withTrashed()
            ->where('first_name', $data['first_name'])
            ->where('last_name', $data['last_name'])
            ->first();
    }
}
