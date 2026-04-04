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
        'canceled_at',
    ];

    protected $casts = [
        'checkin'  => 'date',
        'checkout' => 'date',
        'adults'   => 'integer',
        'children' => 'integer',
        'babies'   => 'integer',
        'pets'     => 'integer',
        'canceled_at' => 'datetime',
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

    public function isCanceled(): bool
    {
        return $this->canceled_at !== null;
    }
}
