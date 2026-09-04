<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FinancialEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaxDeclarationController extends Controller
{
    public function index(Request $request): View
    {
        // ── Determine the year to display ────────────────────────────────────

        $availableYears = $this->availableYears();
        $defaultYear = $availableYears[0] ?? now()->year;
        $year = (int) $request->input('year', $defaultYear);

        // ── Year totals (only flagged + paid items) ───────────────────────────

        // Booking income (flagged + paid)
        $incomeFromBookings = Booking::whereNull('canceled_at')
            ->where('income_tax', true)
            ->where('income_paid', true)
            ->whereNotNull('income_amount')
            ->whereYear(DB::raw('COALESCE(income_paid_at, checkout)'), $year)
            ->sum('income_amount');

        // Booking parking (flagged + paid) — treated as income
        $parkingFromBookings = Booking::whereNull('canceled_at')
            ->where('parking_tax', true)
            ->where('parking_paid', true)
            ->whereNotNull('parking_amount')
            ->whereYear(DB::raw('COALESCE(parking_paid_at, checkout)'), $year)
            ->sum('parking_amount');

        // Booking cleaning (flagged + paid) — expense
        $cleaningFromBookings = Booking::whereNull('canceled_at')
            ->where('cleaning_tax', true)
            ->where('cleaning_paid', true)
            ->whereNotNull('cleaning_amount')
            ->whereYear(DB::raw('COALESCE(services_paid_at, checkout)'), $year)
            ->sum('cleaning_amount');

        // Booking linen (flagged + paid) — expense
        $linenFromBookings = Booking::whereNull('canceled_at')
            ->where('linen_tax', true)
            ->where('linen_paid', true)
            ->whereNotNull('linen_amount')
            ->whereYear(DB::raw('COALESCE(services_paid_at, checkout)'), $year)
            ->sum('linen_amount');

        // Extra financial entries (flagged, any type)
        $extraIncome = FinancialEntry::where('tax_declaration', true)
            ->where('type', 'income')
            ->whereYear('entry_date', $year)
            ->sum('amount');

        $extraExpenses = FinancialEntry::where('tax_declaration', true)
            ->where('type', 'expense')
            ->whereYear('entry_date', $year)
            ->sum('amount');

        $totals = [
            'income' => (float) $incomeFromBookings + (float) $parkingFromBookings + (float) $cleaningFromBookings + (float) $linenFromBookings + (float) $extraIncome,
            'expenses' => (float) $cleaningFromBookings + (float) $linenFromBookings + (float) $extraExpenses,
        ];

        // ── Build movements list (flagged items, paid and unpaid) ─────────────

        $movements = collect();

        // 1. Extra financial entries (flagged)
        $entries = FinancialEntry::where('tax_declaration', true)
            ->whereYear('entry_date', $year)
            ->with('attachments')
            ->get();

        foreach ($entries as $entry) {
            $movements->push([
                'date' => $entry->entry_date,
                'type' => $entry->type,
                'category_label' => config('finance.categories')[$entry->category] ?? $entry->category,
                'description' => $entry->description,
                'amount' => (float) $entry->amount,
                'included' => true, // financial entries are always "paid" once created
                'source' => 'entry',
                'entry' => $entry,
                'booking_id' => null,
                'model_type' => 'entry',
                'model_id' => $entry->id,
                'attachments' => $entry->attachments,
            ]);
        }

        // 2. Booking income rows (flagged — both paid and unpaid shown)
        $bookingIncomeRows = Booking::whereNull('canceled_at')
            ->where('income_tax', true)
            ->whereNotNull('income_amount')
            ->whereYear(DB::raw('COALESCE(income_paid_at, checkout)'), $year)
            ->with(['person', 'attachments'])
            ->get();

        foreach ($bookingIncomeRows as $booking) {
            $date = $booking->income_paid_at ?? $booking->checkout;
            $desc = ($booking->person?->full_name ?? 'Prenotazione #'.$booking->id)
                .' ('.$booking->checkin->format('d/m').'–'.$booking->checkout->format('d/m/Y').')';
            $movements->push([
                'date' => $date,
                'type' => 'income',
                'category_label' => 'Incasso prenotazione',
                'description' => $desc,
                'amount' => (float) $booking->income_amount,
                'included' => (bool) $booking->income_paid,
                'source' => 'booking',
                'entry' => null,
                'booking_id' => $booking->id,
                'model_type' => 'booking',
                'model_id' => $booking->id,
                'attachments' => $booking->attachments,
            ]);
        }

        // 3. Booking parking rows (flagged)
        $bookingParkingRows = Booking::whereNull('canceled_at')
            ->where('parking_tax', true)
            ->whereNotNull('parking_amount')
            ->whereYear(DB::raw('COALESCE(parking_paid_at, checkout)'), $year)
            ->with(['person', 'attachments'])
            ->get();

        foreach ($bookingParkingRows as $booking) {
            $date = $booking->parking_paid_at ?? $booking->checkout;
            $desc = ($booking->person?->full_name ?? 'Prenotazione #'.$booking->id)
                .' ('.$booking->checkin->format('d/m').'–'.$booking->checkout->format('d/m/Y').')';
            $movements->push([
                'date' => $date,
                'type' => 'income',
                'category_label' => 'Posto auto',
                'description' => $desc,
                'amount' => (float) $booking->parking_amount,
                'included' => (bool) $booking->parking_paid,
                'source' => 'booking',
                'entry' => null,
                'booking_id' => $booking->id,
                'model_type' => 'booking',
                'model_id' => $booking->id,
                'attachments' => $booking->attachments,
            ]);
        }

        // 4. Booking cleaning rows (flagged)
        $bookingCleaningRows = Booking::whereNull('canceled_at')
            ->where('cleaning_tax', true)
            ->whereNotNull('cleaning_amount')
            ->whereYear(DB::raw('COALESCE(services_paid_at, checkout)'), $year)
            ->with(['person', 'attachments'])
            ->get();

        foreach ($bookingCleaningRows as $booking) {
            $date = $booking->services_paid_at ?? $booking->checkout;
            $desc = ($booking->person?->full_name ?? 'Prenotazione #'.$booking->id)
                .' ('.$booking->checkin->format('d/m').'–'.$booking->checkout->format('d/m/Y').')';
            // Income: cleaning fee collected from guest (pass-through)
            $movements->push([
                'date' => $date,
                'type' => 'income',
                'category_label' => 'Pulizie (incasso)',
                'description' => $desc,
                'amount' => (float) $booking->cleaning_amount,
                'included' => (bool) $booking->cleaning_paid,
                'source' => 'booking',
                'entry' => null,
                'booking_id' => $booking->id,
                'model_type' => 'booking',
                'model_id' => $booking->id,
                'attachments' => $booking->attachments,
            ]);
            // Expense: cleaning fee paid to cleaner (pass-through)
            $movements->push([
                'date' => $date,
                'type' => 'expense',
                'category_label' => 'Pulizie',
                'description' => $desc,
                'amount' => (float) $booking->cleaning_amount,
                'included' => (bool) $booking->cleaning_paid,
                'source' => 'booking',
                'entry' => null,
                'booking_id' => $booking->id,
                'model_type' => 'booking',
                'model_id' => $booking->id,
                'attachments' => $booking->attachments,
            ]);
        }

        // 5. Booking linen rows (flagged)
        $bookingLinenRows = Booking::whereNull('canceled_at')
            ->where('linen_tax', true)
            ->whereNotNull('linen_amount')
            ->whereYear(DB::raw('COALESCE(services_paid_at, checkout)'), $year)
            ->with(['person', 'attachments'])
            ->get();

        foreach ($bookingLinenRows as $booking) {
            $date = $booking->services_paid_at ?? $booking->checkout;
            $desc = ($booking->person?->full_name ?? 'Prenotazione #'.$booking->id)
                .' ('.$booking->checkin->format('d/m').'–'.$booking->checkout->format('d/m/Y').')';
            // Income: linen fee collected from guest (pass-through)
            $movements->push([
                'date' => $date,
                'type' => 'income',
                'category_label' => 'Biancheria (incasso)',
                'description' => $desc,
                'amount' => (float) $booking->linen_amount,
                'included' => (bool) $booking->linen_paid,
                'source' => 'booking',
                'entry' => null,
                'booking_id' => $booking->id,
                'model_type' => 'booking',
                'model_id' => $booking->id,
                'attachments' => $booking->attachments,
            ]);
            // Expense: linen fee paid to provider (pass-through)
            $movements->push([
                'date' => $date,
                'type' => 'expense',
                'category_label' => 'Biancheria',
                'description' => $desc,
                'amount' => (float) $booking->linen_amount,
                'included' => (bool) $booking->linen_paid,
                'source' => 'booking',
                'entry' => null,
                'booking_id' => $booking->id,
                'model_type' => 'booking',
                'model_id' => $booking->id,
                'attachments' => $booking->attachments,
            ]);
        }

        // Sort descending by date (most recent first)
        $movements = $movements->sortByDesc(function ($m) {
            return $m['date']->timestamp;
        })->values();

        return view('admin.finance.tax-declaration', compact('year', 'availableYears', 'totals', 'movements'));
    }

    /** Returns years that have flagged booking items or flagged financial entries, plus current year */
    private function availableYears(): array
    {
        $bookingYears = Booking::whereNull('canceled_at')
            ->where(function ($q) {
                $q->where('income_tax', true)
                    ->orWhere('cleaning_tax', true)
                    ->orWhere('linen_tax', true)
                    ->orWhere('parking_tax', true);
            })
            ->selectRaw(sql_year_expr('checkin').' as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y')
            ->toArray();

        $entryYears = FinancialEntry::where('tax_declaration', true)
            ->selectRaw(sql_year_expr('entry_date').' as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y')
            ->toArray();

        $years = array_unique(array_merge($bookingYears, $entryYears, [now()->year]));
        rsort($years);

        return $years;
    }
}
