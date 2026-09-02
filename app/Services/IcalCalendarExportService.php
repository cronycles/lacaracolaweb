<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingRequest;
use Carbon\CarbonInterface;
use DateTimeZone;
use Sabre\VObject\Component\VCalendar;

class IcalCalendarExportService
{
    public function generate(): string
    {
        $calendar = new VCalendar;
        $calendar->VERSION = '2.0';

        Booking::query()
            ->whereNull('canceled_at')
            ->each(fn (Booking $booking) => $this->addBlockedEvent($calendar, 'booking', $booking->id, $booking->checkin, $booking->checkout));

        BookingRequest::pending()
            ->each(fn (BookingRequest $request) => $this->addBlockedEvent($calendar, 'request', $request->id, $request->checkin, $request->checkout));

        AvailabilityBlock::query()
            ->whereNull('booking_id')
            ->whereNull('booking_request_id')
            ->whereIn('reason', ['owner', 'maintenance'])
            ->each(fn (AvailabilityBlock $block) => $this->addBlockedEvent($calendar, 'block', $block->id, $block->start_date, $block->end_date));

        return $calendar->serialize();
    }

    private function addBlockedEvent(VCalendar $calendar, string $type, int $id, CarbonInterface $startDate, CarbonInterface $endDate): void
    {
        $event = $calendar->add('VEVENT');
        $event->UID = sprintf('%s-%d@%s', $type, $id, $this->domain());
        $event->DTSTAMP = now('UTC');
        $event->add('DTSTART', $this->dateTimeAt($startDate, (string) config('apartment.booking.checkin_time', '15:00')));
        $event->add('DTEND', $this->dateTimeAt($this->exclusiveEndDate($startDate, $endDate), (string) config('apartment.booking.checkout_time', '10:00')));
        $event->add('SUMMARY', 'Blocked');
        $event->add('STATUS', 'CONFIRMED');
        $event->add('TRANSP', 'OPAQUE');
    }

    private function exclusiveEndDate(CarbonInterface $startDate, CarbonInterface $endDate): CarbonInterface
    {
        return $endDate->greaterThan($startDate) ? $endDate : $startDate->copy()->addDay();
    }

    private function dateTimeAt(CarbonInterface $date, string $time): CarbonInterface
    {
        return $date->copy()
            ->setTimezone(new DateTimeZone((string) config('apartment.calendar.timezone', 'Europe/Rome')))
            ->setTimeFromTimeString($time)
            ->utc();
    }

    private function domain(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: (string) config('apartment.schema.identifier');
    }
}
