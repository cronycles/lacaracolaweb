<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExternalCalendarProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExternalCalendarFeedClient
{
    public function download(ExternalCalendarProvider $provider): string
    {
        if (blank($provider->url)) {
            throw new RuntimeException("External calendar provider {$provider->key} has no feed URL.");
        }

        $response = Http::timeout((int) config('apartment.calendar.http_timeout', 10))
            ->accept('text/calendar')
            ->get($provider->url);

        if (! $response->successful()) {
            throw new RuntimeException("External calendar provider {$provider->key} returned HTTP {$response->status()}.");
        }

        return $response->body();
    }
}
