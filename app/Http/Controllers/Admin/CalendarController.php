<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        // Load bookings and manual blocks for the calendar view
        $bookings = Booking::with('person')
            ->where('checkout', '>=', today()->subMonths(1))
            ->orderBy('checkin')
            ->get();

        $blocks = AvailabilityBlock::whereNull('booking_id')
            ->where('end_date', '>=', today()->subMonths(1))
            ->orderBy('start_date')
            ->get();

        return view('admin.calendar', compact('bookings', 'blocks'));
    }

    public function storeBlock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'reason'     => ['required', 'in:owner,maintenance'],
            'notes'      => ['nullable', 'string', 'max:255'],
        ]);

        AvailabilityBlock::create($data);

        return redirect()->route('admin.calendar')->with('success', 'Blocco aggiunto.');
    }

    public function destroyBlock(AvailabilityBlock $block): RedirectResponse
    {
        // Only manual blocks can be deleted here (booking-linked blocks go via bookings)
        abort_if($block->booking_id !== null, 403);
        $block->delete();

        return redirect()->route('admin.calendar')->with('success', 'Blocco rimosso.');
    }
}
