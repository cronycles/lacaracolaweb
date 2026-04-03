<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'person_id',
        'checkin',
        'checkout',
        'adults',
        'children',
        'babies',
        'pets',
        'source',
        'external_ref',
        'notes',
    ];

    protected $casts = [
        'checkin'  => 'date',
        'checkout' => 'date',
        'adults'   => 'integer',
        'children' => 'integer',
        'babies'   => 'integer',
        'pets'     => 'integer',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function availabilityBlock(): HasOne
    {
        return $this->hasOne(AvailabilityBlock::class);
    }

    /** Guests occupying bed spaces */
    public function getTotalGuestsAttribute(): int
    {
        return $this->adults + $this->children;
    }

    /** Number of nights */
    public function getNightsAttribute(): int
    {
        return (int) $this->checkin->diffInDays($this->checkout);
    }
}
