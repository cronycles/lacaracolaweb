<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\PricingQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function quote(Request $request, PricingQuoteService $pricingQuoteService): JsonResponse
    {
        $data = $request->validate([
            'checkin'  => ['required', 'date', 'after_or_equal:today'],
            'checkout' => ['required', 'date', 'after:checkin'],
        ]);

        $checkin = new \DateTimeImmutable($data['checkin']);
        $checkout = new \DateTimeImmutable($data['checkout']);
        $nights = (int) $checkin->diff($checkout)->days;
        $minNights = (int) config('apartment.booking.min_nights', 3);

        if ($nights < $minNights) {
            return response()->json([
                'available' => false,
                'message' => __('app.error_min_nights', ['nights' => $minNights]),
            ]);
        }

        $quote = $pricingQuoteService->calculate($data['checkin'], $data['checkout']);

        if (! $quote['available']) {
            return response()->json([
                'available' => false,
                'message' => __('app.booking_price_unavailable'),
            ]);
        }

        $stayCents = (int) ($quote['stay_cents'] ?? 0);
        $cleaningCents = ((int) config('apartment.booking.cleaning_fee', 0)) * 100;
        $totalCents = $stayCents + $cleaningCents;

        return response()->json([
            'available' => true,
            'stay_cents' => $stayCents,
            'cleaning_cents' => $cleaningCents,
            'total_cents' => $totalCents,
            'nights' => $quote['nights'],
            'message' => __('app.booking_price_detail', ['nights' => $quote['nights']]),
        ]);
    }

    /**
     * Handle availability request form submission (flow B).
     * Sends an email notification to the owner and redirects to thank-you page.
     */
    public function requestAvailability(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        // Honeypot: bots fill hidden fields that humans never see
        if ($request->filled('website')) {
            return $request->wantsJson()
                ? response()->json(['success' => true])
                : redirect()->route('booking.thanks');
        }

        $data = $request->validate([
            'checkin'    => ['required', 'date', 'after_or_equal:today'],
            'checkout'   => ['required', 'date', 'after:checkin'],
            'adults'     => ['required', 'integer', 'min:1', 'max:6'],
            'children'   => ['nullable', 'integer', 'min:0', 'max:6'],
            'name'       => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:150'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'message'    => ['nullable', 'string', 'max:1000'],
            'newsletter' => ['nullable', 'boolean'],
        ]);

        // Validate minimum nights
        $checkin   = new \DateTimeImmutable($data['checkin']);
        $checkout  = new \DateTimeImmutable($data['checkout']);
        $nights    = (int) $checkin->diff($checkout)->days;
        $minNights = (int) config('apartment.booking.min_nights', 3);

        if ($nights < $minNights) {
            $error = __('app.error_min_nights', ['nights' => $minNights]);

            if ($request->wantsJson()) {
                return response()->json(['errors' => ['checkout' => [$error]]], 422);
            }

            return back()->withErrors(['checkout' => $error])->withInput();
        }

        // Store in session for thank-you page (non-AJAX fallback)
        session()->flash('booking_request', $data);

        // TODO (Phase 2): send email notification to owner via Laravel Mail
        // Mail::to(config('apartment.email'))->send(new BookingRequestMail($data));

        return $request->wantsJson()
            ? response()->json(['success' => true])
            : redirect()->route('booking.thanks');
    }

    public function thanks(): View
    {
        return view('public.booking-thanks');
    }
}
