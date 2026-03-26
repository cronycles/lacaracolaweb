<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'price_per_night',
        'min_nights',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'price_per_night' => 'integer',
        'min_nights'      => 'integer',
    ];

    /** Price in euros (stored as cents) */
    public function getPriceEurosAttribute(): float
    {
        return $this->price_per_night / 100;
    }
}
