<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Services\Concerns\ResolvesLengthDiscountRate;

/**
 * Turns the real direct-site total into suggested listing prices for Airbnb, Booking.com and
 * HomeToGo — accounting for each portal's commission and its own (assumed identical) length-of-stay
 * discount. Pure display/reporting concern, has no bearing on real charges.
 */
class OtaPortalPricingService
{
    use ResolvesLengthDiscountRate;

    private const PORTALS = ['airbnb', 'booking', 'hometogo'];

    private const DEFAULT_COMMISSION_RATES = [
        'airbnb' => '0.155',
        'booking' => '0.165',
        'hometogo' => '0.155',
    ];

    /** @return array<string, array{total_cents: int, avg_per_night_cents: int, commission_rate: float}> */
    public function suggest(int $totalCents, int $nights): array
    {
        $lengthDiscountRate = $this->lengthDiscountRateForNights($nights);

        $portals = [];
        foreach (self::PORTALS as $portal) {
            $commissionRate = $this->resolveCommissionRate($portal);
            $factor = (1 - $lengthDiscountRate) * (1 - $commissionRate);
            $portalTotalCents = $factor > 0 ? (int) round($totalCents / $factor) : 0;

            $portals[$portal] = [
                'total_cents' => $portalTotalCents,
                'avg_per_night_cents' => $nights > 0 ? (int) round($portalTotalCents / $nights) : 0,
                'commission_rate' => $commissionRate,
            ];
        }

        return $portals;
    }

    private function resolveCommissionRate(string $portal): float
    {
        return (float) Setting::get("pricing_commission_{$portal}", self::DEFAULT_COMMISSION_RATES[$portal]);
    }
}
