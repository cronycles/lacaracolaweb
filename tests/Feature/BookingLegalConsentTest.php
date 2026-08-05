<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\BookingRequestMail;
use App\Mail\BookingRequestPendingMail;
use App\Models\BookingRequest;
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
            'name'       => 'Mario Rossi',
            'email'      => 'mario.rossi@example.com',
            'phone'      => '+39 333 1234567',
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
}
