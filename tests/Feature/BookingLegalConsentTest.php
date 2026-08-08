<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\BookingRequestMail;
use App\Mail\BookingRequestPendingMail;
use App\Models\BookingRequest;
use App\Models\PricingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingLegalConsentTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'checkin'    => now()->addDays(10)->format('Y-m-d'),
            'checkout'   => now()->addDays(15)->format('Y-m-d'),
            'adults'     => 2,
            'children'   => 0,
            'babies'     => 1,
            'pets'       => 1,
            'first_name' => 'Mario',
            'last_name'  => 'Rossi',
            'email'      => 'mario.rossi@example.com',
            'phone'      => '333 1234567',
            'phone_prefix' => '+39',
            'message'    => 'Test message',
        ];
    }

    public function test_request_without_accepted_terms_is_rejected(): void
    {
        Mail::fake();

        $this->postJson(route('it.booking.request'), $this->validPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['accepted_terms']);

        $this->assertDatabaseCount('booking_requests', 0);
        Mail::assertNothingSent();
    }

    public function test_request_with_accepted_terms_creates_booking_request_with_consent_proof(): void
    {
        Mail::fake();

        $payload = $this->validPayload() + ['accepted_terms' => '1'];

        $this->postJson(route('it.booking.request'), $payload, ['User-Agent' => 'PHPUnit-Agent'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('booking_requests', 1);

        $bookingRequest = BookingRequest::first();
        $this->assertNotNull($bookingRequest->terms_accepted_at);
        $this->assertSame('mario.rossi@example.com', $bookingRequest->email);
        $this->assertNotEmpty($bookingRequest->user_agent);
        $this->assertSame(1, $bookingRequest->babies);
        $this->assertSame(1, $bookingRequest->pets);
    }

    public function test_request_rejects_adults_and_children_above_apartment_capacity(): void
    {
        Mail::fake();

        $payload = array_merge($this->validPayload(), [
            'adults' => 6,
            'children' => 1,
            'accepted_terms' => '1',
        ]);

        $this->postJson(route('it.booking.request'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['children']);

        $this->assertDatabaseCount('booking_requests', 0);
        Mail::assertNothingSent();
    }

    public function test_infants_and_pets_do_not_count_towards_apartment_capacity(): void
    {
        Mail::fake();

        $payload = array_merge($this->validPayload(), [
            'adults' => 6,
            'children' => 0,
            'babies' => 3,
            'pets' => 3,
            'accepted_terms' => '1',
        ]);

        $this->postJson(route('it.booking.request'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('booking_requests', 1);
    }

    public function test_successful_request_sends_owner_and_guest_emails(): void
    {
        Mail::fake();

        $payload = $this->validPayload() + ['accepted_terms' => '1'];

        $this->postJson(route('it.booking.request'), $payload)->assertOk();

        Mail::assertSent(BookingRequestMail::class, function (BookingRequestMail $mail) {
            return $mail->hasTo(config('apartment.email'));
        });

        Mail::assertSent(BookingRequestPendingMail::class, function (BookingRequestPendingMail $mail) {
            return $mail->hasTo('mario.rossi@example.com');
        });
    }

    public function test_terms_page_renders_for_every_locale(): void
    {
        foreach (['it', 'en', 'fr', 'de'] as $locale) {
            $this->get(route("{$locale}.terms"))->assertStatus(200);
        }
    }

    public function test_successful_request_stores_the_server_computed_price_estimate(): void
    {
        Mail::fake();

        // Recurring rule covering the whole year: 100€/night.
        PricingRule::create([
            'start_month'     => 1,
            'start_day'       => 1,
            'end_month'       => 12,
            'end_day'         => 31,
            'price_per_night' => 10000,
        ]);

        $payload = $this->validPayload() + ['accepted_terms' => '1'];

        $this->postJson(route('it.booking.request'), $payload)->assertOk();

        $bookingRequest = BookingRequest::first();

        // 5 nights × 100€ = 500€ stay + config cleaning/linen fees.
        $cleaningFee = (float) config('apartment.booking.cleaning_fee', 0);
        $linenFee = ((float) config('apartment.booking.linen_fee_per_person', 0)) * 2;

        $this->assertSame('500.00', $bookingRequest->estimated_stay_amount);
        $this->assertSame(number_format($cleaningFee, 2, '.', ''), $bookingRequest->estimated_cleaning_amount);
        $this->assertSame(number_format($linenFee, 2, '.', ''), $bookingRequest->estimated_linen_amount);
        $this->assertSame(
            number_format(500 + $cleaningFee + $linenFee, 2, '.', ''),
            $bookingRequest->estimated_total_amount
        );
    }

    public function test_successful_request_adds_the_requested_parking_to_the_server_computed_estimate(): void
    {
        Mail::fake();

        PricingRule::create([
            'start_month'     => 1,
            'start_day'       => 1,
            'end_month'       => 12,
            'end_day'         => 31,
            'price_per_night' => 10000,
        ]);

        $payload = $this->validPayload() + [
            'accepted_terms'    => '1',
            'parking_requested' => '1',
        ];

        $this->postJson(route('it.booking.request'), $payload)->assertOk();

        $bookingRequest = BookingRequest::first();
        $parkingAmount = (float) config('apartment.booking.parking_fee_per_day', 0) * 5;

        $this->assertTrue($bookingRequest->parking_requested);
        $this->assertSame(number_format($parkingAmount, 2, '.', ''), $bookingRequest->estimated_parking_amount);
        $this->assertSame(
            number_format(500 + (float) config('apartment.booking.cleaning_fee', 0) + 50 + $parkingAmount, 2, '.', ''),
            $bookingRequest->estimated_total_amount
        );
    }
}
