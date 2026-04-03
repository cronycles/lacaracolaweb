<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PricingRule;
use Carbon\Carbon;

class PricingQuoteService
{
    /**
     * @return array{available: bool, nights: int, total_cents: int|null}
     */
    public function calculate(string $checkin, string $checkout): array
    {
        $checkinDate = Carbon::parse($checkin)->startOfDay();
        $checkoutDate = Carbon::parse($checkout)->startOfDay();
        $nights = (int) $checkinDate->diffInDays($checkoutDate);

        if ($nights <= 0) {
            return [
                'available' => false,
                'nights' => 0,
                'total_cents' => null,
            ];
        }

        $rules = PricingRule::query()
            ->get();

        $totalCents = 0;
        $cursor = $checkinDate->copy();

        while ($cursor->lt($checkoutDate)) {
            $ruleForNight = $rules
                ->filter(fn (PricingRule $rule): bool => $this->matchesMonthDayRule($cursor, $rule))
                ->sortBy(fn (PricingRule $rule): int => $this->ruleSpanInDays($rule))
                ->first();

            if (! $ruleForNight) {
                return [
                    'available' => false,
                    'nights' => $nights,
                    'total_cents' => null,
                ];
            }

            $totalCents += (int) $ruleForNight->price_per_night;
            $cursor->addDay();
        }

        return [
            'available' => true,
            'nights' => $nights,
            'total_cents' => $totalCents,
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
}
