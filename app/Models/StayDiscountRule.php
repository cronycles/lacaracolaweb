<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StayDiscountRule extends Model
{
    protected $fillable = [
        'min_nights',
        'max_nights',
        'discount_percent',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'min_nights' => 'integer',
        'max_nights' => 'integer',
        'discount_percent' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForNights(Builder $query, int $nights): Builder
    {
        return $query
            ->where('min_nights', '<=', $nights)
            ->where(function (Builder $query) use ($nights): void {
                $query
                    ->whereNull('max_nights')
                    ->orWhere('max_nights', '>=', $nights);
            });
    }

    public function getRangeLabelAttribute(): string
    {
        if ($this->max_nights === null) {
            return sprintf('%d+ notti', $this->min_nights);
        }

        if ($this->max_nights === $this->min_nights) {
            return sprintf('%d notti', $this->min_nights);
        }

        return sprintf('%d-%d notti', $this->min_nights, $this->max_nights);
    }
}
