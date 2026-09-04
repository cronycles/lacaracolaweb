<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\OtaPortalPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtaPortalPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_nightly_rate_worked_example_at_default_settings(): void
    {
        // 100€/night, fixed 2-guest reference, default pricing_min_nights (3, from config).
        $service = app(OtaPortalPricingService::class);

        $this->assertSame(14200, $service->baseNightlyRateCents(10000, 'airbnb'));
        $this->assertSame(14400, $service->baseNightlyRateCents(10000, 'booking'));
        $this->assertSame(14200, $service->baseNightlyRateCents(10000, 'hometogo'));
    }

    public function test_commission_rate_defaults(): void
    {
        $service = app(OtaPortalPricingService::class);

        $this->assertSame(0.155, $service->commissionRate('airbnb'));
        $this->assertSame(0.165, $service->commissionRate('booking'));
        $this->assertSame(0.155, $service->commissionRate('hometogo'));
    }

    public function test_base_nightly_rate_ignores_the_weekly_monthly_discount_even_when_min_nights_crosses_it(): void
    {
        Setting::set('pricing_min_nights', '7');

        // Only the linen-recovery amortisation changes (spread over 7 instead of 3 nights) —
        // baseNightlyRateCents() never applies a weekly/monthly discount factor (see design.md
        // Decision 4); that only happens inside guestFacingTotal(), based on the real stay length.
        $this->assertSame(12900, app(OtaPortalPricingService::class)->baseNightlyRateCents(10000, 'airbnb'));
    }

    public function test_extra_guest_fee_default_and_is_editable(): void
    {
        $this->assertSame(1200, app(OtaPortalPricingService::class)->extraGuestFeeCents());

        Setting::set('pricing_extra_guest_fee', '15');

        $this->assertSame(1500, app(OtaPortalPricingService::class)->extraGuestFeeCents());
    }

    public function test_cleaning_fee_defaults_from_config_and_is_editable(): void
    {
        $this->assertSame(10000, app(OtaPortalPricingService::class)->cleaningFeeCents());

        Setting::set('pricing_cleaning_fee', '120');

        $this->assertSame(12000, app(OtaPortalPricingService::class)->cleaningFeeCents());
    }

    public function test_guest_facing_total_for_2_guests_below_the_weekly_threshold(): void
    {
        $result = app(OtaPortalPricingService::class)->guestFacingTotal(
            stayGrossCents: 30000, // 3 nights × 100€/night raw
            nights: 3,
            guests: 2,
            portal: 'airbnb',
            directTotalCents: 44501,
        );

        $this->assertSame(52664, $result['guest_total_cents']);
        $this->assertSame(44501, $result['owner_net_cents']);
        $this->assertSame(0.155, $result['commission_rate']);
        $this->assertTrue($result['margin_safe']);
    }

    public function test_guest_facing_total_applies_the_weekly_discount_for_a_10_night_stay(): void
    {
        $result = app(OtaPortalPricingService::class)->guestFacingTotal(
            stayGrossCents: 100000, // 10 nights × 100€/night raw — qualifies for the 10% weekly discount
            nights: 10,
            guests: 2,
            portal: 'airbnb',
            directTotalCents: 0,
        );

        $this->assertSame(137992, $result['guest_total_cents']);
        $this->assertSame(116603, $result['owner_net_cents']);
    }

    public function test_guest_facing_total_adds_the_extra_guest_surcharge_beyond_2_guests(): void
    {
        $result = app(OtaPortalPricingService::class)->guestFacingTotal(
            stayGrossCents: 30000,
            nights: 3,
            guests: 4, // 2 guests beyond the 2-guest reference
            portal: 'airbnb',
            directTotalCents: 0,
        );

        $this->assertSame(59864, $result['guest_total_cents']);
    }

    public function test_guest_facing_total_margin_safe_is_false_when_owner_net_falls_short_of_direct(): void
    {
        $result = app(OtaPortalPricingService::class)->guestFacingTotal(
            stayGrossCents: 30000,
            nights: 3,
            guests: 2,
            portal: 'airbnb',
            directTotalCents: 44502, // 1 cent above the computed owner net (44501)
        );

        $this->assertFalse($result['margin_safe']);
    }
}
