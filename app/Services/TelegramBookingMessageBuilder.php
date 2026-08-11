<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;

class TelegramBookingMessageBuilder
{
    public function buildBookingSummary(Booking $booking): string
    {
        $booking->loadMissing('person', 'additionalGuests');
        $person = $booking->person;

        $lines = [];
        $lines[] = "\u{1F3E0} Prenotazione \u{2014} {$person->full_name}";

        if (! empty($person->phone)) {
            $lines[] = "\u{1F4DE} {$person->phone}";
        }

        $lines[] = "\u{1F4C5} Check-in: {$booking->checkin->format('d/m/Y')}  Check-out: {$booking->checkout->format('d/m/Y')}";

        $guests = "\u{1F465} Adulti: {$booking->adults}";
        if ($booking->children) {
            $guests .= "  Bambini: {$booking->children}";
        }
        if ($booking->pets) {
            $guests .= "  Animali: {$booking->pets}";
        }
        $lines[] = $guests;

        if (! empty($booking->notes)) {
            $lines[] = "\u{1F4DD} Note: {$booking->notes}";
        }

        $lines[] = '';
        $lines[] = $this->buildGuestDetails($booking);

        return implode("\n", $lines);
    }

    public function buildGuestDetails(Booking $booking): string
    {
        $booking->loadMissing('person', 'additionalGuests');
        $guests = $booking->allGuests()->values();
        $completed = $booking->checkin_completed_at !== null
            && $guests->count() >= $booking->total_guests;

        $lines = [
            $completed
                ? "\u{1F464} Dati ospiti (check-in online completato)"
                : "\u{1F464} Dati ospiti (check-in online non completato: dati eventualmente incompleti)",
        ];

        foreach ($guests as $index => $guest) {
            $lines[] = '';
            $lines[] = sprintf('Ospite %d: %s', $index + 1, $guest->full_name);
            $lines[] = '  Sesso: '.($guest->gender ?: '—');
            $lines[] = '  Data di nascita: '.$this->formatDate($guest->birth_date);
            $lines[] = '  Nazionalità: '.($guest->nationality_code ?: '—');
            $lines[] = '  Paese di nascita: '.($guest->birth_country_code ?: '—');
            $lines[] = '  Luogo di nascita: '.($guest->birth_municipality ?: '—');

            if ($guest->birth_province) {
                $lines[] = '  Provincia di nascita: '.$guest->birth_province;
            }

            $lines[] = '  Tipo documento: '.($this->documentTypeLabel($guest->document_type));
            $lines[] = '  Numero documento: '.($guest->document_number ?: '—');
            $lines[] = '  Paese emissione: '.($guest->document_issue_country_code ?: '—');

            if ($guest->document_issue_place) {
                $lines[] = '  Luogo emissione: '.$guest->document_issue_place;
            }
        }

        return implode("\n", $lines);
    }

    private function formatDate(?\DateTimeInterface $date): string
    {
        return $date?->format('d/m/Y') ?? '—';
    }

    private function documentTypeLabel(?string $type): string
    {
        return match ($type) {
            'passport' => 'Passaporto',
            'id_card' => 'Carta d’identità',
            'driving_license' => 'Patente di guida',
            'residence_permit' => 'Permesso di soggiorno',
            'other' => 'Altro',
            default => '—',
        };
    }
}
