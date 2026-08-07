<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Person;
use App\Services\BookingCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for `BookingCreationService` after extracting the guest
 * matching logic into `findOrCreatePerson()`/`matchExistingPerson()` (see
 * openspec change `booking-request-confirmation`). Behavior must stay
 * identical to before the extraction for the Interhome PDF import flow.
 */
class BookingCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_from_parsed_matches_existing_person_by_email(): void
    {
        $existing = Person::create([
            'first_name' => 'Anna',
            'last_name'  => 'Verdi',
            'email'      => 'anna@example.com',
        ]);

        $booking = (new BookingCreationService())->createFromParsed([
            'first_name'   => 'Anna',
            'last_name'    => 'Verdi',
            'email'        => 'anna@example.com',
            'phone'        => null,
            'checkin'      => now()->addDays(5)->format('Y-m-d'),
            'checkout'     => now()->addDays(10)->format('Y-m-d'),
            'adults'       => 2,
            'children'     => 0,
            'babies'       => 0,
            'pets'         => 0,
            'source'       => 'interhome',
            'external_ref' => 'REF123',
            'notes'        => null,
        ]);

        $this->assertSame($existing->id, $booking->person_id);
        $this->assertSame(1, Person::count());
        $this->assertNotNull($booking->availabilityBlock);
    }

    public function test_create_from_parsed_creates_new_person_when_no_match(): void
    {
        $booking = (new BookingCreationService())->createFromParsed([
            'first_name'   => 'Luca',
            'last_name'    => 'Bianchi',
            'email'        => null,
            'phone'        => null,
            'checkin'      => now()->addDays(5)->format('Y-m-d'),
            'checkout'     => now()->addDays(10)->format('Y-m-d'),
            'adults'       => 1,
            'children'     => 0,
            'babies'       => 0,
            'pets'         => 0,
            'source'       => 'interhome',
            'external_ref' => null,
            'notes'        => null,
        ]);

        $this->assertSame('Luca', $booking->person->first_name);
        $this->assertSame('Bianchi', $booking->person->last_name);
    }

    public function test_preview_match_does_not_create_or_persist_anything(): void
    {
        $service = new BookingCreationService();

        $match = $service->previewMatch([
            'first_name' => 'Nobody',
            'last_name'  => 'Here',
            'email'      => null,
            'phone'      => null,
        ]);

        $this->assertNull($match);
        $this->assertSame(0, Person::count());
    }

    /**
     * Regression test: `email`/`phone` have plain unique DB constraints that
     * aren't scoped to non-deleted rows, so a soft-deleted `Person` still
     * occupies the email and previously caused a duplicate-entry SQL error
     * when `matchExistingPerson()` (which excluded trashed rows) failed to
     * find it and tried to create a new one instead.
     */
    public function test_find_or_create_person_restores_a_soft_deleted_match_instead_of_duplicating(): void
    {
        $existing = Person::create([
            'first_name' => 'Daniele',
            'last_name'  => 'Crosetti',
            'email'      => 'cronycles@gmail.com',
        ]);
        $existing->delete();

        $this->assertTrue($existing->fresh()->trashed());

        $person = (new BookingCreationService())->findOrCreatePerson([
            'first_name' => 'Daniele',
            'last_name'  => 'Crosetti',
            'email'      => 'cronycles@gmail.com',
            'phone'      => '+39 629919011',
        ]);

        $this->assertSame($existing->id, $person->id);
        $this->assertFalse($person->fresh()->trashed());
        $this->assertSame(1, Person::count());
    }
}
