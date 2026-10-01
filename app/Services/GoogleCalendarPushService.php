<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingRequest;
use Carbon\CarbonInterface;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarService;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventExtendedProperties;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

/**
 * Pushes the same "blocked" periods used by IcalCalendarExportService into a Google Calendar,
 * whose own iCal feed is accepted by OTAs (Booking.com, etc.) that reject self-hosted iCal URLs.
 */
class GoogleCalendarPushService
{
    private const MANAGED_PROPERTY = 'managed_by';

    private const MANAGED_VALUE = 'lacaracolaweb';

    /** @return array{synced: int, created: int, updated: int, deleted: int} */
    public function sync(): array
    {
        $calendarId = (string) config('apartment.google_calendar_push.calendar_id', '');
        if ($calendarId === '') {
            throw new RuntimeException('apartment.google_calendar_push.calendar_id is not configured.');
        }

        $service = $this->buildClient();

        $desired = $this->desiredEvents();
        $existing = $this->listManagedEvents($service, $calendarId);

        $created = 0;
        $updated = 0;

        foreach ($desired as $uid => $event) {
            $googleEvent = $this->buildGoogleEvent($uid, $event['start'], $event['end']);

            try {
                $service->events->import($calendarId, $googleEvent);
                $existing->has($uid) ? $updated++ : $created++;
            } catch (Throwable $exception) {
                throw new RuntimeException("Failed to push calendar event {$uid} to Google Calendar: {$exception->getMessage()}", previous: $exception);
            }
        }

        $deleted = 0;
        foreach ($existing as $uid => $googleEventId) {
            if (! isset($desired[$uid])) {
                $service->events->delete($calendarId, $googleEventId);
                $deleted++;
            }
        }

        return [
            'synced' => count($desired),
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
        ];
    }

    /** @return Collection<string, array{start: CarbonInterface, end: CarbonInterface}> */
    private function desiredEvents(): Collection
    {
        $events = collect();

        Booking::query()
            ->whereNull('canceled_at')
            ->each(function (Booking $booking) use ($events): void {
                $events->put(
                    $this->uid('booking', $booking->id),
                    ['start' => $booking->checkin, 'end' => $this->exclusiveEndDate($booking->checkin, $booking->checkout)]
                );
            });

        BookingRequest::pending()
            ->each(function (BookingRequest $request) use ($events): void {
                $events->put(
                    $this->uid('request', $request->id),
                    ['start' => $request->checkin, 'end' => $this->exclusiveEndDate($request->checkin, $request->checkout)]
                );
            });

        AvailabilityBlock::query()
            ->whereNull('booking_id')
            ->whereNull('booking_request_id')
            ->whereIn('reason', ['owner', 'maintenance'])
            ->each(function (AvailabilityBlock $block) use ($events): void {
                $events->put(
                    $this->uid('block', $block->id),
                    ['start' => $block->start_date, 'end' => $this->exclusiveEndDate($block->start_date, $block->end_date)]
                );
            });

        return $events;
    }

    private function uid(string $type, int $id): string
    {
        return sprintf('%s-%d@%s', $type, $id, $this->domain());
    }

    private function exclusiveEndDate(CarbonInterface $startDate, CarbonInterface $endDate): CarbonInterface
    {
        return $endDate->greaterThan($startDate) ? $endDate : $startDate->copy()->addDay();
    }

    private function domain(): string
    {
        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: (string) config('apartment.schema.identifier');
    }

    private function buildGoogleEvent(string $uid, CarbonInterface $start, CarbonInterface $end): GoogleEvent
    {
        $startDate = new EventDateTime;
        $startDate->setDate($start->toDateString());
        $endDate = new EventDateTime;
        $endDate->setDate($end->toDateString());

        $event = new GoogleEvent;
        $event->setICalUID($uid);
        $event->setStart($startDate);
        $event->setEnd($endDate);
        $event->setSummary('Blocked');
        $event->setStatus('confirmed');
        $event->setTransparency('opaque');

        $extendedProperties = new EventExtendedProperties;
        $extendedProperties->setPrivate([self::MANAGED_PROPERTY => self::MANAGED_VALUE]);
        $event->setExtendedProperties($extendedProperties);

        return $event;
    }

    /** @return Collection<string, string> Map of iCalUID => Google event id */
    private function listManagedEvents(GoogleCalendarService $service, string $calendarId): Collection
    {
        $map = collect();
        $pageToken = null;

        do {
            $response = $service->events->listEvents($calendarId, array_filter([
                'privateExtendedProperty' => self::MANAGED_PROPERTY.'='.self::MANAGED_VALUE,
                'singleEvents' => true,
                'maxResults' => 250,
                'pageToken' => $pageToken,
            ]));

            foreach ($response->getItems() as $item) {
                $uid = $item->getICalUID();
                if ($uid !== null && $uid !== '') {
                    $map->put($uid, $item->getId());
                }
            }

            $pageToken = $response->getNextPageToken();
        } while ($pageToken !== null);

        return $map;
    }

    private function buildClient(): GoogleCalendarService
    {
        $credentialsPath = (string) config('apartment.google_calendar_push.credentials_path', '');
        if ($credentialsPath === '' || ! is_readable($credentialsPath)) {
            throw new RuntimeException("Google Calendar credentials file not found or not readable at [{$credentialsPath}].");
        }

        $client = new GoogleClient;
        $client->setAuthConfig($credentialsPath);
        $client->setScopes([GoogleCalendarService::CALENDAR_EVENTS]);
        $client->setApplicationName('La Caracola Calendar Push');

        return new GoogleCalendarService($client);
    }
}
