<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingGuestController extends Controller
{
    /** Attach an additional guest to a booking. */
    public function store(Request $request, Booking $prenotazioni): RedirectResponse
    {
        $data = $request->validate([
            'person_id' => ['required', 'integer', 'exists:people,id'],
        ]);

        $person = Person::findOrFail((int) $data['person_id']);

        // Prevent attaching the primary guest again
        if ($prenotazioni->person_id === $person->id) {
            return back()->with('error', 'Questo ospite è già il capogruppo della prenotazione.');
        }

        // Sync avoids duplicates
        $prenotazioni->additionalGuests()->syncWithoutDetaching([$person->id]);

        return back()->with('success', "{$person->full_name} aggiunto agli ospiti.");
    }

    /** Detach an additional guest from a booking. */
    public function destroy(Booking $prenotazioni, Person $person): RedirectResponse
    {
        $prenotazioni->additionalGuests()->detach($person->id);

        return back()->with('success', "{$person->full_name} rimosso dagli ospiti.");
    }
}
