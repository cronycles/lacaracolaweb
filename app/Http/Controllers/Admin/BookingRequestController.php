<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingRequestDeclinedMail;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Services\BookingCreationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            // Pre-fill the financial fields with the price quoted to the guest
            // on the public form, so the owner just has to verify/adjust it.
            'income_amount'      => $bookingRequest->estimated_stay_amount,
            'cleaning_amount'    => $bookingRequest->estimated_cleaning_amount,
            'linen_amount'       => $bookingRequest->estimated_linen_amount,
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

    /** Decline a pending request: keeps the row (legal consent proof), removes it from the queue, notifies the guest. */
    public function decline(BookingRequest $bookingRequest): RedirectResponse
    {
        $bookingRequest->update(['declined_at' => now()]);

        try {
            Mail::to($bookingRequest->email)
                ->locale($bookingRequest->locale ?? 'it')
                ->send(new BookingRequestDeclinedMail($bookingRequest));
        } catch (\Throwable $e) {
            Log::error('BookingRequestDeclinedMail failed to send', [
                'error'              => $e->getMessage(),
                'booking_request_id' => $bookingRequest->id,
                'email'              => $bookingRequest->email,
            ]);
        }

        return redirect()
            ->route('admin.booking-requests.index')
            ->with('success', 'Richiesta rifiutata: abbiamo avvisato l\'ospite via email.');
    }

    /** Permanently delete a request row (no email, no trace) — for cleanup/testing or requests the owner wants gone entirely. */
    public function destroy(BookingRequest $bookingRequest): RedirectResponse
    {
        $bookingRequest->delete();

        return redirect()
            ->route('admin.booking-requests.index')
            ->with('success', 'Richiesta eliminata.');
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
