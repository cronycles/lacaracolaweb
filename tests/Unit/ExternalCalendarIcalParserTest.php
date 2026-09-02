<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ExternalCalendarIcalParser;
use InvalidArgumentException;
use Tests\TestCase;

class ExternalCalendarIcalParserTest extends TestCase
{
    public function test_parses_booking_style_all_day_events_and_folded_lines(): void
    {
        $events = app(ExternalCalendarIcalParser::class)->parse($this->fixture('booking-com.ics'));

        $this->assertSame([
            ['external_uid' => 'booking-com-all-day-1', 'start_date' => '2026-09-10', 'end_date' => '2026-09-14'],
            ['external_uid' => 'booking-com-folded-2', 'start_date' => '2026-09-20', 'end_date' => '2026-09-23'],
        ], $events);
    }

    public function test_normalizes_google_utc_events_and_ignores_canceled_or_transparent_events(): void
    {
        $events = app(ExternalCalendarIcalParser::class)->parse($this->fixture('google-calendar.ics'));

        $this->assertSame([
            ['external_uid' => 'google-utc-1', 'start_date' => '2026-09-11', 'end_date' => '2026-09-13'],
            ['external_uid' => 'google-same-day-2', 'start_date' => '2026-09-20', 'end_date' => '2026-09-21'],
        ], $events);
    }

    public function test_rejects_events_without_required_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing its UID');

        app(ExternalCalendarIcalParser::class)->parse(<<<'ICAL'
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
DTSTART;VALUE=DATE:20260910
DTEND;VALUE=DATE:20260912
END:VEVENT
END:VCALENDAR
ICAL);
    }

    private function fixture(string $name): string
    {
        return (string) file_get_contents(base_path("tests/Fixtures/Calendars/{$name}"));
    }
}
