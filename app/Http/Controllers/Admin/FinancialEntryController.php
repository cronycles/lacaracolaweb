<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FinancialEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialEntryController extends Controller
{
    public function index(Request $request): View
    {
        $year = (int) $request->input('year', now()->year);

        // Booking-sourced income (income_amount where income_paid = true) for the year
        $bookingIncome = Booking::whereNull('canceled_at')
            ->whereYear('checkin', $year)
            ->where('income_paid', true)
            ->whereNotNull('income_amount')
            ->sum('income_amount');

        // Booking-sourced expenses (cleaning + linen only when marked as paid) for the year
        $bookingCleaning = Booking::whereNull('canceled_at')
            ->whereYear('checkin', $year)
            ->where('cleaning_paid', true)
            ->whereNotNull('cleaning_amount')
            ->sum('cleaning_amount');

        $bookingLinen = Booking::whereNull('canceled_at')
            ->whereYear('checkin', $year)
            ->where('linen_paid', true)
            ->whereNotNull('linen_amount')
            ->sum('linen_amount');

        $bookingExpenses = $bookingCleaning + $bookingLinen;

        // Extra financial entries for the year
        $extraIncome = FinancialEntry::where('type', 'income')
            ->whereYear('entry_date', $year)
            ->sum('amount');

        $extraExpenses = FinancialEntry::where('type', 'expense')
            ->whereYear('entry_date', $year)
            ->sum('amount');

        $totals = [
            'income'           => $bookingIncome + $extraIncome,
            'expenses'         => $bookingExpenses + $extraExpenses,
            'booking_income'   => $bookingIncome,
            'booking_expenses' => $bookingExpenses,
            'extra_income'     => $extraIncome,
            'extra_expenses'   => $extraExpenses,
        ];

        $totals['balance'] = $totals['income'] - $totals['expenses'];

        // Cumulative all-time balance (no year filter)
        $globalBookingIncome = Booking::whereNull('canceled_at')
            ->where('income_paid', true)
            ->whereNotNull('income_amount')
            ->sum('income_amount');

        $globalBookingExpenses = Booking::whereNull('canceled_at')
            ->selectRaw('COALESCE(SUM(CASE WHEN cleaning_paid = 1 THEN cleaning_amount ELSE 0 END), 0) +
                         COALESCE(SUM(CASE WHEN linen_paid = 1 THEN linen_amount ELSE 0 END), 0) as total')
            ->value('total') ?? 0;

        $globalExtraIncome    = FinancialEntry::where('type', 'income')->sum('amount');
        $globalExtraExpenses  = FinancialEntry::where('type', 'expense')->sum('amount');

        $globalBalance = ($globalBookingIncome + $globalExtraIncome) - ($globalBookingExpenses + $globalExtraExpenses);

        // Monthly breakdown for the selected year
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $bInc = Booking::whereNull('canceled_at')
                ->whereYear('checkin', $year)
                ->whereMonth('checkin', $m)
                ->where('income_paid', true)
                ->whereNotNull('income_amount')
                ->sum('income_amount');

            $bExp = Booking::whereNull('canceled_at')
                ->whereYear('checkin', $year)
                ->whereMonth('checkin', $m)
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
                'income'   => $bInc + $eInc,
                'expenses' => $bExp + $eExp,
            ];
        }

        // All extra entries for the year, sorted by date desc
        $entries = FinancialEntry::whereYear('entry_date', $year)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        $availableYears = $this->availableYears();

        return view('admin.finance.index', compact(
            'year', 'totals', 'monthlyData', 'entries', 'availableYears', 'globalBalance'
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
            'type'        => ['required', 'in:income,expense'],
            'category'    => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'entry_date'  => ['required', 'date'],
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
