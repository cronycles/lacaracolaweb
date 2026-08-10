<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingRequest extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'checkin',
        'checkout',
        'adults',
        'children',
        'babies',
        'pets',
        'parking_requested',
        'message',
        'newsletter',
        'terms_accepted_at',
        'ip_address',
        'user_agent',
        'locale',
        'declined_at',
        'estimated_stay_amount',
        'estimated_cleaning_amount',
        'estimated_linen_amount',
        'estimated_parking_amount',
        'estimated_total_amount',
    ];

    protected $casts = [
        'checkin'                   => 'date',
        'checkout'                  => 'date',
        'adults'                    => 'integer',
        'children'                  => 'integer',
        'babies'                    => 'integer',
        'pets'                      => 'integer',
        'parking_requested'         => 'boolean',
        'newsletter'                => 'boolean',
        'terms_accepted_at'         => 'datetime',
        'declined_at'               => 'datetime',
        'estimated_stay_amount'     => 'decimal:2',
        'estimated_cleaning_amount' => 'decimal:2',
        'estimated_linen_amount'    => 'decimal:2',
        'estimated_parking_amount'  => 'decimal:2',
        'estimated_total_amount'    => 'decimal:2',
    ];

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    public function availabilityBlock(): HasOne
    {
        return $this->hasOne(AvailabilityBlock::class);
    }

    /** Full name helper, mirrors `Person::getFullNameAttribute()`. */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Requests still awaiting owner action: not declined, and not yet
     * converted into a Booking.
     *
     * `withTrashed()` on the relation is required: `Booking` uses SoftDeletes,
     * so deleting the linked booking later (e.g. cleaning up a test booking)
     * must NOT resurrect the already-confirmed request into the pending
     * queue — the request was already handled.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('declined_at')
            ->whereDoesntHave('booking', fn (Builder $q) => $q->withTrashed());
    }
}
