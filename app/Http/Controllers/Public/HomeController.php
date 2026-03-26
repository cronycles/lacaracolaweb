<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $apartment          = config('apartment');
        $bookingMode        = Setting::get('booking_mode', 'form');
        $bookingExternalUrl = Setting::get('booking_external_url', '');

        return view('public.home', compact('apartment', 'bookingMode', 'bookingExternalUrl'));
    }
}
