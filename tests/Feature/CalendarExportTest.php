<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sabre\VObject\Reader;
use Tests\TestCase;

class CalendarExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_requires_the_configured_token(): void
    {
        config(['apartment.calendar.export_token' => 'calendar-secret']);

        $this->get('/api/calendar/export')->assertForbidden();
        $this->get('/api/calendar/export?t=wrong-token')->assertForbidden();
        $this->get('/api/calendar/wrong-token/export.ics')->assertForbidden();
    }

    public function test_export_contains_only_current_local_unavailable_periods_without_personal_data(): void
    {
        config([
            'app.url' => 'https://lacaracolaandora.com',
            'apartment.calendar.export_token' => 'calendar-secret',
            'apartment.calendar.timezone' => 'Europe/Rome',
        ]);

        $activeBooking = $this->createBooking('2026-09-10', '2026-09-14', 'Alice Private', 'alice@example.test');
        $this->createBooking('2026-09-15', '2026-09-17', 'Canceled Guest', 'canceled@example.test', ['canceled_at' => now()]);

        BookingRequest::create($this->requestAttributes('2026-09-18', '2026-09-21', 'Pending Guest', 'pending@example.test'));
        BookingRequest::create($this->requestAttributes('2026-09-22', '2026-09-24', 'Declined Guest', 'declined@example.test', ['declined_at' => now()]));
        $convertedRequest = BookingRequest::create($this->requestAttributes('2026-09-25', '2026-09-27', 'Converted Guest', 'converted@example.test'));
        $this->createBooking('2026-09-25', '2026-09-27', 'Converted Guest', 'converted-booking@example.test', ['booking_request_id' => $convertedRequest->id]);

        AvailabilityBlock::create([
            'start_date' => '2026-09-28',
            'end_date' => '2026-09-28',
            'reason' => 'owner',
            'notes' => 'Private owner note',
        ]);
        AvailabilityBlock::create([
            'start_date' => '2026-09-29',
            'end_date' => '2026-10-02',
            'reason' => 'maintenance',
            'notes' => 'Private maintenance note',
        ]);
        AvailabilityBlock::create([
            'start_date' => '2026-10-03',
            'end_date' => '2026-10-05',
            'reason' => 'booked',
        ]);

        $response = $this->get('/api/calendar/export.ics?t=calendar-secret');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename=calendar.ics');
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('must-revalidate', (string) $response->headers->get('Cache-Control'));

        $this->get('/api/calendar/calendar-secret/export.ics')->assertOk();

        $calendar = Reader::read($response->getContent());
        $events = $calendar->select('VEVENT');

        $this->assertSame('2.0', (string) $calendar->VERSION);
        $this->assertSame('-//La Caracola//External Calendar//EN', (string) $calendar->PRODID);
        $this->assertSame('GREGORIAN', (string) $calendar->CALSCALE);
        $this->assertCount(5, $events);
        $this->assertSame('20260910', (string) $events[0]->DTSTART);
        $this->assertSame('20260914', (string) $events[0]->DTEND);
        $this->assertSame('20260928', (string) $events[3]->DTSTART);
        $this->assertSame('20260929', (string) $events[3]->DTEND);

        foreach ($events as $event) {
            $this->assertSame('Blocked', (string) $event->SUMMARY);
            $this->assertSame('CONFIRMED', (string) $event->STATUS);
            $this->assertSame('OPAQUE', (string) $event->TRANSP);
            $this->assertStringContainsString('@lacaracolaandora.com', (string) $event->UID);
        }

        $response->assertDontSee('Alice Private')
            ->assertDontSee('alice@example.test')
            ->assertDontSee('Private owner note')
            ->assertDontSee('Private maintenance note')
            ->assertDontSee('Canceled Guest')
            ->assertDontSee('Declined Guest')
            ->assertDontSee('Converted Guest');

        $this->assertStringContainsString(sprintf('booking-%d@', $activeBooking->id), $response->getContent());
    }

    /** @param array<string, mixed> $overrides */
    private function createBooking(string $checkin, string $checkout, string $name, string $email, array $overrides = []): Booking
    {
        $person = Person::create([
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1],
            'email' => $email,
        ]);

        return Booking::create(array_merge([
            'person_id' => $person->id,
            'checkin' => $checkin,
            'checkout' => $checkout,
            'adults' => 2,
            'source' => 'airbnb',
            'external_ref' => 'private-reference',
            'notes' => 'Private booking note',
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function requestAttributes(string $checkin, string $checkout, string $name, string $email, array $overrides = []): array
    {
        [$firstName, $lastName] = explode(' ', $name);

        return array_merge([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'checkin' => $checkin,
            'checkout' => $checkout,
            'adults' => 2,
            'terms_accepted_at' => now(),
            'message' => 'Private request message',
        ], $overrides);
    }
}
