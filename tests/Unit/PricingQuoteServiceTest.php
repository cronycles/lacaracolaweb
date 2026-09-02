<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\PricingRule;
use App\Models\Setting;
use App\Services\PricingQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingQuoteServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeRule(int $pricePerNightCents): void
    {
        PricingRule::create([
            'start_month' => 1,
            'start_day' => 1,
            'end_month' => 12,
            'end_day' => 31,
            'price_per_night' => $pricePerNightCents,
        ]);
    }

    private function calculate(int $nights, int $guests = 2): array
    {
        $checkin = now()->addDays(30)->startOfDay();
        $checkout = $checkin->copy()->addDays($nights);

        return app(PricingQuoteService::class)->calculate(
            $checkin->format('Y-m-d'),
            $checkout->format('Y-m-d'),
            $guests
        );
    }

    public function test_default_tax_rate_is_applied_to_cleaning_and_linen(): void
    {
        $this->makeRule(10000); // 100€/night

        $quote = $this->calculate(nights: 5);

        // cleaning 100€ + linen 25€×2 = 150€ taxable, 21% default = 31.50€ = 3150 cents.
        $this->assertSame(3150, $quote['tax_gross_up_cents']);
    }

    public function test_zero_tax_rate_is_a_no_op(): void
    {
        Setting::set('pricing_tax_rate', '0');
        $this->makeRule(10000); // 100€/night → 500€ stay + 150€ fixed = 650€, already a multiple of €5.

        $quote = $this->calculate(nights: 5);

        $this->assertSame(0, $quote['tax_gross_up_cents']);
        $this->assertSame(65000, $quote['total_cents']);
        $this->assertSame(50000, $quote['stay_cents']);
    }

    public function test_item_excluded_from_gross_up_settings_is_not_taxed(): void
    {
        Setting::set('pricing_tax_gross_up_items', json_encode(['cleaning']));
        $this->makeRule(10000);

        $quote = $this->calculate(nights: 5);

        // Only cleaning (100€) is taxable now: 21% of 100€ = 21€ = 2100 cents.
        $this->assertSame(2100, $quote['tax_gross_up_cents']);
    }

    public function test_total_rounds_down_to_nearest_5_euros(): void
    {
        $this->makeRule(10000); // stay 500€ + tax 31.50€ + fixed 150€ = 681.50€ → rounds down to 680€.

        $quote = $this->calculate(nights: 5);

        $this->assertSame(68000, $quote['total_cents']);
    }

    public function test_total_rounds_up_to_nearest_5_euros(): void
    {
        $this->makeRule(10040); // stay 502€ + tax 31.50€ + fixed 150€ = 683.50€ → rounds up to 685€.

        $quote = $this->calculate(nights: 5);

        $this->assertSame(68500, $quote['total_cents']);
    }

    public function test_breakdown_reconciles_exactly_with_total(): void
    {
        $this->makeRule(10040);

        $quote = $this->calculate(nights: 9);

        $this->assertSame($quote['total_cents'], $quote['stay_cents'] + $quote['cleaning_cents'] + $quote['linen_cents']);
    }

    public function test_weekly_discount_applies_at_7_nights(): void
    {
        $this->makeRule(10000);

        $quote = $this->calculate(nights: 7);

        // stay gross = 700€, 10% weekly discount = 70€.
        $this->assertSame(0.10, $quote['length_discount_rate']);
        $this->assertSame(7000, $quote['stay_discount_cents']);
    }

    public function test_monthly_discount_replaces_rather_than_stacks_with_weekly_at_28_nights(): void
    {
        $this->makeRule(10000);

        $quote = $this->calculate(nights: 28);

        // stay gross = 2800€, 20% monthly discount = 560€ (not 10%+20%=30%).
        $this->assertSame(0.20, $quote['length_discount_rate']);
        $this->assertSame(56000, $quote['stay_discount_cents']);
    }

    public function test_no_discount_below_7_nights(): void
    {
        $this->makeRule(10000);

        $quote = $this->calculate(nights: 6);

        $this->assertSame(0.0, $quote['length_discount_rate']);
        $this->assertSame(0, $quote['stay_discount_cents']);
    }

    public function test_cleaning_and_linen_amounts_are_unaffected_by_the_length_discount(): void
    {
        $this->makeRule(10000);

        $shortStay = $this->calculate(nights: 5);
        $longStay = $this->calculate(nights: 28);

        $this->assertSame($shortStay['cleaning_cents'], $longStay['cleaning_cents']);
        $this->assertSame($shortStay['linen_cents'], $longStay['linen_cents']);
    }
}
