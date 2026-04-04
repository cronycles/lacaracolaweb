<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PricingRule;
use App\Models\StayDiscountRule;
use Carbon\Carbon;

class PricingQuoteService
{
    /**
    * @return array{available: bool, nights: int, stay_cents: int|null, discount_percent: int, discount_cents: int, discounted_stay_cents: int|null}
     */
    public function calculate(string $checkin, string $checkout): array
    {
        $checkinDate = Carbon::parse($checkin)->startOfDay();
        $checkoutDate = Carbon::parse($checkout)->startOfDay();
        $hidePriceFromDate = $this->resolveHidePriceFromDate();
        $nights = (int) $checkinDate->diffInDays($checkoutDate);

        if ($nights <= 0) {
            return [
                'available' => false,
                'nights' => 0,
                'stay_cents' => null,
                'discount_percent' => 0,
                'discount_cents' => 0,
                'discounted_stay_cents' => null,
            ];
        }

        $rules = PricingRule::query()
            ->get();

        $totalCents = 0;
        $cursor = $checkinDate->copy();

        while ($cursor->lt($checkoutDate)) {
            if ($hidePriceFromDate !== null && $cursor->greaterThanOrEqualTo($hidePriceFromDate)) {
                return [
                    'available' => false,
                    'nights' => $nights,
                    'stay_cents' => null,
                    'discount_percent' => 0,
                    'discount_cents' => 0,
                    'discounted_stay_cents' => null,
                ];
            }

            $ruleForNight = $rules
                ->filter(fn (PricingRule $rule): bool => $this->matchesMonthDayRule($cursor, $rule))
                ->sortBy(fn (PricingRule $rule): int => $this->ruleSpanInDays($rule))
                ->first();

            if (! $ruleForNight) {
                return [
                    'available' => false,
                    'nights' => $nights,
                    'stay_cents' => null,
                    'discount_percent' => 0,
                    'discount_cents' => 0,
                    'discounted_stay_cents' => null,
                ];
            }

            $totalCents += (int) $ruleForNight->price_per_night;
            $cursor->addDay();
        }

        $discountRule = StayDiscountRule::query()
            ->active()
            ->forNights($nights)
            ->orderBy('priority')
            ->orderByDesc('min_nights')
            ->first();

        $discountPercent = $discountRule?->discount_percent ?? 0;
        $discountCents = $discountPercent > 0
            ? (int) round(($totalCents * $discountPercent) / 100)
            : 0;

        $discountedStayCents = max(0, $totalCents - $discountCents);

        return [
            'available' => true,
            'nights' => $nights,
            'stay_cents' => $totalCents,
            'discount_percent' => (int) $discountPercent,
            'discount_cents' => $discountCents,
            'discounted_stay_cents' => $discountedStayCents,
        ];
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
