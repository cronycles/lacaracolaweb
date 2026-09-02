<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarProvider;
use App\Models\PricingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ExternalCalendarAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_rejects_an_enabled_successfully_synchronized_external_conflict(): void
    {
        [$checkin, $checkout] = $this->stayDates();
        $this->createExternalEvent($checkin, $checkout);

        $this->postJson(route('it.booking.quote'), ['checkin' => $checkin, 'checkout' => $checkout])
            ->assertOk()
            ->assertJson(['available' => false])
            ->assertJsonPath('message', __('app.booking_dates_unavailable'));
    }

    public function test_request_rejects_external_conflict_before_creating_a_request_or_block(): void
    {
        Mail::fake();
        [$checkin, $checkout] = $this->stayDates();
        $this->createExternalEvent($checkin, $checkout);

        $this->postJson(route('it.booking.request'), $this->requestPayload($checkin, $checkout))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('checkin');

        $this->assertDatabaseCount('booking_requests', 0);
        $this->assertDatabaseCount('availability_blocks', 0);
        Mail::assertNothingSent();
    }

    public function test_external_event_checkout_day_is_available(): void
    {
        [$checkin, $checkout] = $this->stayDates();
        $this->createExternalEvent($checkin, $checkout);
        $this->addPricingRuleFor($checkout, 3);
        $nextCheckout = now()->parse($checkout)->addDays(3)->toDateString();

        $this->postJson(route('it.booking.quote'), ['checkin' => $checkout, 'checkout' => $nextCheckout])
            ->assertOk()
            ->assertJson(['available' => true]);
    }

    public function test_disabled_and_never_synchronized_providers_do_not_block_availability(): void
    {
        [$checkin, $checkout] = $this->stayDates();
        $this->addPricingRuleFor($checkin, 3);
        $this->createExternalEvent($checkin, $checkout, ['enabled' => false]);
        $this->createExternalEvent($checkin, $checkout, ['last_successful_sync_at' => null, 'sync_status' => 'never_synced']);

        $this->postJson(route('it.booking.quote'), ['checkin' => $checkin, 'checkout' => $checkout])
            ->assertOk()
            ->assertJson(['available' => true]);
    }

    public function test_public_calendar_exposes_only_eligible_external_event_dates(): void
    {
        [$checkin, $checkout] = $this->stayDates();
        $this->createExternalEvent($checkin, $checkout);
        $this->createExternalEvent($checkin, $checkout, ['enabled' => false]);

        $this->get(route('it.home'))
            ->assertViewHas('unavailableDates', function (array $dates) use ($checkin, $checkout): bool {
                return in_array($checkin, $dates, true)
                    && in_array(now()->parse($checkout)->subDay()->toDateString(), $dates, true)
                    && ! in_array($checkout, $dates, true);
            });
    }

    /** @param array<string, mixed> $providerAttributes */
    private function createExternalEvent(string $startDate, string $endDate, array $providerAttributes = []): ExternalCalendarEvent
    {
        $provider = ExternalCalendarProvider::factory()->create(array_merge([
            'key' => fake()->unique()->slug(2),
            'enabled' => true,
            'last_successful_sync_at' => now(),
        ], $providerAttributes));

        return ExternalCalendarEvent::factory()->create([
            'external_calendar_provider_id' => $provider->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /** @return array{string, string} */
    private function stayDates(): array
    {
        $checkin = now()->addDays((int) config('apartment.booking.min_booking_lead_days', 7) + 3)->startOfDay();

        return [$checkin->toDateString(), $checkin->copy()->addDays(3)->toDateString()];
    }

    private function addPricingRuleFor(string $checkin, int $nights): void
    {
        $start = now()->parse($checkin);
        $end = $start->copy()->addDays($nights - 1);

        PricingRule::create([
            'start_month' => (int) $start->format('n'),
            'start_day' => (int) $start->format('j'),
            'end_month' => (int) $end->format('n'),
            'end_day' => (int) $end->format('j'),
            'price_per_night' => 10000,
        ]);
    }

    /** @return array<string, string|int> */
    private function requestPayload(string $checkin, string $checkout): array
    {
        return [
            'checkin' => $checkin,
            'checkout' => $checkout,
            'adults' => 2,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'mario.rossi@example.com',
            'phone' => '333 1234567',
            'phone_prefix' => '+39',
            'accepted_terms' => '1',
        ];
    }
}
