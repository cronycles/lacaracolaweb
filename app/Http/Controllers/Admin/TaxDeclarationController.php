<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialEntry;
use App\Services\BookingMovements;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxDeclarationController extends Controller
{
    public function index(Request $request, BookingMovements $bookingMovements): View
    {
        $allMovements = $bookingMovements->all(taxOnly: true);

        foreach (FinancialEntry::where('tax_declaration', true)->with('attachments')->get() as $entry) {
            $allMovements->push([
                'date' => $entry->entry_date,
                'type' => $entry->type,
                'category_label' => config('finance.categories')[$entry->category] ?? $entry->category,
                'description' => $entry->description,
                'amount' => (float) $entry->amount,
                'included' => true,
                'source' => 'entry',
                'entry' => $entry,
                'booking_id' => null,
                'model_type' => 'entry',
                'model_id' => $entry->id,
                'attachments' => $entry->attachments,
            ]);
        }

        $availableYears = $allMovements->map(fn ($m) => $m['date']->year)->unique()->sortDesc()->values()->all();
        $defaultYear = in_array(now()->year, $availableYears, true) ? now()->year : ($availableYears[0] ?? now()->year);
        if ($availableYears === []) {
            $availableYears = [now()->year];
        }
        $year = (int) $request->input('year', $defaultYear);
        $movements = $allMovements->filter(fn ($m) => $m['date']->year === $year)
            ->sortByDesc(fn ($m) => $m['date']->timestamp)->values();
        $totals = [
            'income' => $movements->where('type', 'income')->sum('amount'),
            'expenses' => $movements->where('type', 'expense')->sum('amount'),
        ];

        return view('admin.finance.tax-declaration', compact('year', 'availableYears', 'totals', 'movements'));
    }
}
