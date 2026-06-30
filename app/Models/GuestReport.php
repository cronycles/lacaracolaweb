<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestReport extends Model
{
    protected $fillable = [
        'booking_id',
        'driver',
        'mode',
        'status',
        'guests_count',
        'guests_payload',
        'soap_response',
        'error_message',
        'submitted_at',
    ];

    protected $casts = [
        'guests_payload' => 'array',
        'soap_response'  => 'array',
        'submitted_at'   => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
