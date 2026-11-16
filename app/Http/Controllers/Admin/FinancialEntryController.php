<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FinancialEntry;
use App\Services\BookingMovements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinancialEntryController extends Controller
{
    public function index(Request $request, BookingMovements $bookingMovements): View
    {
        $year = (int) $request->input('year', now()->year);
        $allMovements = $bookingMovements->all();

        foreach (FinancialEntry::with('attachments')->get() as $entry) {
            $allMovements->push([
                'date' => $entry->entry_date,
                'type' => $entry->type,
                'category_label' => config('finance.categories')[$entry->category] ?? $entry->category,
                'description' => $entry->description,
                'amount' => (float) $entry->amount,
                'source' => 'entry',
                'entry' => $entry,
                'booking_id' => null,
                'model_type' => 'entry',
                'model_id' => $entry->id,
                'attachments' => $entry->attachments,
            ]);
        }

        $balance = static fn ($items) => $items->where('type', 'income')->sum('amount')
            - $items->where('type', 'expense')->sum('amount');
        $globalBalance = $balance($allMovements);
        $previousBalance = $balance($allMovements->filter(fn ($m) => $m['date']->year < $year));
        $movements = $allMovements->filter(fn ($m) => $m['date']->year === $year)->values();
        $bookingRows = $movements->where('model_type', 'booking');
        $entryRows = $movements->where('model_type', 'entry');

        $totals = [
            'income' => $movements->where('type', 'income')->sum('amount'),
            'expenses' => $movements->where('type', 'expense')->sum('amount'),
            'booking_income' => $bookingRows->where('source', 'booking_income')->sum('amount'),
            'booking_parking' => $bookingRows->where('source', 'booking_parking')->sum('amount'),
            'booking_expenses' => $bookingRows->where('type', 'expense')->sum('amount'),
            'extra_income' => $entryRows->where('type', 'income')->sum('amount'),
            'extra_expenses' => $entryRows->where('type', 'expense')->sum('amount'),
            'cleaning_paid' => $bookingRows->where('source', 'booking_cleaning')->sum('amount'),
            'linen_paid' => $bookingRows->where('source', 'booking_linen')->sum('amount'),
            'utilities' => $entryRows->filter(fn ($m) => $m['type'] === 'expense' && $m['entry']->category === 'utenze')->sum('amount'),
        ];
        $totals['balance'] = $totals['income'] - $totals['expenses'];

        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthly = $movements->filter(fn ($m) => $m['date']->month === $month);
            $monthlyData[$month] = [
                'income' => $monthly->where('type', 'income')->sum('amount'),
                'expenses' => $monthly->where('type', 'expense')->sum('amount'),
            ];
        }

        $movements = $movements->sort(function ($a, $b) {
            return ($a['date']->timestamp <=> $b['date']->timestamp)
                ?: (($a['entry']?->id ?? $a['booking_id']) <=> ($b['entry']?->id ?? $b['booking_id']));
        })->values();
        $runningBalance = $previousBalance;
        $movements = $movements->map(function ($movement) use (&$runningBalance) {
            $runningBalance += $movement['type'] === 'income' ? $movement['amount'] : -$movement['amount'];
            $movement['running_balance'] = $runningBalance;

            return $movement;
        })->reverse()->values();

        $availableYears = $allMovements->map(fn ($m) => $m['date']->year)
            ->push(now()->year)->unique()->sortDesc()->values()->all();

        $unpaid = static fn (string $service) => Booking::whereNull('canceled_at')
            ->whereDate('checkin', '<=', today())
            ->whereNotNull($service.'_amount')
            ->where($service.'_paid', false)
            ->sum($service.'_amount');
        $cleaningUnpaid = $unpaid('cleaning');
        $linenUnpaid = $unpaid('linen');
        $parkingUnpaid = $unpaid('parking');

        return view('admin.finance.index', compact(
            'year', 'totals', 'monthlyData', 'movements', 'previousBalance', 'availableYears', 'globalBalance',
            'cleaningUnpaid', 'linenUnpaid', 'parkingUnpaid'
        ));
    }

    public function create(): View
    {
        return view('admin.finance.form', ['entry' => new FinancialEntry]);
    }

    public function store(Request $request): RedirectResponse
    {
        FinancialEntry::create($this->validated($request));

        return redirect()->route('admin.finance.index')->with('success', 'Voce aggiunta.');
    }

    public function edit(FinancialEntry $entry): View
    {
        $entry->load('attachments');

        return view('admin.finance.form', compact('entry'));
    }

    public function update(Request $request, FinancialEntry $entry): RedirectResponse
    {
        $entry->update($this->validated($request));

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
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', Rule::in(array_keys(config('finance.categories')))],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'entry_date' => ['required', 'date'],
            'tax_declaration' => ['nullable', 'boolean'],
        ]);
    }
}
