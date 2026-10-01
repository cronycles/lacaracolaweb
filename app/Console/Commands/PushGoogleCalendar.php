<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\GoogleCalendarPushService;
use Illuminate\Console\Command;
use Throwable;

class PushGoogleCalendar extends Command
{
    protected $signature = 'calendar:push-google';

    protected $description = 'Push blocked booking periods to the configured Google Calendar for OTA iCal sync.';

    public function handle(GoogleCalendarPushService $pushService): int
    {
        if (! config('apartment.google_calendar_push.enabled')) {
            $this->line('Google Calendar push is disabled (apartment.google_calendar_push.enabled).');

            return self::SUCCESS;
        }

        try {
            $result = $pushService->sync();
        } catch (Throwable $exception) {
            $this->error("Google Calendar push failed: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Synced %d events (created %d, updated %d, deleted %d).',
            $result['synced'],
            $result['created'],
            $result['updated'],
            $result['deleted'],
        ));

        return self::SUCCESS;
    }
}
