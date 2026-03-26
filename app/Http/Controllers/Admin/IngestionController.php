<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Person;
use App\Services\BookingEmailParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IngestionController extends Controller
{
    /** Show the paste-email form. */
    public function index(): View
    {
        return view('admin.ingestion', [
            'parsed'         => null,
            'rawText'        => '',
            'existingPerson' => null,
        ]);
    }

    /** Parse the pasted email and show the pre-filled confirmation form. */
    public function parse(Request $request): View
    {
        $request->validate(['raw_text' => ['required', 'string', 'max:10000']]);

        $parsed = (new BookingEmailParser())->parse($request->input('raw_text'));

        $existingPerson = null;
        if (!empty($parsed['email'])) {
            $existingPerson = Person::where('email', $parsed['email'])->first();
        }

        return view('admin.ingestion', [
            'parsed'         => $parsed,
            'rawText'        => $request->input('raw_text'),
            'existingPerson' => $existingPerson,
        ]);
    }

    /** Save the confirmed booking. Finds or creates the person first. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name'   => ['required', 'string', 'max:60'],
            'last_name'    => ['required', 'string', 'max:60'],
            'email'        => ['nullable', 'email', 'max:150'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'checkin'      => ['required', 'date'],
            'checkout'     => ['required', 'date', 'after:checkin'],
            'adults'       => ['required', 'integer', 'min:1', 'max:10'],
            'children'     => ['nullable', 'integer', 'min:0', 'max:6'],
            'babies'       => ['nullable', 'integer', 'min:0', 'max:6'],
            'source'       => ['required', 'in:direct,airbnb,booking,interhome'],
            'external_ref' => ['nullable', 'string', 'max:60'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        // Find existing person by email, or create a new one
        $person = null;
        if (!empty($data['email'])) {
            $person = Person::where('email', $data['email'])->first();
        }
        if (!$person) {
            $person = Person::create([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'] ?? null,
                'phone'      => $data['phone'] ?? null,
            ]);
        }

        $booking = Booking::create([
            'person_id'    => $person->id,
            'checkin'      => $data['checkin'],
            'checkout'     => $data['checkout'],
            'adults'       => $data['adults'],
            'children'     => $data['children'] ?? 0,
            'babies'       => $data['babies'] ?? 0,
            'source'       => $data['source'],
            'external_ref' => $data['external_ref'] ?? null,
            'notes'        => $data['notes'] ?? null,
        ]);

        AvailabilityBlock::create([
            'start_date' => $data['checkin'],
            'end_date'   => $data['checkout'],
            'reason'     => 'booked',
            'booking_id' => $booking->id,
        ]);

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Prenotazione creata con successo tramite ingestion.');
    }
}
