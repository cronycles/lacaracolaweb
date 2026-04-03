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

        $lastNight = $checkoutDate->copy()->subDay();

        $rules = PricingRule::query()
            ->whereDate('start_date', '<=', $lastNight->toDateString())
            ->whereDate('end_date', '>=', $checkinDate->toDateString())
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $totalCents = 0;
        $cursor = $checkinDate->copy();

        while ($cursor->lt($checkoutDate)) {
            $ruleForNight = $rules->first(function (PricingRule $rule) use ($cursor): bool {
                $startDate = Carbon::parse($rule->start_date)->startOfDay();
                $endDate = Carbon::parse($rule->end_date)->startOfDay();

                return $cursor->betweenIncluded($startDate, $endDate);
            });

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
}
