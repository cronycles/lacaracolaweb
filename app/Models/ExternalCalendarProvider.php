<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalCalendarProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'url',
        'enabled',
        'sync_status',
        'last_sync_attempt_at',
        'last_successful_sync_at',
        'imported_event_count',
        'latest_error',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_sync_attempt_at' => 'datetime',
        'last_successful_sync_at' => 'datetime',
        'imported_event_count' => 'integer',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(ExternalCalendarEvent::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function scopeSuccessfullySynced(Builder $query): Builder
    {
        return $query->whereNotNull('last_successful_sync_at');
    }

    public function scopeAvailableForAvailability(Builder $query): Builder
    {
        return $query->enabled()->successfullySynced();
    }
}