<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(): View
    {
        $bookings = Booking::with('person')
            ->orderByDesc('checkin')
            ->paginate(20);

        return view('admin.bookings.index', compact('bookings'));
    }

    public function create(): View
    {
        $people = Person::orderBy('last_name')->orderBy('first_name')->get();

        return view('admin.bookings.form', ['booking' => new Booking(), 'people' => $people]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

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

    private function validated(Request $request): array
    {
        return $request->validate([
            'person_id'    => ['required', 'exists:people,id'],
            'checkin'      => ['required', 'date'],
            'checkout'     => ['required', 'date', 'after:checkin'],
            'adults'       => ['required', 'integer', 'min:1', 'max:6'],
            'children'     => ['nullable', 'integer', 'min:0', 'max:6'],
            'babies'       => ['nullable', 'integer', 'min:0', 'max:6'],
            'pets'         => ['nullable', 'integer', 'min:0', 'max:4'],
            'source'       => ['required', 'in:direct,airbnb,booking,interhome'],
            'external_ref' => ['nullable', 'string', 'max:60'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
