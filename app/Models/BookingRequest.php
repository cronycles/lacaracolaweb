<?php

declare(strict_types=1);

namespace App\Models;

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
    ];

    protected $casts = [
        'checkin'            => 'date',
        'checkout'           => 'date',
        'adults'             => 'integer',
        'children'           => 'integer',
        'newsletter'         => 'boolean',
        'terms_accepted_at'  => 'datetime',
    ];

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }
}
