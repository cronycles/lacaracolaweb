<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExternalCalendarProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ExternalCalendarFeedClient
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    public function download(ExternalCalendarProvider $provider): string
    {
        if (blank($provider->url)) {
            throw new RuntimeException("External calendar provider {$provider->key} has no feed URL.");
        }

        try {
            $response = Http::timeout((int) config('apartment.calendar.http_timeout', 10))
                ->withHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'text/calendar, text/plain, */*',
                    'Accept-Language' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
                ])
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($provider->url);
        } catch (ConnectionException $exception) {
            Log::error("External calendar provider {$provider->key} request failed.", [
                'url' => $provider->url,
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException("External calendar provider {$provider->key} request failed: {$exception->getMessage()}.", previous: $exception);
        }

        if (! $response->successful()) {
            Log::error("External calendar provider {$provider->key} returned an error response.", [
                'url' => $provider->url,
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException("External calendar provider {$provider->key} returned HTTP {$response->status()}.");
        }

        return $response->body();
    }
}
