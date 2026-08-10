<?php

declare(strict_types=1);

namespace App\Services\GuestReporting;

/**
 * Shared `tipo_alloggiato` (AlloggiatiWeb guest classification) auto-defaulting
 * logic, used by both the admin guest-reporting form and the public check-in
 * form so the two never drift apart.
 */
class GuestClassifier
{
    /**
     * Return the AlloggiatiWeb type for each included row, preserving its index.
     * The first included row is always the head of the group, or a single guest.
     *
     * @param array<int, int|string> $includedIndexes
     * @return array<int, string>
     */
    public static function typesForIncludedIndexes(array $includedIndexes): array
    {
        $types = [];
        $totalGuests = count($includedIndexes);

        foreach (array_values($includedIndexes) as $position => $index) {
            $types[(int) $index] = self::defaultTipoFor($position, $totalGuests);
        }

        return $types;
    }

    /**
     * @param int $index       Zero-based position of the guest within the booking's guest
     *                          list (0 = primary guest / capogruppo).
     * @param int $totalGuests Total number of guests being reported for the booking.
     * @return string One of '16' (ospite singolo), '18' (capo gruppo), '20' (membro gruppo).
     */
    public static function defaultTipoFor(int $index, int $totalGuests): string
    {
        if ($totalGuests <= 1) {
            return '16';
        }

        return $index === 0 ? '18' : '20';
    }

    /** Whether the given tipo_alloggiato code requires document data (16/17/18). */
    public static function requiresDocument(string $tipoAlloggiato): bool
    {
        return in_array($tipoAlloggiato, ['16', '17', '18'], true);
    }
}
