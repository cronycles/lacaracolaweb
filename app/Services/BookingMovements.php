<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Collection;

class BookingMovements
{
    public function all(bool $taxOnly = false): Collection
    {
        $movements = collect();

        foreach (Booking::whereNull('canceled_at')->with(['person', 'attachments'])->get() as $booking) {
            $description = ($booking->person?->full_name ?? 'Prenotazione #'.$booking->id)
                .' ('.$booking->checkin->format('d/m').'–'.$booking->checkout->format('d/m/Y').')';

            foreach ([
                ['income', 'income', 'Prenotazione', $booking->income_paid, $booking->income_paid_at, $booking->income_amount, $booking->income_tax],
                ['parking', 'income', 'Posto auto', ! $booking->income_includes_parking && $booking->parking_paid, $booking->parking_paid_at, $booking->parking_amount, $booking->parking_tax],
                ['cleaning', 'expense', 'Pulizie', $booking->cleaning_paid, $booking->services_paid_at, $booking->cleaning_amount, $booking->cleaning_tax],
                ['linen', 'expense', 'Biancheria', $booking->linen_paid, $booking->services_paid_at, $booking->linen_amount, $booking->linen_tax],
            ] as [$name, $type, $label, $paid, $date, $amount, $tax]) {
                if (! $paid || $date === null || $amount === null || ($taxOnly && ! $tax)) {
                    continue;
                }

                $movements->push([
                    'date' => $date,
                    'type' => $type,
                    'category_label' => $label,
                    'description' => $description,
                    'amount' => (float) $amount,
                    'included' => true,
                    'source' => 'booking_'.$name,
                    'entry' => null,
                    'booking_id' => $booking->id,
                    'model_type' => 'booking',
                    'model_id' => $booking->id,
                    'attachments' => $booking->attachments,
                ]);
            }
        }

        return $movements;
    }
}
