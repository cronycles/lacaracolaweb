<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Person;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_bookings'  => Booking::count(),
            'active_bookings' => Booking::whereNull('canceled_at')->count(),
            'canceled_bookings' => Booking::whereNotNull('canceled_at')->count(),
            'total_guests'    => Person::count(),
            'newsletter_subs' => Person::where('newsletter_subscribed', true)->count(),
            'upcoming'        => Booking::whereNull('canceled_at')
                                        ->where('checkin', '>=', today())
                                        ->orderBy('checkin')
                                        ->with('person')
                                        ->take(5)
                                        ->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
