<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PricingRule;
use App\Models\Setting;
use App\Services\Concerns\ResolvesLengthDiscountRate;
use App\Services\Concerns\ResolvesTaxGrossUp;

/**
 * Computes, for a given `PricingRule` period, a suggested blended nightly rate per OTA portal
 * (Airbnb/Booking.com/HomeToGo) that folds cleaning + linen + tax gross-up into a single figure,
 * amortised over a reference stay length/guest count, so the owner never has to expose a separate
 * cleaning-fee line item on the portal. See openspec/changes/ota-portal-price-table/design.md.
 */
class OtaPortalNightlyRateService
{
    use ResolvesLengthDiscountRate;
    use ResolvesTaxGrossUp;

    private const PORTALS = ['airbnb', 'booking', 'hometogo'];

    private const DEFAULT_COMMISSION_RATES = [
        'airbnb' => '0.155',
        'booking' => '0.165',
        'hometogo' => '0.155',
    ];

    /** @return array<string, array{nightly_rate_cents: int, commission_rate: float}> */
    public function ratesFor(PricingRule $rule): array
    {
        $referenceNights = $this->resolveReferenceNights();
        $referenceGuests = $this->resolveReferenceGuests();

        $stayGrossCents = ((int) $rule->price_per_night) * $referenceNights;
        $lengthDiscountRate = $this->lengthDiscountRateForNights($referenceNights);
        $stayDiscountCents = (int) round($stayGrossCents * $lengthDiscountRate);
        $discountedStayCents = $stayGrossCents - $stayDiscountCents;

        $cleaningCents = ((int) config('apartment.booking.cleaning_fee', 0)) * 100;
        $linenCents = ((int) config('apartment.booking.linen_fee_per_person', 0)) * 100 * $referenceGuests;
        $taxGrossUpCents = $this->taxGrossUpCents($cleaningCents, $linenCents);

        // No €5 rounding here — this is an internal reference figure, not a guest-facing charge.
        $referenceTotalCents = $discountedStayCents + $taxGrossUpCents + $cleaningCents + $linenCents;

        $rates = [];
        foreach (self::PORTALS as $portal) {
            $commissionRate = $this->resolveCommissionRate($portal);
            $portalTotalCents = $commissionRate < 1.0 ? $referenceTotalCents / (1 - $commissionRate) : 0.0;
            $nightlyRateCents = $referenceNights > 0 ? $portalTotalCents / $referenceNights : 0.0;

            $rates[$portal] = [
                // Rounded to the nearest whole euro (not €5) so equal-commission portals land equal.
                'nightly_rate_cents' => (int) round($nightlyRateCents / 100) * 100,
                'commission_rate' => $commissionRate,
            ];
        }

        return $rates;
    }

    private function resolveReferenceNights(): int
    {
        return (int) Setting::get('pricing_portal_reference_nights', (string) config('apartment.booking.min_nights', 3));
    }

    private function resolveReferenceGuests(): int
    {
        return (int) Setting::get('pricing_portal_reference_guests', (string) config('apartment.specs.beds', 6));
    }

    private function resolveCommissionRate(string $portal): float
    {
        return (float) Setting::get("pricing_commission_{$portal}", self::DEFAULT_COMMISSION_RATES[$portal]);
    }
}
