<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PricingRule;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Keeps the Easter "bridge" pricing rule aligned with the real (moving) date of Easter
 * Sunday each year. `pricing_rules` windows are otherwise recurring by fixed month/day
 * and cannot track a lunar-calendar holiday on their own — this command computes the
 * real date via the Meeus/Jones/Butcher algorithm and upserts a year-specific override.
 *
 * Run manually or via the yearly schedule (see routes/console.php).
 */
class SyncEasterPricingRule extends Command
{
    protected $signature = 'pricing:sync-easter
        {year? : Target year to compute Easter for (default: next calendar year)}
        {--price= : Price per night in EUR for the Easter window (default: reuse previous Easter rule price, or 105)}
        {--days-before=3 : Days before Easter Sunday the window starts (default 3 = Thursday)}
        {--days-after=2 : Days after Easter Sunday the window ends (default 2 = Tuesday)}';

    protected $description = 'Create/update the Easter pricing rule with the correct dates for a given year.';

    public function handle(): int
    {
        $year = (int) ($this->argument('year') ?? (Carbon::now()->year + 1));

        $easter = $this->computeEasterSunday($year);
        $start = $easter->copy()->subDays((int) $this->option('days-before'));
        $end = $easter->copy()->addDays((int) $this->option('days-after'));

        $existing = PricingRule::query()
            ->where('year', $year)
            ->where('note', 'like', 'Pasqua %')
            ->first();

        $priceEur = $this->option('price') !== null
            ? (int) $this->option('price')
            : $this->resolveDefaultPrice($existing);

        $attributes = [
            'start_month' => (int) $start->format('n'),
            'start_day' => (int) $start->format('j'),
            'end_month' => (int) $end->format('n'),
            'end_day' => (int) $end->format('j'),
            'price_per_night' => $priceEur * 100,
            'note' => "Pasqua {$year} (Domenica {$easter->format('d/m')}) - generata automaticamente",
            'year' => $year,
        ];

        if ($existing) {
            $existing->update($attributes);
            $this->info("Regola Pasqua {$year} aggiornata (#{$existing->id}): {$start->format('d/m')} - {$end->format('d/m')} a {$priceEur}€/notte.");
        } else {
            $rule = PricingRule::create($attributes);
            $this->info("Regola Pasqua {$year} creata (#{$rule->id}): {$start->format('d/m')} - {$end->format('d/m')} a {$priceEur}€/notte.");
        }

        return self::SUCCESS;
    }

    private function resolveDefaultPrice(?PricingRule $existing): int
    {
        if ($existing) {
            return (int) $existing->price_euros;
        }

        $previous = PricingRule::query()
            ->where('note', 'like', 'Pasqua %')
            ->orderByDesc('year')
            ->first();

        return $previous ? (int) $previous->price_euros : 105;
    }

    /** Meeus/Jones/Butcher Gregorian algorithm for the date of Easter Sunday. */
    private function computeEasterSunday(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day)->startOfDay();
    }
}
