<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Models\Setting;

/** Shared weekly/monthly stay-length discount lookup, used both for the real direct price and the portal price suggestion. */
trait ResolvesLengthDiscountRate
{
    private const WEEKLY_THRESHOLD_NIGHTS = 7;

    private const MONTHLY_THRESHOLD_NIGHTS = 28;

    private function resolveWeeklyDiscountRate(): float
    {
        return (float) Setting::get('pricing_weekly_discount_percent', '0.10');
    }

    private function resolveMonthlyDiscountRate(): float
    {
        return (float) Setting::get('pricing_monthly_discount_percent', '0.20');
    }

    /** Monthly tier replaces, never stacks with, the weekly tier. */
    private function lengthDiscountRateForNights(int $nights): float
    {
        if ($nights >= self::MONTHLY_THRESHOLD_NIGHTS) {
            return $this->resolveMonthlyDiscountRate();
        }

        if ($nights >= self::WEEKLY_THRESHOLD_NIGHTS) {
            return $this->resolveWeeklyDiscountRate();
        }

        return 0.0;
    }
}
