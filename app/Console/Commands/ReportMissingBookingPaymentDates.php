<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class ReportMissingBookingPaymentDates extends Command
{
    protected $signature = 'bookings:missing-payment-dates';

    protected $description = 'Report booking payment statuses that have no date without changing data.';

    public function handle(): int
    {
        $rows = [];
        foreach (Booking::withTrashed()->orderBy('id')->get() as $booking) {
            $missing = [];
            foreach ([
                'income' => ['income_paid', 'income_paid_at'],
                'cleaning' => ['cleaning_paid', 'services_paid_at'],
                'linen' => ['linen_paid', 'services_paid_at'],
                'parking' => ['parking_paid', 'parking_paid_at'],
            ] as $label => [$status, $date]) {
                if ($booking->$status && $booking->$date === null) {
                    $missing[] = $label;
                }
            }
            if ($missing !== []) {
                $rows[] = [$booking->id, implode(', ', $missing)];
            }
        }

        $this->table(['Booking ID', 'Missing dates'], $rows);
        $this->info(count($rows).' booking(s) with missing payment dates.');

        return self::SUCCESS;
    }
}
