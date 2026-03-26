<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Parses raw booking email text and extracts structured field data.
 * Supports Interhome, Airbnb, Booking.com and generic email formats.
 * Recognises dates in Italian, English, French and German.
 */
class BookingEmailParser
{
    /** Month names in IT / EN / FR / DE → numeric month */
    private const MONTHS = [
        // EN
        'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
        'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
        'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
        // IT
        'gennaio' => 1, 'febbraio' => 2, 'marzo' => 3, 'aprile' => 4,
        'maggio' => 5, 'giugno' => 6, 'luglio' => 7, 'agosto' => 8,
        'settembre' => 9, 'ottobre' => 10, 'novembre' => 11, 'dicembre' => 12,
        // FR
        'janvier' => 1, 'février' => 2, 'mars' => 3, 'avril' => 4,
        'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8,
        'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12,
        // DE
        'januar' => 1, 'februar' => 2, 'märz' => 3,
        'juni' => 6, 'juli' => 7,
        'oktober' => 10, 'dezember' => 12,
    ];

    public function parse(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $dates  = $this->extractDates($text);
        $name   = $this->extractName($text);
        $guests = $this->extractGuests($text);

        return [
            'source'       => $this->detectSource($text),
            'external_ref' => $this->extractReference($text),
            'checkin'      => $dates['checkin'],
            'checkout'     => $dates['checkout'],
            'first_name'   => $name['first_name'],
            'last_name'    => $name['last_name'],
            'adults'       => $guests['adults'],
            'children'     => $guests['children'],
            'email'        => $this->extractEmail($text),
            'phone'        => $this->extractPhone($text),
        ];
    }

    // ── Source detection ─────────────────────────────────────────────────────

    private function detectSource(string $text): string
    {
        $lower = mb_strtolower($text);
        if (str_contains($lower, 'interhome'))  return 'interhome';
        if (str_contains($lower, 'airbnb'))     return 'airbnb';
        if (str_contains($lower, 'booking.com')) return 'booking';
        return 'direct';
    }

    // ── Reference code ───────────────────────────────────────────────────────

    private function extractReference(string $text): string
    {
        // Interhome: IT1850.726.1-XXXXXXXX
        if (preg_match('/\b([A-Z]{2}\d{4}\.\d+\.\d+-[A-Z0-9]+)\b/', $text, $m)) {
            return $m[1];
        }
        // Airbnb confirmation codes: HMXXXXXXXXXX
        if (preg_match('/\bHM[A-Z0-9]{6,12}\b/', $text, $m)) {
            return $m[0];
        }
        // Booking.com / generic reservation number label
        if (preg_match('/(?:reservation|prenotazione|réservation|buchungs)(?:\s*(?:no|nr|n°|#|number|numero))?\s*[:\.]?\s*([A-Z0-9\-]{5,20})/iu', $text, $m)) {
            return trim($m[1]);
        }
        // Generic code / reference label
        if (preg_match('/(?:codice|conferma|code|ref|rif)\.?\s*[:\.]?\s*([A-Z0-9\-]{6,20})/iu', $text, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    // ── Date extraction ──────────────────────────────────────────────────────

    private function extractDates(string $text): array
    {
        $dates = [];

        // ISO: yyyy-mm-dd
        preg_match_all('/\b(\d{4})-(\d{2})-(\d{2})\b/', $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $dates[] = "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        // dd/mm/yyyy or dd.mm.yyyy or dd-mm-yyyy
        preg_match_all('/\b(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})\b/', $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $dates[] = sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        // Written month: "1 aprile 2026" / "1er avril 2026"
        $monthPat = implode('|', array_keys(self::MONTHS));
        preg_match_all('/(\d{1,2})[°ºer\.]*\s+(' . $monthPat . ')\s+(\d{4})/iu', $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $mon = self::MONTHS[mb_strtolower($m[2])] ?? null;
            if ($mon) {
                $dates[] = sprintf('%04d-%02d-%02d', (int) $m[3], $mon, (int) $m[1]);
            }
        }

        // Written month: "April 1, 2026"
        preg_match_all('/(' . $monthPat . ')\s+(\d{1,2}),?\s+(\d{4})/iu', $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $mon = self::MONTHS[mb_strtolower($m[1])] ?? null;
            if ($mon) {
                $dates[] = sprintf('%04d-%02d-%02d', (int) $m[3], $mon, (int) $m[2]);
            }
        }

        // Deduplicate, validate range, sort
        $dates = array_values(array_unique(array_filter(
            $dates,
            fn ($d) => $d >= '2020-01-01' && $d <= '2040-12-31',
        )));
        sort($dates);

        return ['checkin' => $dates[0] ?? null, 'checkout' => $dates[1] ?? null];
    }

    // ── Guest name ───────────────────────────────────────────────────────────

    private function extractName(string $text): array
    {
        $patterns = [
            // Label before name: "Ospite: Mario Rossi"
            '/(?:ospite|cliente|titolare|booking name|guest name?|guest|name|nome|prénom et nom|gast)\s*[:\-]\s*([A-ZÀ-Ö][a-zA-ZÀ-ÿ\'\-]+ [A-ZÀ-Ö][a-zA-ZÀ-ÿ\'\-]+)/iu',
            // Greeting: "Caro Mario Rossi," / "Dear John Smith,"
            '/(?:caro|cara|dear|bonjour|lieber?|gentile)\s+([A-ZÀ-Ö][a-zA-ZÀ-ÿ\'\-]+ [A-ZÀ-Ö][a-zA-ZÀ-ÿ\'\-]+)/iu',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return $this->splitName(trim($m[1]));
            }
        }
        return ['first_name' => '', 'last_name' => ''];
    }

    private function splitName(string $full): array
    {
        $parts = explode(' ', trim($full), 2);
        return ['first_name' => $parts[0] ?? '', 'last_name' => $parts[1] ?? ''];
    }

    // ── Guests count ─────────────────────────────────────────────────────────

    private function extractGuests(string $text): array
    {
        $adults = $children = null;

        if (preg_match('/(?:adulti|adults|erwachsene|adultes)\s*[:\-]?\s*(\d+)/iu', $text, $m)) {
            $adults = (int) $m[1];
        }
        if (preg_match('/(?:bambini|children|kinder|enfants)\s*[:\-]?\s*(\d+)/iu', $text, $m)) {
            $children = (int) $m[1];
        }
        // Fallback: total guests mentioned inline
        if ($adults === null && preg_match('/(\d+)\s*(?:ospiti|guests?|persone|Gäste|personnes)/iu', $text, $m)) {
            $adults = (int) $m[1];
        }

        return ['adults' => $adults ?? 2, 'children' => $children ?? 0];
    }

    // ── Email & phone ────────────────────────────────────────────────────────

    private function extractEmail(string $text): string
    {
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $m)) {
            return $m[0];
        }
        return '';
    }

    private function extractPhone(string $text): string
    {
        // International format: +39 333 1234567
        if (preg_match('/\+\d{1,3}[\s\-]?(?:\d[\s\-]?){8,14}/', $text, $m)) {
            return preg_replace('/\s+/', ' ', trim($m[0]));
        }
        // Labelled local number
        if (preg_match('/(?:tel|phone|telefono|fon|tél)\.?\s*[:\-]?\s*([\d\s\(\)\-\+]{8,20})/iu', $text, $m)) {
            return trim($m[1]);
        }
        return '';
    }
}
