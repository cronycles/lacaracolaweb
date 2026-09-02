<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarProvider;
use App\Services\ExternalCalendarSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalCalendarSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_sync_replaces_provider_events_idempotently(): void
    {
        Http::fake(['https://airbnb.example/calendar.ics' => Http::response($this->fixture('booking-com.ics'))]);
        $provider = ExternalCalendarProvider::factory()->create(['key' => 'airbnb', 'url' => 'https://airbnb.example/calendar.ics']);
        ExternalCalendarEvent::factory()->create(['external_calendar_provider_id' => $provider->id, 'external_uid' => 'obsolete']);

        $syncService = app(ExternalCalendarSyncService::class);
        $firstResult = $syncService->syncProvider($provider);
        $secondResult = $syncService->syncProvider($provider->fresh());

        $this->assertSame('success', $firstResult['status']);
        $this->assertSame('success', $secondResult['status']);
        $this->assertDatabaseCount('external_calendar_events', 2);
        $event = ExternalCalendarEvent::query()
            ->where('external_calendar_provider_id', $provider->id)
            ->where('external_uid', 'booking-com-all-day-1')
            ->firstOrFail();
        $this->assertSame('2026-09-10', $event->start_date->toDateString());
        $this->assertSame('2026-09-14', $event->end_date->toDateString());
        $this->assertDatabaseHas('external_calendar_providers', ['id' => $provider->id, 'sync_status' => 'success', 'imported_event_count' => 2]);
    }

    public function test_valid_empty_calendar_clears_existing_events(): void
    {
        Http::fake(['https://booking.example/calendar.ics' => Http::response("BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR\r\n")]);
        $provider = ExternalCalendarProvider::factory()->create(['key' => 'booking', 'url' => 'https://booking.example/calendar.ics']);
        ExternalCalendarEvent::factory()->create(['external_calendar_provider_id' => $provider->id]);

        $result = app(ExternalCalendarSyncService::class)->syncProvider($provider);

        $this->assertSame('success', $result['status']);
        $this->assertDatabaseCount('external_calendar_events', 0);
        $this->assertDatabaseHas('external_calendar_providers', ['id' => $provider->id, 'imported_event_count' => 0]);
    }

    public function test_failed_sync_preserves_events_and_does_not_stop_other_providers(): void
    {
        Http::fake([
            'https://airbnb.example/calendar.ics' => Http::response('Unavailable', 503),
            'https://google.example/calendar.ics' => Http::response($this->fixture('google-calendar.ics')),
        ]);
        $failedProvider = ExternalCalendarProvider::factory()->create(['key' => 'airbnb', 'url' => 'https://airbnb.example/calendar.ics']);
        $successfulProvider = ExternalCalendarProvider::factory()->create(['key' => 'google_calendar', 'url' => 'https://google.example/calendar.ics']);
        ExternalCalendarEvent::factory()->create(['external_calendar_provider_id' => $failedProvider->id, 'external_uid' => 'trusted-event']);

        $results = app(ExternalCalendarSyncService::class)->syncEnabledProviders();

        $this->assertSame(['airbnb', 'google_calendar'], array_column($results, 'provider'));
        $this->assertSame(['error', 'success'], array_column($results, 'status'));
        $this->assertDatabaseHas('external_calendar_events', ['external_calendar_provider_id' => $failedProvider->id, 'external_uid' => 'trusted-event']);
        $this->assertDatabaseCount('external_calendar_events', 3);
        $this->assertDatabaseHas('external_calendar_providers', ['id' => $failedProvider->id, 'sync_status' => 'error']);
        $this->assertDatabaseHas('external_calendar_providers', ['id' => $successfulProvider->id, 'sync_status' => 'success', 'imported_event_count' => 2]);
    }

    public function test_malformed_events_preserve_previously_synchronized_data(): void
    {
        Http::fake(['https://hometogo.example/calendar.ics' => Http::response(<<<'ICAL'
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:valid-event
DTSTART;VALUE=DATE:20260910
DTEND;VALUE=DATE:20260912
END:VEVENT
BEGIN:VEVENT
DTSTART;VALUE=DATE:20260913
DTEND;VALUE=DATE:20260915
END:VEVENT
END:VCALENDAR
ICAL)]);
        $provider = ExternalCalendarProvider::factory()->create(['key' => 'hometogo', 'url' => 'https://hometogo.example/calendar.ics']);
        ExternalCalendarEvent::factory()->create(['external_calendar_provider_id' => $provider->id, 'external_uid' => 'trusted-event']);

        $result = app(ExternalCalendarSyncService::class)->syncProvider($provider);

        $this->assertSame('error', $result['status']);
        $this->assertDatabaseCount('external_calendar_events', 1);
        $this->assertDatabaseHas('external_calendar_events', ['external_calendar_provider_id' => $provider->id, 'external_uid' => 'trusted-event']);
    }

    public function test_command_synchronizes_one_selected_provider(): void
    {
        Http::fake([
            'https://airbnb.example/calendar.ics' => Http::response($this->fixture('booking-com.ics')),
            'https://booking.example/calendar.ics' => Http::response($this->fixture('booking-com.ics')),
        ]);
        ExternalCalendarProvider::factory()->create(['key' => 'airbnb', 'url' => 'https://airbnb.example/calendar.ics']);
        $otherProvider = ExternalCalendarProvider::factory()->create(['key' => 'booking', 'url' => 'https://booking.example/calendar.ics']);

        $this->artisan('calendar:sync-external', ['--provider' => 'airbnb'])
            ->expectsOutputToContain('airbnb: success (2 events)')
            ->assertExitCode(0);

        Http::assertSentCount(1);
        $this->assertDatabaseCount('external_calendar_events', 2);
        $this->assertDatabaseHas('external_calendar_providers', ['id' => $otherProvider->id, 'sync_status' => 'success', 'imported_event_count' => 0]);
    }

    private function fixture(string $name): string
    {
        return (string) file_get_contents(base_path("tests/Fixtures/Calendars/{$name}"));
    }
}
