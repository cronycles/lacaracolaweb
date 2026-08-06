<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingRequest extends Model
{
    protected $fillable = [
        'name',
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

    /**
     * Requests still awaiting owner action: not declined, and not yet
     * converted into a Booking.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('declined_at')->whereDoesntHave('booking');
    }
}
