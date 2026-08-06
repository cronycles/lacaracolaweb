<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Services\BookingCreationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookingRequestController extends Controller
{
    /** List pending requests (not declined, not yet converted), oldest first, with a person-match preview. */
    public function index(BookingCreationService $creationService): View
    {
        $requests = BookingRequest::pending()
            ->orderBy('created_at')
            ->get();

        $matches = $requests->mapWithKeys(function (BookingRequest $request) use ($creationService) {
            return [$request->id => $creationService->previewMatch($this->personData($request))];
        });

        return view('admin.booking-requests.index', [
            'requests' => $requests,
            'matches'  => $matches,
        ]);
    }

    /** Confirm a pending request: find/create the guest, create the linked booking, redirect to edit it. */
    public function confirm(BookingRequest $bookingRequest, BookingCreationService $creationService): RedirectResponse
    {
        $person = $creationService->findOrCreatePerson($this->personData($bookingRequest));

        $booking = Booking::create([
            'person_id'          => $person->id,
            'booking_request_id' => $bookingRequest->id,
            'checkin'            => $bookingRequest->checkin,
            'checkout'           => $bookingRequest->checkout,
            'adults'             => $bookingRequest->adults,
            'children'           => $bookingRequest->children,
            'source'             => 'direct',
            'locale'             => $bookingRequest->locale,
            'notes'              => $bookingRequest->message,
        ]);

        AvailabilityBlock::create([
            'start_date' => $bookingRequest->checkin,
            'end_date'   => $bookingRequest->checkout,
            'reason'     => 'booked',
            'booking_id' => $booking->id,
        ]);

        return redirect()
            ->route('admin.bookings.edit', $booking)
            ->with('success', 'Richiesta confermata: completa i dati economici della prenotazione.');
    }

    /** Decline a pending request: keeps the row (legal consent proof) but removes it from the queue. */
    public function decline(BookingRequest $bookingRequest): RedirectResponse
    {
        $bookingRequest->update(['declined_at' => now()]);

        return redirect()
            ->route('admin.booking-requests.index')
            ->with('success', 'Richiesta rifiutata.');
    }

    /** @return array{first_name: string, last_name: string, email: string|null, phone: string|null} */
    private function personData(BookingRequest $bookingRequest): array
    {
        return [
            'first_name' => $bookingRequest->first_name,
            'last_name'  => $bookingRequest->last_name,
            'email'      => $bookingRequest->email,
            'phone'      => $bookingRequest->phone,
        ];
    }
}
