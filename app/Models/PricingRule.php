<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    protected $fillable = [
        'start_month',
        'start_day',
        'end_month',
        'end_day',
        'price_per_night',
    ];

    protected $casts = [
        'start_month'     => 'integer',
        'start_day'       => 'integer',
        'end_month'       => 'integer',
        'end_day'         => 'integer',
        'price_per_night' => 'integer',
    ];

    /** Price in euros (stored as cents) */
    public function getPriceEurosAttribute(): float
    {
        return $this->price_per_night / 100;
    }

    public function getPeriodLabelAttribute(): string
    {
        $start = sprintf('%02d/%02d', $this->start_day, $this->start_month);
        $end = sprintf('%02d/%02d', $this->end_day, $this->end_month);

        return "{$start} - {$end}";
    }
}
