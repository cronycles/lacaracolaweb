<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalCalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_calendar_provider_id',
        'external_uid',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ExternalCalendarProvider::class, 'external_calendar_provider_id');
    }
}