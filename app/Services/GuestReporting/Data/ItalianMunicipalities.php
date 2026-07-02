<?php

declare(strict_types=1);

namespace App\Services\GuestReporting\Data;

/**
 * Lookup table: Italian municipality name → 9-digit Alloggiati Web code.
 *
 * Data is loaded lazily from resources/data/AlloggiatiWeb/comuni.csv (11 295 entries).
 * CSV format: Codice,Descrizione,Provincia,DataFineVal
 *   - Codice: 9-digit Alloggiati Web code
 *   - Descrizione: municipality name (uppercase)
 *   - Provincia: 2-char province abbreviation
 *   - DataFineVal: empty = currently valid; non-empty = historical/merged entry
 *
 * Lookup strategy:
 *   1. Normalise input name to lowercase.
 *   2. If multiple entries share the same normalised name, prefer non-expired ones.
 *   3. If a province is given and multiple non-expired entries still match, use it
 *      to pick the exact one.
 */
class ItalianMunicipalities
{
    /**
     * Loaded index: normalised name → list of entries (non-expired first).
     *
     * @var array<string, list<array{code: string, province: string, expired: bool}>>|null
     */
    private static ?array $index = null;

    /**
     * Return a sorted list of all currently-valid municipality names (title-cased).
     * Used by views to render the full comuni datalist and by JS for client-side validation.
     *
     * @return string[]
     */
    public static function allValidNames(): array
    {
        $index = self::loadIndex();
        $names = [];
        foreach ($index as $key => $entries) {
            foreach ($entries as $entry) {
                if (! $entry['expired']) {
                    $names[] = mb_convert_case($key, MB_CASE_TITLE);
                    break;
                }
            }
        }
        sort($names);
        return $names;
    }

    /**
     * Find the 9-digit Alloggiati Web code for an Italian municipality.
     *
     * @param  string      $name      Municipality name (case-insensitive)
     * @param  string|null $province  Optional 2-char province abbreviation for disambiguation
     * @return string|null            9-digit code, or null if not found
     */
    public static function findCode(string $name, ?string $province = null): ?string
    {
        $index = self::loadIndex();
        $key   = mb_strtolower(trim($name));

        $entries = $index[$key] ?? [];
        if (empty($entries)) {
            return null;
        }

        // Try to disambiguate by province when there are multiple matches
        if ($province !== null && count($entries) > 1) {
            $prov = mb_strtoupper(trim($province));
            foreach ($entries as $entry) {
                if ($entry['province'] === $prov) {
                    return $entry['code'];
                }
            }
        }

        // Return first entry (non-expired entries sorted first)
        return $entries[0]['code'];
    }

    /**
     * @return array<string, list<array{code: string, province: string, expired: bool}>>
     */
    private static function loadIndex(): array
    {
        if (self::$index !== null) {
            return self::$index;
        }

        self::$index = [];
        $csvPath = resource_path('data/AlloggiatiWeb/comuni.csv');

        if (! file_exists($csvPath)) {
            return self::$index;
        }

        $fh = fopen($csvPath, 'r');
        if ($fh === false) {
            return self::$index;
        }

        // Skip header row: Codice,Descrizione,Provincia,DataFineVal
        fgetcsv($fh);

        while (($row = fgetcsv($fh)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            [$code, $description, $province] = $row;
            $dataFineVal = $row[3] ?? '';

            $key     = mb_strtolower(trim($description));
            $expired = $dataFineVal !== '';

            self::$index[$key][] = [
                'code'     => $code,
                'province' => mb_strtoupper(trim($province)),
                'expired'  => $expired,
            ];
        }

        fclose($fh);

        // Sort each entry list: non-expired first, then expired
        foreach (self::$index as &$entries) {
            usort($entries, static fn ($a, $b) => ($a['expired'] ? 1 : 0) - ($b['expired'] ? 1 : 0));
        }
        unset($entries);

        return self::$index;
    }
}
