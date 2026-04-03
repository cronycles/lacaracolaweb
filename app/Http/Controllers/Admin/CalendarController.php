<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $centerMonth = $this->resolveCenterMonth($request->query('month'));
        $windowStart = $centerMonth->copy()->subMonth()->startOfMonth();
        $windowEnd = $centerMonth->copy()->addMonth()->endOfMonth();

        // Load bookings and manual blocks overlapping the visible 3-month window
        $bookings = Booking::with('person')
            ->whereDate('checkin', '<=', $windowEnd->toDateString())
            ->whereDate('checkout', '>', $windowStart->toDateString())
            ->orderBy('checkin')
            ->get();

        $blocks = AvailabilityBlock::whereNull('booking_id')
            ->whereDate('start_date', '<=', $windowEnd->toDateString())
            ->whereDate('end_date', '>=', $windowStart->toDateString())
            ->orderBy('start_date')
            ->get();

        $months = [
            $windowStart->copy(),
            $windowStart->copy()->addMonth(),
            $windowStart->copy()->addMonths(2),
        ];

        $bookedDays = [];
        $arrivalDays = [];
        $departureDays = [];
        foreach ($bookings as $bookingItem) {
            $checkinDate = Carbon::parse($bookingItem->checkin)->startOfDay();
            $checkoutDate = Carbon::parse($bookingItem->checkout)->startOfDay();

            $arrivalDays[$checkinDate->format('Y-m-d')] = true;
            $departureDays[$checkoutDate->format('Y-m-d')] = true;

            $cursor = $checkinDate->copy()->addDay();

            while ($cursor->lt($checkoutDate)) {
                $bookedDays[$cursor->format('Y-m-d')] = true;
                $cursor->addDay();
            }
        }

        $ownerDays = [];
        $ownerArrivalDays = [];
        $ownerDepartureDays = [];
        $maintenanceDays = [];
        $maintenanceArrivalDays = [];
        $maintenanceDepartureDays = [];
        foreach ($blocks as $blockItem) {
            $blockStart = Carbon::parse($blockItem->start_date)->startOfDay();
            $blockEnd = Carbon::parse($blockItem->end_date)->startOfDay();
            $blockType = $blockItem->type ?? $blockItem->reason;

            if ($blockType === 'owner') {
                if ($blockStart->equalTo($blockEnd)) {
                    $ownerDays[$blockStart->format('Y-m-d')] = true;
                    continue;
                }

                $ownerArrivalDays[$blockStart->format('Y-m-d')] = true;
                $ownerDepartureDays[$blockEnd->format('Y-m-d')] = true;

                $cursor = $blockStart->copy()->addDay();
                while ($cursor->lt($blockEnd)) {
                    $ownerDays[$cursor->format('Y-m-d')] = true;
                    $cursor->addDay();
                }

                continue;
            }

            if ($blockStart->equalTo($blockEnd)) {
                $maintenanceDays[$blockStart->format('Y-m-d')] = true;
                continue;
            }

            $maintenanceArrivalDays[$blockStart->format('Y-m-d')] = true;
            $maintenanceDepartureDays[$blockEnd->format('Y-m-d')] = true;

            $cursor = $blockStart->copy()->addDay();
            while ($cursor->lt($blockEnd)) {
                $maintenanceDays[$cursor->format('Y-m-d')] = true;
                $cursor->addDay();
            }
        }

        [$selectorStart, $selectorEnd] = $this->resolveSelectorBounds($centerMonth);
        $selectorMonths = [];
        $cursor = $selectorStart->copy();

        while ($cursor->lte($selectorEnd)) {
            $selectorMonths[] = [
                'value' => $cursor->format('Y-m'),
                'label' => ucfirst($cursor->translatedFormat('F Y')),
            ];
            $cursor->addMonth();
        }

        return view('admin.calendar', [
            'bookings' => $bookings,
            'blocks' => $blocks,
            'months' => $months,
            'bookedDays' => $bookedDays,
            'arrivalDays' => $arrivalDays,
            'departureDays' => $departureDays,
            'ownerDays' => $ownerDays,
            'ownerArrivalDays' => $ownerArrivalDays,
            'ownerDepartureDays' => $ownerDepartureDays,
            'maintenanceDays' => $maintenanceDays,
            'maintenanceArrivalDays' => $maintenanceArrivalDays,
            'maintenanceDepartureDays' => $maintenanceDepartureDays,
            'windowCenterMonth' => $centerMonth->format('Y-m'),
            'previousWindowMonth' => $centerMonth->copy()->subMonth()->format('Y-m'),
            'nextWindowMonth' => $centerMonth->copy()->addMonth()->format('Y-m'),
            'selectorMonths' => $selectorMonths,
            'windowLabel' => sprintf(
                '%s - %s',
                $months[0]->translatedFormat('F Y'),
                $months[2]->translatedFormat('F Y')
            ),
        ]);
    }

    public function storeBlock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'reason'     => ['required', 'in:owner,maintenance'],
            'notes'      => ['nullable', 'string', 'max:255'],
        ]);

        AvailabilityBlock::create($data);

        $month = $this->normalizeMonthQuery($request->query('month', $request->input('month')));

        return redirect()
            ->route('admin.calendar', $month !== null ? ['month' => $month] : [])
            ->with('success', 'Blocco aggiunto.');
    }

    public function destroyBlock(Request $request, AvailabilityBlock $block): RedirectResponse
    {
        // Only manual blocks can be deleted here (booking-linked blocks go via bookings)
        abort_if($block->booking_id !== null, 403);
        $block->delete();

        $month = $this->normalizeMonthQuery($request->query('month', $request->input('month')));

        return redirect()
            ->route('admin.calendar', $month !== null ? ['month' => $month] : [])
            ->with('success', 'Blocco rimosso.');
    }

    private function resolveCenterMonth(mixed $monthQuery): Carbon
    {
        $normalized = $this->normalizeMonthQuery($monthQuery);

        if ($normalized === null) {
            return now()->startOfMonth();
        }

        return Carbon::createFromFormat('Y-m', $normalized)->startOfMonth();
    }

    private function normalizeMonthQuery(mixed $monthQuery): ?string
    {
        if (! is_string($monthQuery)) {
            return null;
        }

        $monthQuery = trim($monthQuery);
        if ($monthQuery === '' || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthQuery)) {
            return null;
        }

        return $monthQuery;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveSelectorBounds(Carbon $centerMonth): array
    {
        $minBooking = Booking::query()->min('checkin');
        $maxBooking = Booking::query()->max('checkout');

        $minBlock = AvailabilityBlock::query()
            ->whereNull('booking_id')
            ->min('start_date');

        $maxBlock = AvailabilityBlock::query()
            ->whereNull('booking_id')
            ->max('end_date');

        $minDateRaw = collect([$minBooking, $minBlock])->filter()->min();
        $maxDateRaw = collect([$maxBooking, $maxBlock])->filter()->max();

        $defaultStart = now()->startOfMonth()->subMonths(12);
        $defaultEnd = now()->startOfMonth()->addMonths(24);

        $start = $minDateRaw !== null
            ? Carbon::parse($minDateRaw)->startOfMonth()->subMonths(1)
            : $defaultStart;

        $end = $maxDateRaw !== null
            ? Carbon::parse($maxDateRaw)->startOfMonth()->addMonths(1)
            : $defaultEnd;

        if ($centerMonth->lt($start)) {
            $start = $centerMonth->copy();
        }

        if ($centerMonth->gt($end)) {
            $end = $centerMonth->copy();
        }

        return [$start, $end];
    }
}
