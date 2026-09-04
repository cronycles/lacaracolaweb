<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Services\Concerns\ResolvesLengthDiscountRate;
use App\Services\Concerns\ResolvesTaxGrossUp;

/**
 * Suggested OTA portal pricing (Airbnb/Booking.com/HomeToGo), used by both the read-only
 * `admin/prezzi-portali` period table and the `admin/prezzi` simulator. See
 * openspec/changes/ota-portal-guest-tiered-pricing/design.md.
 */
class OtaPortalPricingService
{
    use ResolvesLengthDiscountRate;
    use ResolvesTaxGrossUp;

    private const PORTALS = ['airbnb', 'booking', 'hometogo'];

    private const DEFAULT_COMMISSION_RATES = [
        'airbnb' => '0.155',
        'booking' => '0.165',
        'hometogo' => '0.155',
    ];

    /** Fixed reference guest count for the base nightly rate — see design.md Decision 2. */
    private const REFERENCE_GUESTS = 2;

    /** @return list<string> */
    public static function portals(): array
    {
        return self::PORTALS;
    }

    /**
     * Base nightly rate for a portal listing: the direct nightly rate plus the 2-guest reference
     * linen recovery (tax-grossed-up, amortised over the minimum stay), divided by the portal's
     * commission. Excludes the cleaning fee entirely (see design.md Decision 1).
     */
    public function baseNightlyRateCents(int $pricePerNightCents, string $portal): int
    {
        $commissionRate = $this->commissionRate($portal);
        $rateCents = $commissionRate < 1.0
            ? ($pricePerNightCents + $this->perNightLinenAddOnCents()) / (1 - $commissionRate)
            : 0.0;

        // Rounded to the nearest whole euro (not €5) so equal-commission portals land equal.
        return (int) round($rateCents / 100) * 100;
    }

    /** Shared flat surcharge (€/night/guest, from the 3rd guest onward), same for every portal. */
    public function extraGuestFeeCents(): int
    {
        return ((int) Setting::get('pricing_extra_guest_fee', '12')) * 100;
    }

    /** Flat cleaning fee — display/legend reference only, never blended into the nightly rate. */
    public function cleaningFeeCents(): int
    {
        return ((int) Setting::get('pricing_cleaning_fee', (string) config('apartment.booking.cleaning_fee', 100))) * 100;
    }

    public function commissionRate(string $portal): float
    {
        return (float) Setting::get("pricing_commission_{$portal}", self::DEFAULT_COMMISSION_RATES[$portal]);
    }

    /**
     * Real guest-facing total a portal would charge for a simulated stay (base nightly rate,
     * discounted the same as the direct site, plus the extra-guest surcharge and the flat
     * cleaning fee), and the owner's approximate net revenue after that portal's commission.
     * `margin_safe` is informational only — see design.md Decision 8 and Risks.
     *
     * @return array{guest_total_cents: int, owner_net_cents: int, commission_rate: float, margin_safe: bool}
     */
    public function guestFacingTotal(int $stayGrossCents, int $nights, int $guests, string $portal, int $directTotalCents): array
    {
        $commissionRate = $this->commissionRate($portal);

        $baseStayBeforeCents = $stayGrossCents + $this->perNightLinenAddOnCents() * $nights;
        $baseStayGrossedCents = $commissionRate < 1.0 ? $baseStayBeforeCents / (1 - $commissionRate) : 0.0;

        $lengthDiscountRate = $this->lengthDiscountRateForNights($nights);
        $baseStayDiscountedCents = (int) round($baseStayGrossedCents * (1 - $lengthDiscountRate));

        $extraGuestCents = $this->extraGuestFeeCents() * max(0, $guests - self::REFERENCE_GUESTS) * $nights;
        $guestTotalCents = $baseStayDiscountedCents + $extraGuestCents + $this->cleaningFeeCents();
        $ownerNetCents = (int) round($guestTotalCents * (1 - $commissionRate));

        return [
            'guest_total_cents' => $guestTotalCents,
            'owner_net_cents' => $ownerNetCents,
            'commission_rate' => $commissionRate,
            'margin_safe' => $ownerNetCents >= $directTotalCents,
        ];
    }

    /** 2-guest reference linen cost, tax-grossed-up, amortised over the minimum-stay setting. */
    private function perNightLinenAddOnCents(): int
    {
        $referenceNights = (int) Setting::get('pricing_min_nights', (string) config('apartment.booking.min_nights', 3));
        $linenFeeCents = ((int) Setting::get('pricing_linen_fee_per_person', (string) config('apartment.booking.linen_fee_per_person', 25))) * 100;
        $referenceLinenCents = $linenFeeCents * self::REFERENCE_GUESTS;
        $linenTaxGrossUpCents = $this->taxGrossUpCents(0, $referenceLinenCents);
        $recoverableCents = $referenceLinenCents + $linenTaxGrossUpCents;

        return $referenceNights > 0 ? (int) round($recoverableCents / $referenceNights) : $recoverableCents;
    }
}
