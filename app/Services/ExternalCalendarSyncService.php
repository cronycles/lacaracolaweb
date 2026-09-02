<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarProvider;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExternalCalendarSyncService
{
    public function __construct(
        private readonly ExternalCalendarFeedClient $feedClient,
        private readonly ExternalCalendarIcalParser $icalParser,
    ) {}

    /** @return array{provider: string, status: string, event_count: int, error: string|null} */
    public function syncProvider(ExternalCalendarProvider $provider): array
    {
        if (! $provider->enabled) {
            return $this->result($provider, 'skipped');
        }

        $provider->forceFill([
            'sync_status' => 'syncing',
            'last_sync_attempt_at' => now(),
            'latest_error' => null,
        ])->save();

        try {
            $events = $this->icalParser->parse($this->feedClient->download($provider));

            DB::transaction(function () use ($provider, $events): void {
                $provider->events()->delete();

                foreach ($events as $event) {
                    ExternalCalendarEvent::create([
                        'external_calendar_provider_id' => $provider->id,
                        ...$event,
                    ]);
                }

                $provider->forceFill([
                    'sync_status' => 'success',
                    'last_successful_sync_at' => now(),
                    'imported_event_count' => count($events),
                    'latest_error' => null,
                ])->save();
            });

            return $this->result($provider, 'success', count($events));
        } catch (Throwable $exception) {
            $provider->forceFill([
                'sync_status' => 'error',
                'latest_error' => $exception->getMessage(),
            ])->save();

            return $this->result($provider, 'error', error: $exception->getMessage());
        }
    }

    /** @return array<int, array{provider: string, status: string, event_count: int, error: string|null}> */
    public function syncEnabledProviders(): array
    {
        return ExternalCalendarProvider::enabled()
            ->orderBy('key')
            ->get()
            ->map(fn (ExternalCalendarProvider $provider): array => $this->syncProvider($provider))
            ->all();
    }

    /** @return array{provider: string, status: string, event_count: int, error: string|null} */
    private function result(ExternalCalendarProvider $provider, string $status, ?int $eventCount = null, ?string $error = null): array
    {
        return [
            'provider' => $provider->key,
            'status' => $status,
            'event_count' => $eventCount ?? $provider->imported_event_count,
            'error' => $error,
        ];
    }
}
