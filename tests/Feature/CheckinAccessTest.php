<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Country;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckinAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createBooking(array $overrides = []): Booking
    {
        Country::firstOrCreate(['iso2' => 'FR'], ['name_it' => 'Francia']);

        $person = Person::create([
            'first_name' => 'Anna',
            'last_name'  => 'Verdi',
            'email'      => 'anna.verdi@example.com',
        ]);

        $booking = Booking::create(array_merge([
            'person_id' => $person->id,
            'checkin'   => now()->addDays(10)->format('Y-m-d'),
            'checkout'  => now()->addDays(15)->format('Y-m-d'),
            'adults'    => 1,
        ], $overrides));

        $booking->generateCheckinToken();

        return $booking;
    }

    public function test_valid_token_shows_the_checkin_form(): void
    {
        $booking = $this->createBooking();

        $this->get(route('checkin.show', $booking->checkin_token))
            ->assertOk()
            ->assertSee('Verdi');
    }

    public function test_unknown_token_returns_not_found(): void
    {
        $this->get(route('checkin.show', 'this-token-does-not-exist'))
            ->assertNotFound();
    }

    public function test_expired_token_shows_error_page_without_leaking_guest_data(): void
    {
        $booking = $this->createBooking();
        $booking->forceFill(['checkin_token_expires_at' => now()->subDay()])->save();

        $response = $this->get(route('checkin.show', $booking->checkin_token));

        $response->assertOk();
        $response->assertDontSee('Verdi');
    }

    public function test_canceled_booking_token_shows_error_page(): void
    {
        $booking = $this->createBooking(['canceled_at' => now()]);

        $response = $this->get(route('checkin.show', $booking->checkin_token));

        $response->assertOk();
        $response->assertDontSee('Verdi');
    }

    public function test_completed_checkin_shows_summary_instead_of_editable_form(): void
    {
        $booking = $this->createBooking(['checkin_completed_at' => now()]);

        $this->get(route('checkin.show', $booking->checkin_token))
            ->assertOk()
            ->assertSee(__('app.checkin_summary_title'))
            ->assertSee(route('checkin.edit', $booking->checkin_token))
            ->assertDontSee('data-checkin-submit');
    }
}
