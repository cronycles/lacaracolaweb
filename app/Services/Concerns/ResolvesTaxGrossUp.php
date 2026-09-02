<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Models\Setting;

/** Shared tax gross-up computation on cleaning/linen costs, used both for the real direct price and the portal nightly rate. */
trait ResolvesTaxGrossUp
{
    private function resolveTaxRate(): float
    {
        return (float) Setting::get('pricing_tax_rate', '0.21');
    }

    /** @return list<string> */
    private function resolveTaxGrossUpItems(): array
    {
        $raw = Setting::get('pricing_tax_gross_up_items', '["cleaning","linen"]');
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? array_values($decoded) : ['cleaning', 'linen'];
    }

    /** Tax gross-up cents on whichever of cleaning/linen are selected in Settings. */
    private function taxGrossUpCents(int $cleaningCents, int $linenCents): int
    {
        $taxGrossUpItems = $this->resolveTaxGrossUpItems();

        $taxableCents = 0;
        if (in_array('cleaning', $taxGrossUpItems, true)) {
            $taxableCents += $cleaningCents;
        }
        if (in_array('linen', $taxGrossUpItems, true)) {
            $taxableCents += $linenCents;
        }

        return (int) round($taxableCents * $this->resolveTaxRate());
    }
}
