<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ExternalCalendarProvider;
use App\Services\ExternalCalendarSyncService;
use Illuminate\Console\Command;

class SyncExternalCalendars extends Command
{
    protected $signature = 'calendar:sync-external {--provider= : Synchronize only one provider key}';

    protected $description = 'Synchronize enabled external iCalendar feeds.';

    public function handle(ExternalCalendarSyncService $syncService): int
    {
        $providerKey = $this->option('provider');

        if (is_string($providerKey) && $providerKey !== '') {
            $provider = ExternalCalendarProvider::query()->where('key', $providerKey)->first();
            if ($provider === null) {
                $this->error("External calendar provider [{$providerKey}] was not found.");

                return self::FAILURE;
            }

            $results = [$syncService->syncProvider($provider)];
        } else {
            $results = $syncService->syncEnabledProviders();
        }

        if ($results === []) {
            $this->line('No enabled external calendar providers to synchronize.');

            return self::SUCCESS;
        }

        $hasErrors = false;
        foreach ($results as $result) {
            if ($result['status'] === 'error') {
                $hasErrors = true;
                $this->error("{$result['provider']}: {$result['error']}");

                continue;
            }

            $this->line("{$result['provider']}: {$result['status']} ({$result['event_count']} events)");
        }

        return $hasErrors ? self::FAILURE : self::SUCCESS;
    }
}
