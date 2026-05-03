<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        // Load bookings (guest reservations)
        $bookings = Booking::with('person')
            ->orderByDesc('checkin')
            ->get()
            ->each(function ($booking) {
                $booking->_type = 'booking';
            });

        // Load personal blocks (owner/maintenance blocks not linked to bookings)
        $personalBlocks = AvailabilityBlock::whereNull('booking_id')
            ->orderByDesc('start_date')
            ->get()
            ->each(function ($block) {
                $block->_type = 'block';
            });

        // Merge and sort all items by date (checkin/start_date)
        $allItems = $bookings
            ->merge($personalBlocks)
            ->sortByDesc(function ($item) {
                return $item->_type === 'booking' ? $item->checkin : $item->start_date;
            })
            ->values();

        $perPage = 20;
        $page = (int) $request->input('page', 1);
        $items = new LengthAwarePaginator(
            $allItems->forPage($page, $perPage),
            $allItems->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.bookings.index', compact('items'));
    }

    public function create(): View
    {
        $people = Person::orderBy('last_name')->orderBy('first_name')->get();

        return view('admin.bookings.form', ['booking' => new Booking(), 'people' => $people]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $person = Person::findOrFail($data['person_id']);
        $person->autoSubscribeToNewsletter();

        $booking = Booking::create($data);

        // Automatically create availability block
        AvailabilityBlock::create([
            'start_date' => $data['checkin'],
            'end_date'   => $data['checkout'],
            'reason'     => 'booked',
            'booking_id' => $booking->id,
        ]);

        return redirect()->route('admin.bookings.index')->with('success', 'Prenotazione aggiunta.');
    }

    public function show(Booking $prenotazioni): View
    {
        $prenotazioni->load('person');

        return view('admin.bookings.show', ['booking' => $prenotazioni]);
    }

    public function edit(Booking $prenotazioni): View
    {
        $people = Person::orderBy('last_name')->orderBy('first_name')->get();

        return view('admin.bookings.form', ['booking' => $prenotazioni, 'people' => $people]);
    }

    public function update(Request $request, Booking $prenotazioni): RedirectResponse
    {
        $data = $this->validated($request);
        $person = Person::findOrFail($data['person_id']);
        $person->autoSubscribeToNewsletter();
        $prenotazioni->update($data);

        // Sync the linked availability block dates
        if ($prenotazioni->availabilityBlock) {
            $prenotazioni->availabilityBlock->update([
                'start_date' => $data['checkin'],
                'end_date'   => $data['checkout'],
            ]);
        }

        return redirect()->route('admin.bookings.index')->with('success', 'Prenotazione aggiornata.');
    }

    public function destroy(Booking $prenotazioni): RedirectResponse
    {
        $prenotazioni->availabilityBlock?->delete();
        $prenotazioni->delete();

        return redirect()->route('admin.bookings.index')->with('success', 'Prenotazione eliminata.');
    }

    public function cancel(Booking $prenotazioni): RedirectResponse
    {
        if (! $prenotazioni->isCanceled()) {
            $prenotazioni->update(['canceled_at' => now()]);
        }

        return redirect()->back()->with('success', 'Prenotazione segnata come cancellata. Giorni liberati.');
    }

    public function restore(Booking $prenotazioni): RedirectResponse
    {
        if ($prenotazioni->isCanceled()) {
            $hasBookingConflict = Booking::query()
                ->whereKeyNot($prenotazioni->id)
                ->whereNull('canceled_at')
                ->whereDate('checkin', '<', $prenotazioni->checkout)
                ->whereDate('checkout', '>', $prenotazioni->checkin)
                ->exists();

            $hasManualBlockConflict = AvailabilityBlock::query()
                ->whereNull('booking_id')
                ->whereDate('start_date', '<', $prenotazioni->checkout)
                ->whereDate('end_date', '>=', $prenotazioni->checkin)
                ->exists();

            if ($hasBookingConflict || $hasManualBlockConflict) {
                return redirect()->back()->with('error', 'Ripristino non possibile: il periodo risulta già occupato.');
            }

            $prenotazioni->update(['canceled_at' => null]);
        }

        return redirect()->back()->with('success', 'Cancellazione rimossa. Prenotazione di nuovo attiva.');
    }

    // Personal blocks management (owner/maintenance)
    public function showBlock(AvailabilityBlock $block): View
    {
        return view('admin.bookings.show-block', compact('block'));
    }

    public function editBlock(AvailabilityBlock $block): View
    {
        return view('admin.bookings.form-block', compact('block'));
    }

    public function updateBlock(Request $request, AvailabilityBlock $block): RedirectResponse
    {
        $data = $this->validatedBlock($request);
        $block->update($data);

        return redirect()->route('admin.bookings.index')->with('success', 'Blocco aggiornato.');
    }

    public function destroyBlock(AvailabilityBlock $block): RedirectResponse
    {
        $block->delete();

        return redirect()->route('admin.bookings.index')->with('success', 'Blocco rimosso.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'person_id'       => ['required', 'exists:people,id'],
            'checkin'         => ['required', 'date'],
            'checkout'        => ['required', 'date', 'after:checkin'],
            'adults'          => ['required', 'integer', 'min:1', 'max:6'],
            'children'        => ['nullable', 'integer', 'min:0', 'max:6'],
            'babies'          => ['nullable', 'integer', 'min:0', 'max:6'],
            'pets'            => ['nullable', 'integer', 'min:0', 'max:4'],
            'source'          => ['required', 'in:direct,airbnb,booking,interhome'],
            'external_ref'    => ['nullable', 'string', 'max:60'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'income_amount'   => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'income_paid'     => ['nullable', 'boolean'],
            'cleaning_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'cleaning_paid'   => ['nullable', 'boolean'],
            'linen_amount'    => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'linen_paid'      => ['nullable', 'boolean'],
        ]);
    }

    private function validatedBlock(Request $request): array
    {
        return $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'reason'     => ['required', 'in:owner,maintenance'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
