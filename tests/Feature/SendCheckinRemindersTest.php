<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\CheckinReminderMail;
use App\Models\Booking;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendCheckinRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function createBooking(\Carbon\Carbon $checkin, array $overrides = []): Booking
    {
        static $counter = 0;
        $counter++;

        $person = Person::create([
            'first_name' => 'Guest',
            'last_name'  => "Number{$counter}",
            'email'      => "guest{$counter}@example.com",
        ]);

        return Booking::create(array_merge([
            'person_id' => $person->id,
            'checkin'   => $checkin->format('Y-m-d'),
            'checkout'  => $checkin->copy()->addDays(5)->format('Y-m-d'),
            'adults'    => 1,
        ], $overrides));
    }

    public function test_sends_reminder_only_for_noncanceled_incomplete_bookings_matching_lead_time(): void
    {
        Mail::fake();

        $leadDays = (int) config('apartment.checkin.reminder_lead_days', 7);
        $targetCheckin = now()->addDays($leadDays);

        $matching   = $this->createBooking($targetCheckin);
        $completed  = $this->createBooking($targetCheckin, ['checkin_completed_at' => now()]);
        $canceled   = $this->createBooking($targetCheckin, ['canceled_at' => now()]);
        $tooEarly   = $this->createBooking($targetCheckin->copy()->addDays(3));

        $this->artisan('checkin:send-reminders')->assertExitCode(0);

        Mail::assertSent(CheckinReminderMail::class, 1);
        Mail::assertSent(CheckinReminderMail::class, function (CheckinReminderMail $mail) use ($matching) {
            return $mail->booking->is($matching);
        });

        $this->assertNotNull($matching->fresh()->checkin_reminder_sent_at);
    }

    public function test_no_reminder_when_already_completed(): void
    {
        Mail::fake();

        $leadDays = (int) config('apartment.checkin.reminder_lead_days', 7);
        $this->createBooking(now()->addDays($leadDays), ['checkin_completed_at' => now()]);

        $this->artisan('checkin:send-reminders');

        Mail::assertNotSent(CheckinReminderMail::class);
    }

    public function test_no_reminder_for_canceled_bookings(): void
    {
        Mail::fake();

        $leadDays = (int) config('apartment.checkin.reminder_lead_days', 7);
        $this->createBooking(now()->addDays($leadDays), ['canceled_at' => now()]);

        $this->artisan('checkin:send-reminders');

        Mail::assertNotSent(CheckinReminderMail::class);
    }
}
