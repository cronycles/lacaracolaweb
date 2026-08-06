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
        'message',
        'newsletter',
        'terms_accepted_at',
        'ip_address',
        'user_agent',
        'locale',
        'declined_at',
    ];

    protected $casts = [
        'checkin'            => 'date',
        'checkout'           => 'date',
        'adults'             => 'integer',
        'children'           => 'integer',
        'newsletter'         => 'boolean',
        'terms_accepted_at'  => 'datetime',
        'declined_at'        => 'datetime',
    ];

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    /** Full name helper, mirrors `Person::getFullNameAttribute()`. */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Requests still awaiting owner action: not declined, and not yet
     * converted into a Booking.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('declined_at')->whereDoesntHave('booking');
    }
}
