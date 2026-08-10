<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Country;
use App\Models\Municipality;
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
        Municipality::create(['code' => '407010025', 'name' => 'GENOVA', 'province' => 'GE']);
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

        // A single guest is classified as type 16, which requires a document.
        $this->post(route('checkin.confirm', $booking->checkin_token), [
            'guests' => [$this->guestPayload($booking->person_id, withDocument: false)],
        ])->assertSessionHasErrors([
            'guests.0.document_type',
            'guests.0.document_number',
            'guests.0.document_issue_country_code',
        ]);

                // Full data is saved and confirmed in one request.
                $this->post(route('checkin.confirm', $booking->checkin_token), [
            'guests' => [$this->guestPayload($booking->person_id, withDocument: true)],
        ])->assertRedirect(route('checkin.show', $booking->checkin_token))
          ->assertSessionHas('success');

        $booking->person->refresh();
        $this->assertSame('passport', $booking->person->document_type);
        $this->assertNotNull($booking->fresh()->checkin_completed_at);
    }

    public function test_italian_birth_requires_birth_province(): void
    {
        $booking = $this->createBooking(1);
        $payload = $this->guestPayload($booking->person_id, withDocument: true);
        $payload['nationality_code'] = 'IT';
        $payload['birth_country_code'] = 'IT';
        $payload['birth_municipality'] = 'Genova';
        $payload['birth_province'] = '';

        $this->post(route('checkin.confirm', $booking->checkin_token), [
            'guests' => [$payload],
        ])->assertSessionHasErrors(['guests.0.birth_province']);

        $this->assertNull($booking->fresh()->checkin_completed_at);
    }

    public function test_multi_guest_booking_classifies_primary_as_capogruppo_and_companions_as_membro_gruppo(): void
    {
        $booking = $this->createBooking(3);
        $companion1 = Person::create(['first_name' => 'Marco', 'last_name' => 'Rossi']);
        $companion2 = Person::create(['first_name' => 'Luca', 'last_name' => 'Bianchi']);
        $booking->additionalGuests()->attach([$companion1->id, $companion2->id]);

        // Primary guest (index 0) is capogruppo (type 18) => document is
        // required, so omitting it is rejected outright...
        $payload = [
            $this->guestPayload($booking->person_id, withDocument: false),
            $this->guestPayload($companion1->id, withDocument: false),
            $this->guestPayload($companion2->id, withDocument: false),
        ];

        $this->post(route('checkin.confirm', $booking->checkin_token), ['guests' => $payload])
            ->assertSessionHasErrors(['guests.0.document_type']);

        // ...but companions (index 1, 2) are membro gruppo (type 20) => no
        // document required, even once the primary guest provides one.
        $payload[0] = $this->guestPayload($booking->person_id, withDocument: true);

        $this->post(route('checkin.confirm', $booking->checkin_token), ['guests' => $payload])
            ->assertRedirect(route('checkin.show', $booking->checkin_token))
            ->assertSessionHas('success');

        $companion1->refresh();
        $this->assertSame('M', $companion1->gender);
        $this->assertNull($companion1->document_type);

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

    public function test_adding_companion_normalizes_name(): void
    {
        $booking = $this->createBooking(2);

        $this->post(route('checkin.companions.store', $booking->checkin_token), [
            'first_name' => ' mARCO ',
            'last_name'  => 'rOSSI',
        ])->assertRedirect(route('checkin.show', $booking->checkin_token));

        $companion = $booking->additionalGuests()->first();
        $this->assertSame('Marco', $companion->first_name);
        $this->assertSame('Rossi', $companion->last_name);
    }

    public function test_checkin_normalizes_province_and_birth_municipality(): void
    {
        $booking = $this->createBooking(1);
        $payload = $this->guestPayload($booking->person_id, withDocument: true);
        $payload['birth_country_code'] = 'IT';
        $payload['birth_municipality'] = 'gENOVA';
        $payload['birth_province'] = 'ge';

        $this->post(route('checkin.confirm', $booking->checkin_token), [
            'guests' => [$payload],
                ])->assertRedirect(route('checkin.show', $booking->checkin_token))
                    ->assertSessionHas('success');

        $booking->person->refresh();
        $this->assertSame('Genova', $booking->person->birth_municipality);
        $this->assertSame('GE', $booking->person->birth_province);
    }

    public function test_adding_companion_preserves_unsaved_guest_input(): void
    {
        $booking = $this->createBooking(2);

        $this->post(route('checkin.companions.store', $booking->checkin_token), [
            'first_name' => 'Extra',
            'last_name'  => 'Guest',
            'guests'     => [[
                'gender' => 'F',
            ]],
        ])->assertRedirect(route('checkin.show', $booking->checkin_token))
          ->assertSessionHas('success')
          ->assertSessionHasInput('guests.0.gender', 'F');
    }

    public function test_confirmation_is_rejected_when_a_booked_guest_has_not_been_added(): void
    {
        $booking = $this->createBooking(2);

        $this->post(route('checkin.confirm', $booking->checkin_token), [
            'guests' => [$this->guestPayload($booking->person_id, withDocument: true)],
        ])->assertRedirect(route('checkin.show', $booking->checkin_token))
          ->assertSessionHas('error');

        $this->assertNull($booking->fresh()->checkin_completed_at);
    }

    public function test_submitting_checkin_form_persists_person_data(): void
    {
        $booking = $this->createBooking(1);

        $this->post(route('checkin.confirm', $booking->checkin_token), [
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

        $this->post(route('checkin.confirm', $booking->checkin_token), [
            'guests' => [$this->guestPayload($booking->person_id, withDocument: true)],
        ])
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
            ->assertSessionHasErrors();

        $booking->refresh();
        $this->assertNull($booking->checkin_completed_at);
    }

    public function test_validation_failure_preserves_submitted_guest_data(): void
    {
        $booking = $this->createBooking(1);

        $this->post(route('checkin.confirm', $booking->checkin_token), [
            'guests' => [[
                'gender' => 'M',
            ]],
        ])->assertRedirect(route('checkin.show', $booking->checkin_token))
          ->assertSessionHasErrors()
          ->assertSessionHasInput('guests.0.gender', 'M');

        $this->assertNull($booking->fresh()->checkin_completed_at);
    }

    public function test_editing_after_confirmation_is_still_possible(): void
    {
        $booking = $this->createBooking(1);

        $this->post(route('checkin.confirm', $booking->checkin_token), [
            'guests' => [$this->guestPayload($booking->person_id, withDocument: true)],
        ]);

        $booking->refresh();
        $this->assertNotNull($booking->checkin_completed_at);

        $updatedPayload = $this->guestPayload($booking->person_id, withDocument: true);
        $updatedPayload['document_number'] = 'NEWDOC999';

        $this->post(route('checkin.confirm', $booking->checkin_token), [
            'guests' => [$updatedPayload],
        ])->assertRedirect(route('checkin.show', $booking->checkin_token));

        $booking->person->refresh();
        $this->assertSame('NEWDOC999', $booking->person->document_number);
        $this->assertNotNull($booking->fresh()->checkin_completed_at);
    }
}
