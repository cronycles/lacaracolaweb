<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FinancialEntry;
use App\Models\Person;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $year = now()->year;

        // Financial totals for the current year (only paid items)
        $bookingIncome = Booking::whereNull('canceled_at')
            ->whereYear('checkin', $year)
            ->where('income_paid', true)
            ->whereNotNull('income_amount')
            ->sum('income_amount');

        $bookingExpenses = Booking::whereNull('canceled_at')
            ->whereYear('checkin', $year)
            ->selectRaw('COALESCE(SUM(CASE WHEN cleaning_paid = 1 THEN cleaning_amount ELSE 0 END), 0) + 
                         COALESCE(SUM(CASE WHEN linen_paid = 1 THEN linen_amount ELSE 0 END), 0) as total')
            ->value('total') ?? 0;

        $extraIncome   = FinancialEntry::where('type', 'income')->whereYear('entry_date', $year)->sum('amount');
        $extraExpenses = FinancialEntry::where('type', 'expense')->whereYear('entry_date', $year)->sum('amount');

        $totalIncome   = $bookingIncome + $extraIncome;
        $totalExpenses = $bookingExpenses + $extraExpenses;

        $stats = [
            'total_bookings'   => Booking::count(),
            'active_bookings'  => Booking::whereNull('canceled_at')->count(),
            'canceled_bookings'=> Booking::whereNotNull('canceled_at')->count(),
            'total_guests'     => Person::count(),
            'newsletter_subs'  => Person::where('newsletter_subscribed', true)->count(),
            'upcoming'         => Booking::whereNull('canceled_at')
                                         ->where('checkin', '>=', today())
                                         ->orderBy('checkin')
                                         ->with('person')
                                         ->take(5)
                                         ->get(),
            'finance_year'     => $year,
            'total_income'     => $totalIncome,
            'total_expenses'   => $totalExpenses,
            'balance'          => $totalIncome - $totalExpenses,
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
