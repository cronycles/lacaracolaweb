<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\CheckinReminderMail;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCheckinReminders extends Command
{
    protected $signature = 'checkin:send-reminders';

    protected $description = 'Send a reminder email to guests who have not completed the online check-in, a configurable number of days before check-in.';

    public function handle(): int
    {
        $leadDays = (int) config('apartment.checkin.reminder_lead_days', 7);

        if ($leadDays <= 0) {
            $this->line('Check-in reminders disabled (reminder_lead_days = 0).');

            return self::SUCCESS;
        }

        $targetDate = Carbon::today()->addDays($leadDays)->toDateString();

        $bookings = Booking::with('person')
            ->whereNull('canceled_at')
            ->whereNull('deleted_at')
            ->whereNull('checkin_completed_at')
            ->where('checkin_reminder_enabled', true)
            ->whereDate('checkin', $targetDate)
            ->get();

        foreach ($bookings as $booking) {
            $email = $booking->person?->email;

            if (empty($email)) {
                $this->line("Skipped booking #{$booking->id}: primary guest has no email.");

                continue;
            }

            try {
                Mail::to($email)->send(new CheckinReminderMail($booking));
                $booking->update(['checkin_reminder_sent_at' => now()]);
                $this->line("Check-in reminder sent for booking #{$booking->id}");
            } catch (\Throwable $e) {
                Log::error('CheckinReminderMail failed to send', [
                    'error'      => $e->getMessage(),
                    'booking_id' => $booking->id,
                ]);
            }
        }

        return self::SUCCESS;
    }
}
