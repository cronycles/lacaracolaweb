<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\OtaPortalPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtaPortalPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_commission_rates_match_the_worked_example_with_no_length_discount(): void
    {
        // 100€/night, 1 night, no length discount (below the 7-night threshold).
        $portals = app(OtaPortalPricingService::class)->suggest(totalCents: 10000, nights: 1);

        $this->assertSame(11834, $portals['airbnb']['total_cents']);
        $this->assertSame(0.155, $portals['airbnb']['commission_rate']);

        $this->assertSame(11976, $portals['booking']['total_cents']);
        $this->assertSame(0.165, $portals['booking']['commission_rate']);

        $this->assertSame(11834, $portals['hometogo']['total_cents']);
        $this->assertSame(0.155, $portals['hometogo']['commission_rate']);
    }

    public function test_portal_total_accounts_for_the_length_of_stay_discount(): void
    {
        // 1000€ total for a 7-night stay: qualifies for the 10% weekly discount on all portals.
        $portals = app(OtaPortalPricingService::class)->suggest(totalCents: 100000, nights: 7);

        // factor = (1 - 0.10) * (1 - 0.155) = 0.7605
        $this->assertSame(131492, $portals['airbnb']['total_cents']);
        $this->assertSame(18785, $portals['airbnb']['avg_per_night_cents']);
    }
}
