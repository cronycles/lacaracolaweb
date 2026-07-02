<?php

declare(strict_types=1);

namespace App\Services\GuestReporting\Data;

use App\Models\Municipality;

/**
 * Lookup helper: Italian municipality name → 9-digit Alloggiati Web code.
 *
 * Data lives in the `municipalities` DB table (seeded from comuni.csv, 11 295 entries).
 * Lookup strategy:
 *   1. Normalise input name to lowercase for a case-insensitive match.
 *   2. Prefer non-expired entries (expires_at IS NULL).
 *   3. If a province is supplied and multiple matches exist, use it to disambiguate.
 */
class ItalianMunicipalities
{
    /**
     * Return a sorted list of all currently-valid municipality names (title-cased).
     * Used by views to render the full comuni datalist and by JS for client-side validation.
     *
     * @return string[]
     */
    public static function allValidNames(): array
    {
        return Municipality::whereNull('expires_at')
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (string $n) => mb_convert_case($n, MB_CASE_TITLE, 'UTF-8'))
            ->all();
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
        $normalised = mb_strtolower(trim($name));

        $query = Municipality::whereRaw('LOWER(name) = ?', [$normalised]);

        // With province: try exact match first
        if ($province !== null) {
            $match = (clone $query)
                ->where('province', mb_strtoupper(trim($province)))
                ->orderByRaw('CASE WHEN expires_at IS NULL THEN 0 ELSE 1 END')
                ->value('code');

            if ($match !== null) {
                return $match;
            }
        }

        // Prefer non-expired, then fall back to any (historical)
        return $query
            ->orderByRaw('CASE WHEN expires_at IS NULL THEN 0 ELSE 1 END')
            ->value('code');
    }
}
