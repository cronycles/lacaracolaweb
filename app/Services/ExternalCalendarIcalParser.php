<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Property\ICalendar\DateTime as IcalDateTime;
use Sabre\VObject\Reader;

class ExternalCalendarIcalParser
{
    /**
     * @return array<int, array{external_uid: string, start_date: string, end_date: string}>
     */
    public function parse(string $calendarBody): array
    {
        try {
            $calendar = Reader::read($calendarBody);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('The iCalendar feed could not be parsed.', previous: $exception);
        }

        if (! $calendar instanceof VCalendar) {
            throw new InvalidArgumentException('The feed is not an iCalendar VCALENDAR document.');
        }

        $events = [];
        $externalUids = [];
        foreach ($calendar->select('VEVENT') as $event) {
            if ($this->shouldIgnore($event)) {
                continue;
            }

            $externalUid = trim((string) ($event->UID ?? ''));
            if ($externalUid === '') {
                throw new InvalidArgumentException('An iCalendar event is missing its UID.');
            }
            if (isset($externalUids[$externalUid])) {
                throw new InvalidArgumentException("The iCalendar event UID {$externalUid} is duplicated.");
            }

            $externalUids[$externalUid] = true;
            if (! isset($event->DTSTART, $event->DTEND) || ! $event->DTSTART instanceof IcalDateTime || ! $event->DTEND instanceof IcalDateTime) {
                throw new InvalidArgumentException("The iCalendar event {$externalUid} requires DTSTART and DTEND values.");
            }

            $events[] = $this->normalizeEvent($externalUid, $event->DTSTART, $event->DTEND);
        }

        return $events;
    }

    private function shouldIgnore(object $event): bool
    {
        return strtoupper((string) ($event->STATUS ?? '')) === 'CANCELLED'
            || strtoupper((string) ($event->TRANSP ?? '')) === 'TRANSPARENT';
    }

    /** @return array{external_uid: string, start_date: string, end_date: string} */
    private function normalizeEvent(string $externalUid, IcalDateTime $start, IcalDateTime $end): array
    {
        $timezone = new DateTimeZone((string) config('apartment.calendar.timezone', 'Europe/Rome'));
        $startDateTime = $start->getDateTime($timezone);
        $endDateTime = $end->getDateTime($timezone);

        if (! $startDateTime instanceof DateTimeInterface || ! $endDateTime instanceof DateTimeInterface || $endDateTime <= $startDateTime) {
            throw new InvalidArgumentException("The iCalendar event {$externalUid} has an invalid date range.");
        }

        $localStart = CarbonImmutable::instance($startDateTime)->setTimezone($timezone)->startOfDay();
        $localEnd = CarbonImmutable::instance($endDateTime)->setTimezone($timezone)->startOfDay();

        if ($localStart->equalTo($localEnd)) {
            if (! $start->hasTime() || ! $end->hasTime()) {
                throw new InvalidArgumentException("The all-day iCalendar event {$externalUid} has an invalid date range.");
            }

            $localEnd = $localStart->addDay();
        }

        return [
            'external_uid' => $externalUid,
            'start_date' => $localStart->toDateString(),
            'end_date' => $localEnd->toDateString(),
        ];
    }
}
