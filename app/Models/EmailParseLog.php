<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records every email processed by the automatic inbox poller.
 *
 * @property int         $id
 * @property string      $message_uid
 * @property string      $from_address
 * @property string|null $subject
 * @property \Carbon\Carbon|null $received_at
 * @property string      $status   success|error|skipped|duplicate
 * @property int|null    $booking_id
 * @property string|null $error_message
 */
class EmailParseLog extends Model
{
    protected $fillable = [
        'message_uid',
        'from_address',
        'subject',
        'received_at',
        'status',
        'booking_id',
        'error_message',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
