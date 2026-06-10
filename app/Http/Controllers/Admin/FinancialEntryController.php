<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FinancialEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinancialEntryController extends Controller
{
    public function index(Request $request): View
    {
        $year = (int) $request->input('year', now()->year);

        // ── Year totals (stat cards) ─────────────────────────────────────────

        $bookingIncome = Booking::whereNull('canceled_at')
            ->whereYear(\DB::raw('COALESCE(income_paid_at, checkout)'), $year)
            ->where('income_paid', true)
            ->whereNotNull('income_amount')
            ->sum('income_amount');

        $bookingCleaning = Booking::whereNull('canceled_at')
            ->whereYear(\DB::raw('COALESCE(services_paid_at, checkout)'), $year)
            ->where('cleaning_paid', true)
            ->whereNotNull('cleaning_amount')
            ->sum('cleaning_amount');

        $bookingLinen = Booking::whereNull('canceled_at')
            ->whereYear(\DB::raw('COALESCE(services_paid_at, checkout)'), $year)
            ->where('linen_paid', true)
            ->whereNotNull('linen_amount')
            ->sum('linen_amount');

        $bookingParking = Booking::whereNull('canceled_at')
            ->whereYear(\DB::raw('COALESCE(parking_paid_at, checkout)'), $year)
            ->where('parking_paid', true)
            ->whereNotNull('parking_amount')
            ->sum('parking_amount');

        $bookingExpenses = $bookingCleaning + $bookingLinen;

        $extraIncome = FinancialEntry::where('type', 'income')
            ->whereYear('entry_date', $year)
            ->sum('amount');

        $extraExpenses = FinancialEntry::where('type', 'expense')
            ->whereYear('entry_date', $year)
            ->sum('amount');

        $totals = [
            'income'           => $bookingIncome + $bookingParking + $extraIncome,
            'expenses'         => $bookingExpenses + $extraExpenses,
            'booking_income'   => $bookingIncome,
            'booking_parking'  => $bookingParking,
            'booking_expenses' => $bookingExpenses,
            'extra_income'     => $extraIncome,
            'extra_expenses'   => $extraExpenses,
            'cleaning_paid'    => $bookingCleaning,
            'linen_paid'       => $bookingLinen,
        ];

        $totals['balance'] = $totals['income'] - $totals['expenses'];

        // ── All-time global balance ──────────────────────────────────────────

        $globalBookingIncome = Booking::whereNull('canceled_at')
            ->where('income_paid', true)
            ->whereNotNull('income_amount')
            ->sum('income_amount');

        $globalBookingParking = Booking::whereNull('canceled_at')
            ->where('parking_paid', true)
            ->whereNotNull('parking_amount')
            ->sum('parking_amount');

        $globalBookingExpenses = Booking::whereNull('canceled_at')
            ->selectRaw('COALESCE(SUM(CASE WHEN cleaning_paid = 1 THEN cleaning_amount ELSE 0 END), 0) +
                         COALESCE(SUM(CASE WHEN linen_paid = 1 THEN linen_amount ELSE 0 END), 0) as total')
            ->value('total') ?? 0;

        $globalExtraIncome   = FinancialEntry::where('type', 'income')->sum('amount');
        $globalExtraExpenses = FinancialEntry::where('type', 'expense')->sum('amount');

        $globalBalance = ($globalBookingIncome + $globalBookingParking + $globalExtraIncome) - ($globalBookingExpenses + $globalExtraExpenses);

        // ── Balance at start of selected year (all previous years combined) ──

        $prevBookingIncome = Booking::whereNull('canceled_at')
            ->whereRaw('YEAR(COALESCE(income_paid_at, checkout)) < ?', [$year])
            ->where('income_paid', true)
            ->whereNotNull('income_amount')
            ->sum('income_amount');

        $prevBookingParking = Booking::whereNull('canceled_at')
            ->whereRaw('YEAR(COALESCE(parking_paid_at, checkout)) < ?', [$year])
            ->where('parking_paid', true)
            ->whereNotNull('parking_amount')
            ->sum('parking_amount');

        $prevBookingExpenses = Booking::whereNull('canceled_at')
            ->whereRaw('YEAR(COALESCE(services_paid_at, checkout)) < ?', [$year])
            ->selectRaw('COALESCE(SUM(CASE WHEN cleaning_paid = 1 THEN cleaning_amount ELSE 0 END), 0) +
                         COALESCE(SUM(CASE WHEN linen_paid = 1 THEN linen_amount ELSE 0 END), 0) as total')
            ->value('total') ?? 0;

        $prevExtraIncome   = FinancialEntry::where('type', 'income')->whereRaw('YEAR(entry_date) < ?', [$year])->sum('amount');
        $prevExtraExpenses = FinancialEntry::where('type', 'expense')->whereRaw('YEAR(entry_date) < ?', [$year])->sum('amount');

        $previousBalance = (float) ($prevBookingIncome + $prevBookingParking + $prevExtraIncome) - ($prevBookingExpenses + $prevExtraExpenses);

        // ── Monthly breakdown ────────────────────────────────────────────────

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $bInc = Booking::whereNull('canceled_at')
                ->whereYear(\DB::raw('COALESCE(income_paid_at, checkout)'), $year)
                ->whereMonth(\DB::raw('COALESCE(income_paid_at, checkout)'), $m)
                ->where('income_paid', true)
                ->whereNotNull('income_amount')
                ->sum('income_amount');

            $bPark = Booking::whereNull('canceled_at')
                ->whereYear(\DB::raw('COALESCE(parking_paid_at, checkout)'), $year)
                ->whereMonth(\DB::raw('COALESCE(parking_paid_at, checkout)'), $m)
                ->where('parking_paid', true)
                ->whereNotNull('parking_amount')
                ->sum('parking_amount');

            $bExp = Booking::whereNull('canceled_at')
                ->whereYear(\DB::raw('COALESCE(services_paid_at, checkout)'), $year)
                ->whereMonth(\DB::raw('COALESCE(services_paid_at, checkout)'), $m)
                ->selectRaw('COALESCE(SUM(CASE WHEN cleaning_paid = 1 THEN cleaning_amount ELSE 0 END), 0) +
                             COALESCE(SUM(CASE WHEN linen_paid = 1 THEN linen_amount ELSE 0 END), 0) as total')
                ->value('total') ?? 0;

            $eInc = FinancialEntry::where('type', 'income')
                ->whereYear('entry_date', $year)
                ->whereMonth('entry_date', $m)
                ->sum('amount');

            $eExp = FinancialEntry::where('type', 'expense')
                ->whereYear('entry_date', $year)
                ->whereMonth('entry_date', $m)
                ->sum('amount');

            $monthlyData[$m] = [
                'income'   => $bInc + $bPark + $eInc,
                'expenses' => $bExp + $eExp,
            ];
        }

        // ── Unified movement list for the year ───────────────────────────────

        $movements = collect();

        // 1. Extra financial entries
        foreach (FinancialEntry::whereYear('entry_date', $year)->with('attachments')->get() as $entry) {
            $movements->push([
                'date'           => $entry->entry_date,
                'type'           => $entry->type,
                'category_label' => config('finance.categories')[$entry->category] ?? $entry->category,
                'description'    => $entry->description,
                'amount'         => (float) $entry->amount,
                'source'         => 'entry',
                'entry'          => $entry,
                'booking_id'     => null,
                'model_type'     => 'entry',
                'model_id'       => $entry->id,
                'attachments'    => $entry->attachments,
            ]);
        }

        // 2. Booking income payments
        $bookingIncomeRows = Booking::whereNull('canceled_at')
            ->whereYear(\DB::raw('COALESCE(income_paid_at, checkout)'), $year)
            ->where('income_paid', true)
            ->whereNotNull('income_amount')
            ->with(['person', 'attachments'])
            ->get();

        foreach ($bookingIncomeRows as $booking) {
            $date = $booking->income_paid_at ?? $booking->checkout;
            $desc = ($booking->person?->full_name ?? 'Prenotazione #' . $booking->id)
                . ' (' . $booking->checkin->format('d/m') . '–' . $booking->checkout->format('d/m/Y') . ')';
            $movements->push([
                'date'           => $date,
                'type'           => 'income',
                'category_label' => 'Prenotazione',
                'description'    => $desc,
                'amount'         => (float) $booking->income_amount,
                'source'         => 'booking_income',
                'entry'          => null,
                'booking_id'     => $booking->id,
                'model_type'     => 'booking',
                'model_id'       => $booking->id,
                'attachments'    => $booking->attachments,
            ]);
        }

        // 3. Booking cleaning/linen payments
        $bookingServiceRows = Booking::whereNull('canceled_at')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('cleaning_paid', true)->whereNotNull('cleaning_amount');
                })->orWhere(function ($q2) {
                    $q2->where('linen_paid', true)->whereNotNull('linen_amount');
                });
            })
            ->whereYear(\DB::raw('COALESCE(services_paid_at, checkout)'), $year)
            ->with(['person', 'attachments'])
            ->get();

        // 4. Booking parking payments
        $bookingParkingRows = Booking::whereNull('canceled_at')
            ->where('parking_paid', true)
            ->whereNotNull('parking_amount')
            ->whereYear(\DB::raw('COALESCE(parking_paid_at, checkout)'), $year)
            ->with('person')
            ->get();

        foreach ($bookingParkingRows as $booking) {
            $date       = $booking->parking_paid_at ?? $booking->checkout;
            $bookingRef = ($booking->person?->full_name ?? 'Prenotazione #' . $booking->id)
                . ' (' . $booking->checkin->format('d/m') . '–' . $booking->checkout->format('d/m/Y') . ')';

            $movements->push([
                'date'           => $date,
                'type'           => 'income',
                'category_label' => 'Posto auto',
                'description'    => $bookingRef,
                'amount'         => (float) $booking->parking_amount,
                'source'         => 'booking_parking',
                'entry'          => null,
                'booking_id'     => $booking->id,
            ]);
        }

        foreach ($bookingServiceRows as $booking) {
            $date       = $booking->services_paid_at ?? $booking->checkout;
            $bookingRef = ($booking->person?->full_name ?? 'Prenotazione #' . $booking->id)
                . ' (' . $booking->checkin->format('d/m') . '–' . $booking->checkout->format('d/m/Y') . ')';

            if ($booking->cleaning_paid && $booking->cleaning_amount !== null) {
                $movements->push([
                    'date'           => $date,
                    'type'           => 'expense',
                    'category_label' => 'Pulizie',
                    'description'    => $bookingRef,
                    'amount'         => (float) $booking->cleaning_amount,
                    'source'         => 'booking_cleaning',
                    'entry'          => null,
                    'booking_id'     => $booking->id,
                    'model_type'     => 'booking',
                    'model_id'       => $booking->id,
                    'attachments'    => $booking->attachments,
                ]);
            }

            if ($booking->linen_paid && $booking->linen_amount !== null) {
                $movements->push([
                    'date'           => $date,
                    'type'           => 'expense',
                    'category_label' => 'Biancheria',
                    'description'    => $bookingRef,
                    'amount'         => (float) $booking->linen_amount,
                    'source'         => 'booking_linen',
                    'entry'          => null,
                    'booking_id'     => $booking->id,
                    'model_type'     => 'booking',
                    'model_id'       => $booking->id,
                    'attachments'    => $booking->attachments,
                ]);
            }
        }

        // Sort ASC by date (then by id for stability), compute running balances, then reverse for display
        $movements = $movements->sort(function ($a, $b) {
            $cmp = $a['date']->timestamp <=> $b['date']->timestamp;
            if ($cmp !== 0) {
                return $cmp;
            }

            return ($a['entry']?->id ?? $a['booking_id'] ?? 0) <=> ($b['entry']?->id ?? $b['booking_id'] ?? 0);
        })->values();

        $runningBalance = $previousBalance;
        $movements = $movements->map(function ($m) use (&$runningBalance) {
            if ($m['type'] === 'income') {
                $runningBalance += $m['amount'];
            } else {
                $runningBalance -= $m['amount'];
            }
            $m['running_balance'] = $runningBalance;

            return $m;
        })->reverse()->values();

        $availableYears = $this->availableYears();

        $cleaningUnpaid = Booking::whereNull('canceled_at')
            ->whereNotNull('cleaning_amount')
            ->where('cleaning_paid', false)
            ->sum('cleaning_amount');

        $linenUnpaid = Booking::whereNull('canceled_at')
            ->whereNotNull('linen_amount')
            ->where('linen_paid', false)
            ->sum('linen_amount');

        $parkingUnpaid = Booking::whereNull('canceled_at')
            ->whereNotNull('parking_amount')
            ->where('parking_paid', false)
            ->sum('parking_amount');

        return view('admin.finance.index', compact(
            'year', 'totals', 'monthlyData', 'movements', 'previousBalance', 'availableYears', 'globalBalance',
            'cleaningUnpaid', 'linenUnpaid', 'parkingUnpaid'
        ));
    }

    public function create(): View
    {
        return view('admin.finance.form', ['entry' => new FinancialEntry()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        FinancialEntry::create($data);

        return redirect()->route('admin.finance.index')->with('success', 'Voce aggiunta.');
    }

    public function edit(FinancialEntry $entry): View
    {
        $entry->load('attachments');

        return view('admin.finance.form', compact('entry'));
    }

    public function update(Request $request, FinancialEntry $entry): RedirectResponse
    {
        $data = $this->validated($request);
        $entry->update($data);

        return redirect()->route('admin.finance.index')->with('success', 'Voce aggiornata.');
    }

    public function destroy(FinancialEntry $entry): RedirectResponse
    {
        $entry->delete();

        return redirect()->route('admin.finance.index')->with('success', 'Voce eliminata.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type'            => ['required', 'in:income,expense'],
            'category'        => ['required', 'string', Rule::in(array_keys(config('finance.categories')))],
            'description'     => ['nullable', 'string', 'max:1000'],
            'amount'          => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'entry_date'      => ['required', 'date'],
            'tax_declaration' => ['nullable', 'boolean'],
        ]);
    }

    /** Returns years that have bookings or financial entries, plus the current year */
    private function availableYears(): array
    {
        $bookingYears = Booking::selectRaw('YEAR(checkin) as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y')
            ->toArray();

        $entryYears = FinancialEntry::selectRaw('YEAR(entry_date) as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y')
            ->toArray();

        $years = array_unique(array_merge($bookingYears, $entryYears, [now()->year]));
        rsort($years);

        return $years;
    }
}
