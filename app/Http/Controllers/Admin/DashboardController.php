<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FinancialEntry;
use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $year = now()->year;

        // Financial totals for the current year (only paid items)
        $bookingIncome = Booking::whereNull('canceled_at')
            ->whereYear(\DB::raw('COALESCE(income_paid_at, checkout)'), $year)
            ->where('income_paid', true)
            ->whereNotNull('income_amount')
            ->sum('income_amount');

        $parkingIncome = Booking::whereNull('canceled_at')
            ->whereYear(\DB::raw('COALESCE(parking_paid_at, checkout)'), $year)
            ->where('parking_paid', true)
            ->whereNotNull('parking_amount')
            ->sum('parking_amount');

        $bookingExpenses = Booking::whereNull('canceled_at')
            ->whereYear(\DB::raw('COALESCE(services_paid_at, checkout)'), $year)
            ->selectRaw('COALESCE(SUM(CASE WHEN cleaning_paid = 1 THEN cleaning_amount ELSE 0 END), 0) +
                         COALESCE(SUM(CASE WHEN linen_paid = 1 THEN linen_amount ELSE 0 END), 0) as total')
            ->value('total') ?? 0;

        $extraIncome   = FinancialEntry::where('type', 'income')->whereYear('entry_date', $year)->sum('amount');
        $extraExpenses = FinancialEntry::where('type', 'expense')->whereYear('entry_date', $year)->sum('amount');

        // bookingExpenses = cleaning+linen paid = same amounts collected from guests (pass-through)
        $totalIncome   = $bookingIncome + $parkingIncome + $bookingExpenses + $extraIncome;
        $totalExpenses = $bookingExpenses + $extraExpenses;

        // Cumulative all-time balance (no year filter)
        $globalBookingIncome = Booking::whereNull('canceled_at')
            ->where('income_paid', true)
            ->whereNotNull('income_amount')
            ->sum('income_amount');

        $globalParkingIncome = Booking::whereNull('canceled_at')
            ->where('parking_paid', true)
            ->whereNotNull('parking_amount')
            ->sum('parking_amount');

        $globalBookingExpenses = Booking::whereNull('canceled_at')
            ->selectRaw('COALESCE(SUM(CASE WHEN cleaning_paid = 1 THEN cleaning_amount ELSE 0 END), 0) +
                         COALESCE(SUM(CASE WHEN linen_paid = 1 THEN linen_amount ELSE 0 END), 0) as total')
            ->value('total') ?? 0;

        $globalExtraIncome   = FinancialEntry::where('type', 'income')->sum('amount');
        $globalExtraExpenses = FinancialEntry::where('type', 'expense')->sum('amount');

        // globalBookingExpenses = all-time cleaning+linen paid = same amounts collected (pass-through)
        $globalBalance = ($globalBookingIncome + $globalParkingIncome + $globalBookingExpenses + $globalExtraIncome) - ($globalBookingExpenses + $globalExtraExpenses);

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
            'global_balance'   => $globalBalance,

            // Current booking (guests in the apartment right now)
            // Considers checkin_time and checkout_time from config so that
            // a guest arriving today is only "home" from checkin_time onwards,
            // and a guest departing today is "gone" from checkout_time onwards.
            'current_booking'  => (static function (): ?Booking {
                $now          = now();
                $checkinTime  = config('apartment.booking.checkin_time', '15:00');
                $checkoutTime = config('apartment.booking.checkout_time', '10:00');

                return Booking::whereNull('canceled_at')
                    ->whereDate('checkin', '<=', $now->toDateString())
                    ->whereDate('checkout', '>=', $now->toDateString())
                    ->with('person')
                    ->get()
                    ->first(static function (Booking $booking) use ($now, $checkinTime, $checkoutTime): bool {
                        $checkinAt  = \Carbon\Carbon::parse($booking->checkin->toDateString() . ' ' . $checkinTime);
                        $checkoutAt = \Carbon\Carbon::parse($booking->checkout->toDateString() . ' ' . $checkoutTime);

                        return $now->gte($checkinAt) && $now->lt($checkoutAt);
                    });
            })(),

            // Cleaning / linen payment summary (visible to all with view_bookings)
            'cleaning_unpaid'  => Booking::whereNull('canceled_at')
                                         ->whereNotNull('cleaning_amount')
                                         ->where('cleaning_paid', false)
                                         ->sum('cleaning_amount'),
            'linen_unpaid'     => Booking::whereNull('canceled_at')
                                         ->whereNotNull('linen_amount')
                                         ->where('linen_paid', false)
                                         ->sum('linen_amount'),
            'parking_unpaid'   => Booking::whereNull('canceled_at')
                                         ->whereNotNull('parking_amount')
                                         ->where('parking_paid', false)
                                         ->sum('parking_amount'),
            'cleaning_paid_total' => Booking::whereNull('canceled_at')
                                         ->whereNotNull('cleaning_amount')
                                         ->where('cleaning_paid', true)
                                         ->sum('cleaning_amount'),
            'linen_paid_total' => Booking::whereNull('canceled_at')
                                         ->whereNotNull('linen_amount')
                                         ->where('linen_paid', true)
                                         ->sum('linen_amount'),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
