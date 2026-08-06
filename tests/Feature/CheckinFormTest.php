<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Country;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckinFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Country::firstOrCreate(['iso2' => 'FR'], ['name_it' => 'Francia']);
        Country::firstOrCreate(['iso2' => 'IT'], ['name_it' => 'Italia']);
    }

    private function createBooking(int $adults, array $overrides = []): Booking
    {
        $person = Person::create([
            'first_name' => 'Anna',
            'last_name'  => 'Verdi',
            'email'      => 'anna.verdi@example.com',
        ]);

        $booking = Booking::create(array_merge([
            'person_id' => $person->id,
            'checkin'   => now()->addDays(10)->format('Y-m-d'),
            'checkout'  => now()->addDays(15)->format('Y-m-d'),
            'adults'    => $adults,
        ], $overrides));

        $booking->generateCheckinToken();

        return $booking;
    }

    private function guestPayload(int $personId, bool $withDocument): array
    {
        return [
            'person_id'                   => $personId,
            'gender'                      => 'M',
            'birth_date'                  => '1990-01-01',
            'nationality_code'            => 'FR',
            'birth_country_code'          => 'FR',
            'birth_municipality'          => 'Paris',
            'document_type'               => $withDocument ? 'passport' : '',
            'document_number'             => $withDocument ? 'X1234567' : '',
            'document_issue_country_code' => $withDocument ? 'FR' : '',
            'document_issue_place'        => '',
        ];
    }

    public function test_single_guest_booking_classified_as_type_16_requires_document(): void
    {
        $booking = $this->createBooking(1);

        // Save without document data: store() succeeds (documents aren't
        // enforced at save time), but confirm() must still reject it because
        // a single guest is classified as type 16 (document required).
        $this->post(route('checkin.store', $booking->checkin_token), [
            'guests' => [$this->guestPayload($booking->person_id, withDocument: false)],
        ])->assertRedirect(route('checkin.show', $booking->checkin_token));

        $this->post(route('checkin.confirm', $booking->checkin_token))
            ->assertSessionHas('error');
        $this->assertNull($booking->fresh()->checkin_completed_at);

        // With full document data, confirmation succeeds.
        $this->post(route('checkin.store', $booking->checkin_token), [
            'guests' => [$this->guestPayload($booking->person_id, withDocument: true)],
        ])->assertRedirect(route('checkin.show', $booking->checkin_token))
          ->assertSessionHas('success');

        $booking->person->refresh();
        $this->assertSame('passport', $booking->person->document_type);

        $this->post(route('checkin.confirm', $booking->checkin_token))
            ->assertSessionHas('success');
        $this->assertNotNull($booking->fresh()->checkin_completed_at);
    }

    public function test_multi_guest_booking_classifies_primary_as_capogruppo_and_companions_as_membro_gruppo(): void
    {
        $booking = $this->createBooking(3);
        $companion1 = Person::create(['first_name' => 'Marco', 'last_name' => 'Rossi']);
        $companion2 = Person::create(['first_name' => 'Luca', 'last_name' => 'Bianchi']);
        $booking->additionalGuests()->attach([$companion1->id, $companion2->id]);

        // Primary guest (index 0, capogruppo/type 18) has a document; companions
        // (index 1/2, membro gruppo/type 20) intentionally don't.
        $payload = [
            $this->guestPayload($booking->person_id, withDocument: true),
            $this->guestPayload($companion1->id, withDocument: false),
            $this->guestPayload($companion2->id, withDocument: false),
        ];

        $this->post(route('checkin.store', $booking->checkin_token), ['guests' => $payload])
            ->assertRedirect(route('checkin.show', $booking->checkin_token))
            ->assertSessionHas('success');

        $companion1->refresh();
        $this->assertSame('M', $companion1->gender);
        $this->assertNull($companion1->document_type);

        // Confirmation succeeds even though companions lack document data,
        // because they are classified as type 20 (no document required).
        $this->post(route('checkin.confirm', $booking->checkin_token))
            ->assertSessionHas('success');
        $this->assertNotNull($booking->fresh()->checkin_completed_at);
    }

    public function test_adding_companion_beyond_total_guest_count_is_rejected(): void
    {
        $booking = $this->createBooking(1);

        $this->post(route('checkin.companions.store', $booking->checkin_token), [
            'first_name' => 'Extra',
            'last_name'  => 'Guest',
        ])->assertRedirect(route('checkin.show', $booking->checkin_token))
          ->assertSessionHas('error');

        $this->assertSame(0, $booking->additionalGuests()->count());
    }

    public function test_adding_companion_within_cap_attaches_new_person(): void
    {
        $booking = $this->createBooking(2);

        $this->post(route('checkin.companions.store', $booking->checkin_token), [
            'first_name' => 'Extra',
            'last_name'  => 'Guest',
        ])->assertRedirect(route('checkin.show', $booking->checkin_token))
          ->assertSessionHas('success');

        $this->assertSame(1, $booking->additionalGuests()->count());
        $this->assertSame('Extra', $booking->additionalGuests()->first()->first_name);
    }

    public function test_submitting_checkin_form_persists_person_data(): void
    {
        $booking = $this->createBooking(1);

        $this->post(route('checkin.store', $booking->checkin_token), [
            'guests' => [$this->guestPayload($booking->person_id, withDocument: true)],
        ])->assertRedirect();

        $booking->person->refresh();
        $this->assertSame('M', $booking->person->gender);
        $this->assertSame('FR', $booking->person->nationality_code);
        $this->assertSame('X1234567', $booking->person->document_number);
    }

    public function test_confirming_sets_checkin_completed_at(): void
    {
        $booking = $this->createBooking(1);

        $this->post(route('checkin.store', $booking->checkin_token), [
            'guests' => [$this->guestPayload($booking->person_id, withDocument: true)],
        ]);

        $this->post(route('checkin.confirm', $booking->checkin_token))
            ->assertRedirect(route('checkin.show', $booking->checkin_token))
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertNotNull($booking->checkin_completed_at);
    }

    public function test_confirmation_rejected_when_required_fields_are_missing(): void
    {
        $booking = $this->createBooking(1);

        $this->post(route('checkin.confirm', $booking->checkin_token))
            ->assertRedirect(route('checkin.show', $booking->checkin_token))
            ->assertSessionHas('error');

        $booking->refresh();
        $this->assertNull($booking->checkin_completed_at);
    }

    public function test_editing_after_confirmation_is_still_possible(): void
    {
        $booking = $this->createBooking(1);

        $this->post(route('checkin.store', $booking->checkin_token), [
            'guests' => [$this->guestPayload($booking->person_id, withDocument: true)],
        ]);
        $this->post(route('checkin.confirm', $booking->checkin_token));

        $booking->refresh();
        $this->assertNotNull($booking->checkin_completed_at);

        $updatedPayload = $this->guestPayload($booking->person_id, withDocument: true);
        $updatedPayload['document_number'] = 'NEWDOC999';

        $this->post(route('checkin.store', $booking->checkin_token), [
            'guests' => [$updatedPayload],
        ])->assertRedirect(route('checkin.show', $booking->checkin_token));

        $booking->person->refresh();
        $this->assertSame('NEWDOC999', $booking->person->document_number);
        $this->assertNotNull($booking->fresh()->checkin_completed_at);
    }
}
