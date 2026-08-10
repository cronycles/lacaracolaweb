<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $apartment          = config('apartment');
        $bookingMode        = Setting::get('booking_mode', 'form');
        $bookingExternalUrl = Setting::get('booking_external_url', '');
        $unavailableDates = $this->unavailableDatesForPublicCalendar();
        $countries        = Country::whereNotNull('iso2')->orderBy('name_it')->pluck('name_it', 'iso2')->toArray();
        $countriesDial    = Country::whereNotNull('iso2')->whereNotNull('dial_code')->orderBy('name_it')->pluck('dial_code', 'iso2')->toArray();

        return view('public.home', compact('apartment', 'bookingMode', 'bookingExternalUrl', 'unavailableDates', 'countries', 'countriesDial'));
    }

    /**
     * @return array<int, string>
     */
    private function unavailableDatesForPublicCalendar(): array
    {
        $today = now()->startOfDay();
        $windowEnd = now()->addYears(2)->endOfDay();

        $bookings = Booking::query()
            ->whereNull('canceled_at')
            ->whereDate('checkin', '<=', $windowEnd->toDateString())
            ->whereDate('checkout', '>', $today->toDateString())
            ->get(['checkin', 'checkout']);

        $manualBlocks = AvailabilityBlock::query()
            ->whereNull('booking_id')
            ->whereNull('booking_request_id')
            ->whereDate('start_date', '<=', $windowEnd->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->get(['start_date', 'end_date']);

        $pendingBlocks = AvailabilityBlock::query()
            ->whereNull('booking_id')
            ->whereNotNull('booking_request_id')
            ->whereDate('start_date', '<=', $windowEnd->toDateString())
            ->whereDate('end_date', '>', $today->toDateString())
            ->get(['start_date', 'end_date']);

        $dates = [];

        foreach ($bookings as $booking) {
            $cursor = Carbon::parse($booking->checkin)->startOfDay();
            $checkout = Carbon::parse($booking->checkout)->startOfDay();

            if ($cursor->lt($today)) {
                $cursor = $today->copy();
            }

            while ($cursor->lt($checkout)) {
                $dates[$cursor->format('Y-m-d')] = true;
                $cursor->addDay();
            }
        }

        foreach ($manualBlocks as $block) {
            $cursor = Carbon::parse($block->start_date)->startOfDay();
            $endDate = Carbon::parse($block->end_date)->startOfDay();

            if ($cursor->lt($today)) {
                $cursor = $today->copy();
            }

            while ($cursor->lte($endDate)) {
                $dates[$cursor->format('Y-m-d')] = true;
                $cursor->addDay();
            }
        }

        foreach ($pendingBlocks as $block) {
            $cursor = Carbon::parse($block->start_date)->startOfDay();
            $endDate = Carbon::parse($block->end_date)->startOfDay();

            if ($cursor->lt($today)) {
                $cursor = $today->copy();
            }

            while ($cursor->lt($endDate)) {
                $dates[$cursor->format('Y-m-d')] = true;
                $cursor->addDay();
            }
        }

        return array_keys($dates);
    }
}
