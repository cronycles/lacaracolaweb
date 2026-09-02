<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\PricingRule;
use App\Models\Setting;
use App\Services\OtaPortalNightlyRateService;
use App\Services\PricingQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtaPortalNightlyRateServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeRule(int $pricePerNightCents): PricingRule
    {
        return PricingRule::create([
            'start_month' => 1,
            'start_day' => 1,
            'end_month' => 12,
            'end_day' => 31,
            'price_per_night' => $pricePerNightCents,
        ]);
    }

    public function test_worked_example_at_default_settings(): void
    {
        // 100€/night, default reference nights (3, min_nights) and guests (6, bed capacity).
        $rule = $this->makeRule(10000);

        $rates = app(OtaPortalNightlyRateService::class)->ratesFor($rule);

        $this->assertSame(23800, $rates['airbnb']['nightly_rate_cents']);
        $this->assertSame(0.155, $rates['airbnb']['commission_rate']);

        $this->assertSame(24100, $rates['booking']['nightly_rate_cents']);
        $this->assertSame(0.165, $rates['booking']['commission_rate']);

        $this->assertSame(23800, $rates['hometogo']['nightly_rate_cents']);
        $this->assertSame(0.155, $rates['hometogo']['commission_rate']);
    }

    public function test_never_cheaper_than_direct_at_default_settings_for_any_guest_count(): void
    {
        $rule = $this->makeRule(10000);
        $referenceNights = (int) config('apartment.booking.min_nights', 3);
        $bedCapacity = (int) config('apartment.specs.beds', 6);

        $rates = app(OtaPortalNightlyRateService::class)->ratesFor($rule);

        $checkin = now()->addDays(30)->startOfDay();
        $checkout = $checkin->copy()->addDays($referenceNights);

        foreach (range(1, $bedCapacity) as $guests) {
            $directQuote = app(PricingQuoteService::class)->calculate(
                $checkin->format('Y-m-d'),
                $checkout->format('Y-m-d'),
                $guests
            );

            foreach ($rates as $portal => $rate) {
                $portalTotalCents = $rate['nightly_rate_cents'] * $referenceNights;

                $this->assertGreaterThanOrEqual(
                    $directQuote['total_cents'],
                    $portalTotalCents,
                    "Portal {$portal} is cheaper than direct for {$guests} guests"
                );
            }
        }
    }

    public function test_reference_nights_above_weekly_threshold_applies_length_discount(): void
    {
        Setting::set('pricing_portal_reference_nights', '7');
        $rule = $this->makeRule(10000);

        $rates = app(OtaPortalNightlyRateService::class)->ratesFor($rule);

        // At 7 reference nights the 10% weekly discount now applies to the reference stay.
        $this->assertSame(15800, $rates['airbnb']['nightly_rate_cents']);
    }

    public function test_reference_settings_are_editable(): void
    {
        Setting::set('pricing_portal_reference_nights', '3');
        Setting::set('pricing_portal_reference_guests', '2');
        $rule = $this->makeRule(10000);

        $defaultGuestsRates = app(OtaPortalNightlyRateService::class)->ratesFor($rule);

        Setting::set('pricing_portal_reference_guests', '6');
        $bedCapacityRates = app(OtaPortalNightlyRateService::class)->ratesFor($rule);

        // A lower reference guest count amortises linen over fewer guests, so the rate is lower.
        $this->assertLessThan($bedCapacityRates['airbnb']['nightly_rate_cents'], $defaultGuestsRates['airbnb']['nightly_rate_cents']);
    }
}
