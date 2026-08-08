<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PricingRule;
use Carbon\Carbon;

class PricingQuoteService
{
    /**
     * Calculate the full price breakdown for a stay using the linear amortisation model.
     *
     * Formula: Total = fixed_costs + sum(nightly_rate per night)
     *   fixed_costs = cleaning_fee + (linen_fee_per_person × guests)
     *   avg_per_night = total / nights
     *
    * @return array{available: bool, nights: int, guests: int, parking_requested: bool, parking_cents: int|null, stay_cents: int|null, cleaning_cents: int|null, linen_cents: int|null, total_cents: int|null, avg_per_night_cents: int|null}
     */
    public function calculate(string $checkin, string $checkout, int $guests = 1, bool $parkingRequested = false): array
    {
        $checkinDate = Carbon::parse($checkin)->startOfDay();
        $checkoutDate = Carbon::parse($checkout)->startOfDay();
        $hidePriceFromDate = $this->resolveHidePriceFromDate();
        $nights = (int) $checkinDate->diffInDays($checkoutDate);

        $minNights = (int) config('apartment.booking.min_nights', 3);
        $maxNights = (int) config('apartment.booking.max_nights', 28);

        if ($nights <= 0 || $nights < $minNights || $nights > $maxNights) {
            return $this->unavailable($nights, $guests, $parkingRequested);
        }

        $rules = PricingRule::query()->get();

        $stayCents = 0;
        $cursor = $checkinDate->copy();

        while ($cursor->lt($checkoutDate)) {
            if ($hidePriceFromDate !== null && $cursor->greaterThanOrEqualTo($hidePriceFromDate)) {
                return $this->unavailable($nights, $guests, $parkingRequested);
            }

            $ruleForNight = $rules
                ->filter(fn (PricingRule $rule): bool => $this->matchesRule($cursor, $rule))
                ->sortBy(fn (PricingRule $rule): string => sprintf('%d-%05d', $rule->year === null ? 1 : 0, $this->ruleSpanInDays($rule)))
                ->first();

            if (! $ruleForNight) {
                return $this->unavailable($nights, $guests, $parkingRequested);
            }

            $stayCents += (int) $ruleForNight->price_per_night;
            $cursor->addDay();
        }

        // Fixed costs (calculated once per booking, independent of nights)
        $cleaningCents = ((int) config('apartment.booking.cleaning_fee', 0)) * 100;
        $linenCentsPerPerson = ((int) config('apartment.booking.linen_fee_per_person', 0)) * 100;
        $linenCents = $linenCentsPerPerson * max(1, $guests);
        $parkingCents = $parkingRequested
            ? ((int) config('apartment.booking.parking_fee_per_day', 0)) * 100 * $nights
            : 0;

        $totalCents = $stayCents + $cleaningCents + $linenCents + $parkingCents;
        $avgPerNightCents = $nights > 0 ? (int) round($totalCents / $nights) : 0;

        return [
            'available' => true,
            'nights' => $nights,
            'guests' => $guests,
            'parking_requested' => $parkingRequested,
            'parking_cents' => $parkingCents,
            'stay_cents' => $stayCents,
            'cleaning_cents' => $cleaningCents,
            'linen_cents' => $linenCents,
            'total_cents' => $totalCents,
            'avg_per_night_cents' => $avgPerNightCents,
        ];
    }

    /**
    * @return array{available: bool, nights: int, guests: int, parking_requested: bool, parking_cents: null, stay_cents: null, cleaning_cents: null, linen_cents: null, total_cents: null, avg_per_night_cents: null}
     */
    private function unavailable(int $nights, int $guests, bool $parkingRequested = false): array
    {
        return [
            'available' => false,
            'nights' => $nights,
            'guests' => $guests,
            'parking_requested' => $parkingRequested,
            'parking_cents' => null,
            'stay_cents' => null,
            'cleaning_cents' => null,
            'linen_cents' => null,
            'total_cents' => null,
            'avg_per_night_cents' => null,
        ];
    }

    /** Year-specific overrides only match their exact year; recurring rules (year=null) match every year. */
    private function matchesRule(Carbon $date, PricingRule $rule): bool
    {
        if ($rule->year !== null && (int) $rule->year !== (int) $date->format('Y')) {
            return false;
        }

        return $this->matchesMonthDayRule($date, $rule);
    }

    private function matchesMonthDayRule(Carbon $date, PricingRule $rule): bool
    {
        $dateValue = ((int) $date->format('m')) * 100 + (int) $date->format('d');
        $startValue = ((int) $rule->start_month) * 100 + (int) $rule->start_day;
        $endValue = ((int) $rule->end_month) * 100 + (int) $rule->end_day;

        if ($startValue <= $endValue) {
            return $dateValue >= $startValue && $dateValue <= $endValue;
        }

        return $dateValue >= $startValue || $dateValue <= $endValue;
    }

    private function ruleSpanInDays(PricingRule $rule): int
    {
        $start = Carbon::create(2000, (int) $rule->start_month, (int) $rule->start_day)->startOfDay();
        $end = Carbon::create(2000, (int) $rule->end_month, (int) $rule->end_day)->startOfDay();

        if ($end->lt($start)) {
            $end->addYear();
        }

        return (int) $start->diffInDays($end) + 1;
    }

    private function resolveHidePriceFromDate(): ?Carbon
    {
        $raw = config('apartment.booking.hide_price_from');

        if (! is_string($raw) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
