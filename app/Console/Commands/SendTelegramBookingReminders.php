<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTelegramBookingReminders extends Command
{
    protected $signature = 'telegram:send-reminders';

    protected $description = 'Send Telegram arrival and departure reminders to configured recipients.';

    public function handle(TelegramService $telegram): int
    {
        $checkinLeadDays  = (int) config('telegram.checkin_lead_days', 1);
        $checkoutLeadDays = (int) config('telegram.checkout_lead_days', 1);

        $checkinDate  = Carbon::today()->addDays($checkinLeadDays)->toDateString();
        $checkoutDate = Carbon::today()->addDays($checkoutLeadDays)->toDateString();

        // Arrival reminders
        if ($checkinLeadDays > 0) {
            $arrivals = Booking::with('person')
                ->whereNull('canceled_at')
                ->whereNull('deleted_at')
                ->whereDate('checkin', $checkinDate)
                ->get();

            foreach ($arrivals as $booking) {
                $text = $this->buildArrivalMessage($booking);
                $telegram->sendToAllRecipients($text);
                $this->line("Arrival reminder sent for booking #{$booking->id}");
            }
        } else {
            $this->line('Arrival reminders disabled (checkin_lead_days = 0).');
        }

        // Departure reminders
        if ($checkoutLeadDays > 0) {
            $departures = Booking::with('person')
                ->whereNull('canceled_at')
                ->whereNull('deleted_at')
                ->whereDate('checkout', $checkoutDate)
                ->get();

            foreach ($departures as $booking) {
                $text = $this->buildDepartureMessage($booking);
                $telegram->sendToAllRecipients($text);
                $this->line("Departure reminder sent for booking #{$booking->id}");
            }
        } else {
            $this->line('Departure reminders disabled (checkout_lead_days = 0).');
        }

        return self::SUCCESS;
    }

    private function buildArrivalMessage(Booking $booking): string
    {
        $person = $booking->person;

        $lines   = [];
        $lines[] = "\u{1F514} Arrivo domani \u{2014} {$person->full_name}";

        if (! empty($person->phone)) {
            $lines[] = "\u{1F4DE} {$person->phone}";
        }

        $lines[] = "\u{1F4C5} Check-in: {$booking->checkin->format('d/m/Y')}";

        $guests = "\u{1F465} Adulti: {$booking->adults}";
        if ($booking->children) {
            $guests .= "  Bambini: {$booking->children}";
        }
        if ($booking->pets) {
            $guests .= "  Animali: {$booking->pets}";
        }
        $lines[] = $guests;

        return implode("\n", $lines);
    }

    private function buildDepartureMessage(Booking $booking): string
    {
        $person = $booking->person;

        $lines   = [];
        $lines[] = "\u{1F514} Partenza domani \u{2014} {$person->full_name}";

        if (! empty($person->phone)) {
            $lines[] = "\u{1F4DE} {$person->phone}";
        }

        $lines[] = "\u{1F4C5} Check-out: {$booking->checkout->format('d/m/Y')}";

        return implode("\n", $lines);
    }
}
