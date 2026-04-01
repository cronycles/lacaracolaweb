<?php

declare(strict_types=1);

namespace App\Services;

use Smalot\PdfParser\Parser;

/**
 * Parses Interhome booking PDF text into normalized booking rows.
 */
class InterhomePdfBookingParser
{
    /**
     * @return array{rows: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function parseFile(string $pdfPath): array
    {
        $parser = new Parser();
        $text = $parser->parseFile($pdfPath)->getText();

        return $this->parseText($text);
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    private function parseText(string $text): array
    {
        $normalizedText = $this->normalizeText($text);
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\n/', $normalizedText) ?: []),
            static fn (string $line): bool => $line !== '',
        ));

        $start = $this->findFirstBookingLineIndex($lines);
        if ($start === null) {
            return [
                'rows' => [],
                'warnings' => ['Nessuna riga prenotazione riconosciuta nel PDF.'],
            ];
        }

        $rows = [];
        $warnings = [];

        $index = $start;
        while ($index < count($lines)) {
            if (!$this->isDateLine($lines[$index]) || !isset($lines[$index + 1]) || !$this->isDateLine($lines[$index + 1])) {
                $index++;
                continue;
            }

            $row = $this->parseBookingFromLines($lines, $index);
            if ($row === null) {
                $index++;
                continue;
            }

            if (!empty($row['warning'])) {
                $warnings[] = 'Riga check-in ' . $row['checkin'] . ': ' . $row['warning'];
            }

            unset($row['warning']);
            $rows[] = $row;
        }

        return [
            'rows' => $rows,
            'warnings' => $warnings,
        ];
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\t]+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseBookingFromLines(array $lines, int &$index): ?array
    {
        $checkin = $this->parseDate($lines[$index]);
        $checkout = $this->parseDate($lines[$index + 1]);

        if ($checkin === null || $checkout === null) {
            return null;
        }

        $index += 2;

        if (!isset($lines[$index])) {
            return null;
        }

        $referenceLine = $lines[$index];
        [$externalRef, $inlineGuests] = $this->extractReferenceAndInlineGuests($referenceLine);
        if ($externalRef === '') {
            return null;
        }

        $isOwnerBooking = false;

        $index++;

        $firstName = '';
        $lastName = '';
        $email = '';
        $phone = '';
        $adults = null;
        $children = null;
        $babies = null;
        $pets = null;
        $warning = null;
        $hasGuestNumbers = false;

        if ($inlineGuests !== null) {
            $adults = $inlineGuests['adults'];
            $children = $inlineGuests['children'];
            $babies = $inlineGuests['babies'];
            $pets = $inlineGuests['pets'];
            $hasGuestNumbers = true;
            $warning = 'Prenotazione senza contatti: dati ospite non presenti nel PDF.';
        }

        if ($index < count($lines) && $this->looksLikeNameLine($lines[$index])) {
            [$firstName, $lastName] = $this->splitName($lines[$index]);
            $index++;
        }

        if ($index < count($lines) && str_contains($lines[$index], '@')) {
            $email = $lines[$index];

            if (
                isset($lines[$index + 1])
                && !$this->isValidEmail($email)
                && !str_contains($lines[$index + 1], ' ')
                && !$this->isDateLine($lines[$index + 1])
            ) {
                $email .= $lines[$index + 1];
                $index++;
            }

            $index++;
        }

        if ($index < count($lines) && $this->looksLikePhoneLine($lines[$index])) {
            $phone = $lines[$index];
            $index++;
        }

        if ($index < count($lines)) {
            $countryGuests = $this->extractCountryGuests($lines[$index]);
            if ($countryGuests !== null) {
                $adults = $countryGuests['adults'];
                $children = $countryGuests['children'];
                $babies = $countryGuests['babies'];
                $pets = $countryGuests['pets'];
                $hasGuestNumbers = true;
                $index++;
            }
        }

        $hasContact = ($firstName !== '' || $email !== '' || $phone !== '');
        $allGuestCountsZero = ($adults ?? 0) === 0
            && ($children ?? 0) === 0
            && ($babies ?? 0) === 0
            && ($pets ?? 0) === 0;

        $shouldSkip = false;
        $skipReason = null;
        if (!$hasContact || !$hasGuestNumbers) {
            $shouldSkip = true;
            $reasons = [];
            if (!$hasContact) {
                $reasons[] = 'contatto assente';
            }
            if (!$hasGuestNumbers) {
                $reasons[] = 'colonne Adulto/Bambino/Bebe/Pet senza numeri';
            }
            $skipReason = 'Prenotazione saltata: ' . implode(' e ', $reasons) . '.';
        }

        if ($firstName === '') {
            $firstName = 'Interhome';
            $lastName = $externalRef;
            $warning = trim(($warning ? $warning . ' ' : '') . 'Nome ospite non disponibile: usato fallback.');
        }

        if ($adults === null) {
            $adults = 2;
            $children = $children ?? 0;
            $babies = $babies ?? 0;
            $warning = trim(($warning ? $warning . ' ' : '') . 'Numero ospiti non trovato: usato default 2 adulti.');
        }


        $index--;

        return [
            'source' => 'interhome',
            'external_ref' => $externalRef,
            'checkin' => $checkin,
            'checkout' => $checkout,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'adults' => $adults,
            'children' => $children ?? 0,
            'babies' => $babies ?? 0,
            'pets' => $pets ?? 0,
            'skip_import' => $shouldSkip,
            'skip_reason' => $skipReason,
            'warning' => $warning,
            'raw_block' => '',
        ];
    }

    private function findFirstBookingLineIndex(array $lines): ?int
    {
        foreach ($lines as $i => $line) {
            if ($this->isDateLine($line)) {
                return $i;
            }
        }

        return null;
    }

    private function isDateLine(string $line): bool
    {
        return (bool) preg_match('/^\d{1,2}[\/\.-]\d{1,2}[\/\.-]\d{2,4}$/', trim($line));
    }

    private function parseDate(string $line): ?string
    {
        if (!preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{2,4})$/', trim($line), $match)) {
            return null;
        }

        $year = (int) $match[3];
        if ($year < 100) {
            $year += 2000;
        }

        return sprintf('%04d-%02d-%02d', $year, (int) $match[2], (int) $match[1]);
    }

    /**
    * @return array{0:string,1:array{adults:int,children:int,babies:int,pets:int}|null}
     */
    private function extractReferenceAndInlineGuests(string $line): array
    {
        $reference = '';
        $guests = null;

        if (preg_match('/\b(\d{10,16})\b/', $line, $match)) {
            $reference = $match[1];
        }

        if (preg_match('/(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*$/', trim($line), $match)) {
            $guests = [
                'adults' => (int) $match[1],
                'children' => (int) $match[2],
                'babies' => (int) $match[3],
                'pets' => (int) $match[4],
            ];
        }

        return [$reference, $guests];
    }

    private function looksLikeNameLine(string $line): bool
    {
        if (preg_match('/\d/', $line)) {
            return false;
        }
        if (str_contains($line, '@') || $this->looksLikePhoneLine($line) || $this->isDateLine($line)) {
            return false;
        }

        return (bool) preg_match('/^[\p{L}][\p{L}\s\'\-\.]{2,}$/u', $line);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitName(string $line): array
    {
        $parts = preg_split('/\s+/', trim($line)) ?: [];
        if (count($parts) < 2) {
            return [$line, ''];
        }

        $firstName = array_shift($parts) ?: '';
        $lastName = implode(' ', $parts);

        return [$firstName, $lastName];
    }

    private function looksLikePhoneLine(string $line): bool
    {
        return (bool) preg_match('/^\+?[\d\s\-\(\)]{8,}$/', trim($line));
    }

    private function isValidEmail(string $value): bool
    {
        return (bool) preg_match('/^[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}$/i', trim($value));
    }

    /**
     * @return array{code:string,adults: int, children: int, babies: int,pets:int}|null
     */
    private function extractCountryGuests(string $line): ?array
    {
        if (!preg_match('/^([A-Z]{2})\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)$/', trim($line), $match)) {
            return null;
        }

        return [
            'code' => strtoupper($match[1]),
            'adults' => (int) $match[2],
            'children' => (int) $match[3],
            'babies' => (int) $match[4],
            'pets' => (int) $match[5],
        ];
    }
}
