<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\BookingRequestMail;
use App\Mail\BookingRequestPendingMail;
use App\Models\AvailabilityBlock;
use App\Models\BookingRequest;
use App\Models\Setting;
use App\Services\AvailabilityService;
use App\Services\PricingQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function quote(Request $request, PricingQuoteService $pricingQuoteService, AvailabilityService $availabilityService): JsonResponse
    {
        $minBookingLeadDays = (int) config('apartment.booking.min_booking_lead_days', 0);
        $earliestCheckin = now()->startOfDay()->addDays($minBookingLeadDays)->toDateString();

        $data = $request->validate([
            'checkin' => ['required', 'date', 'after_or_equal:'.$earliestCheckin],
            'checkout' => ['required', 'date', 'after:checkin'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:12'],
            'parking_requested' => ['nullable', 'boolean'],
        ], [
            'checkin.after_or_equal' => __('app.error_checkin_lead_time', ['days' => $minBookingLeadDays]),
        ]);

        $checkin = new \DateTimeImmutable($data['checkin']);
        $checkout = new \DateTimeImmutable($data['checkout']);
        $nights = (int) $checkin->diff($checkout)->days;
        $minNights = (int) Setting::get('pricing_min_nights', (string) config('apartment.booking.min_nights', 3));
        $maxNights = (int) config('apartment.booking.max_nights', 28);
        $guests = max(1, (int) ($data['guests'] ?? 1));

        if ($nights < $minNights) {
            return response()->json([
                'available' => false,
                'message' => __('app.error_min_nights', ['nights' => $minNights]),
            ]);
        }

        if ($nights > $maxNights) {
            return response()->json([
                'available' => false,
                'message' => __('app.error_max_nights', ['nights' => $maxNights]),
            ]);
        }

        if (! $availabilityService->isAvailable($data['checkin'], $data['checkout'])) {
            return response()->json([
                'available' => false,
                'message' => __('app.booking_dates_unavailable'),
            ]);
        }

        $parkingRequested = (bool) ($data['parking_requested'] ?? false);
        $quote = $pricingQuoteService->calculate($data['checkin'], $data['checkout'], $guests, $parkingRequested);

        if (! $quote['available']) {
            return response()->json([
                'available' => false,
                'message' => __('app.booking_price_unavailable'),
            ]);
        }

        // The public receives the house total and separately payable parking amount;
        // the internal stay/cleaning/linen breakdown remains private.
        return response()->json([
            'available' => true,
            'nights' => $quote['nights'],
            'guests' => $quote['guests'],
            'total_cents' => $quote['total_cents'],
            'parking_requested' => $quote['parking_requested'],
            'parking_cents' => $quote['parking_cents'],
            'message' => __('app.booking_price_detail', ['nights' => $quote['nights']]),
        ]);
    }

    /**
     * Handle availability request form submission (flow B).
     * Sends an email notification to the owner and redirects to thank-you page.
     */
    public function requestAvailability(Request $request, PricingQuoteService $pricingQuoteService, AvailabilityService $availabilityService): RedirectResponse|JsonResponse
    {
        // Honeypot: bots fill hidden fields that humans never see
        if ($request->filled('website')) {
            return $request->wantsJson()
                ? response()->json(['success' => true])
                : redirect()->route('booking.thanks');
        }

        $minBookingLeadDays = (int) config('apartment.booking.min_booking_lead_days', 0);
        $earliestCheckin = now()->startOfDay()->addDays($minBookingLeadDays)->toDateString();

        $data = $request->validate([
            'checkin' => ['required', 'date', 'after_or_equal:'.$earliestCheckin],
            'checkout' => ['required', 'date', 'after:checkin'],
            'adults' => ['required', 'integer', 'min:1', 'max:6'],
            'children' => ['nullable', 'integer', 'min:0', 'max:6'],
            'babies' => ['nullable', 'integer', 'min:0', 'max:3'],
            'pets' => ['nullable', 'integer', 'min:0', 'max:3'],
            'first_name' => ['required', 'string', 'min:3', 'max:100'],
            'last_name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            // Digits only: the international dial code is already captured separately
            // via phone_prefix, so guests must not re-type "+34" (or similar) here.
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9][0-9\s\-]*$/'],
            'phone_prefix' => ['required', 'string', 'max:10'],
            'message' => ['nullable', 'string', 'max:1000'],
            'newsletter' => ['nullable', 'boolean'],
            'parking_requested' => ['nullable', 'boolean'],
            'accepted_terms' => ['required', 'accepted'],
        ], [
            'checkin.after_or_equal' => __('app.error_checkin_lead_time', ['days' => $minBookingLeadDays]),
            'accepted_terms.required' => __('app.error_terms_required'),
            'accepted_terms.accepted' => __('app.error_terms_required'),
            'phone.regex' => __('app.error_phone_digits_only'),
        ]);

        $guests = (int) $data['adults'] + (int) ($data['children'] ?? 0);
        $maxBeds = (int) config('apartment.specs.beds');

        if ($guests > $maxBeds) {
            $error = __('app.error_max_guests', ['guests' => $maxBeds]);

            if ($request->wantsJson()) {
                return response()->json(['errors' => ['children' => [$error]]], 422);
            }

            return back()->withErrors(['children' => $error])->withInput();
        }

        // Combine dial prefix + number into a single formatted phone string,
        // e.g. "+39 333 123 4567" (same format as the admin guest phone field).
        $data['phone'] = trim(($data['phone_prefix'] ?? '').' '.$data['phone']);
        unset($data['phone_prefix']);

        // Validate minimum nights
        $checkin = new \DateTimeImmutable($data['checkin']);
        $checkout = new \DateTimeImmutable($data['checkout']);
        $nights = (int) $checkin->diff($checkout)->days;
        $minNights = (int) Setting::get('pricing_min_nights', (string) config('apartment.booking.min_nights', 3));

        if ($nights < $minNights) {
            $error = __('app.error_min_nights', ['nights' => $minNights]);

            if ($request->wantsJson()) {
                return response()->json(['errors' => ['checkout' => [$error]]], 422);
            }

            return back()->withErrors(['checkout' => $error])->withInput();
        }

        if (! $availabilityService->isAvailable($data['checkin'], $data['checkout'])) {
            $error = __('app.booking_dates_unavailable');

            if ($request->wantsJson()) {
                return response()->json(['errors' => ['checkin' => [$error]]], 422);
            }

            return back()->withErrors(['checkin' => $error])->withInput();
        }

        // Store in session for thank-you page (non-AJAX fallback)
        session()->flash('booking_request', $data);

        // Recalculate the price server-side (never trust a client-submitted amount)
        // so the owner can see the guest's quoted price in the requests queue and
        // have it pre-filled into the booking's financial fields on acceptance.
        $parkingRequested = (bool) ($data['parking_requested'] ?? false);
        $quote = $pricingQuoteService->calculate($data['checkin'], $data['checkout'], $guests, $parkingRequested);

        $bookingRequest = BookingRequest::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'checkin' => $data['checkin'],
            'checkout' => $data['checkout'],
            'adults' => $data['adults'],
            'children' => $data['children'] ?? 0,
            'babies' => $data['babies'] ?? 0,
            'pets' => $data['pets'] ?? 0,
            'parking_requested' => $parkingRequested,
            'message' => $data['message'] ?? null,
            'newsletter' => (bool) ($data['newsletter'] ?? false),
            'terms_accepted_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'locale' => app()->getLocale(),
            'estimated_stay_amount' => $quote['available'] ? $quote['stay_cents'] / 100 : null,
            'estimated_cleaning_amount' => $quote['available'] ? $quote['cleaning_cents'] / 100 : null,
            'estimated_linen_amount' => $quote['available'] ? $quote['linen_cents'] / 100 : null,
            'estimated_parking_amount' => $quote['available'] && $parkingRequested ? $quote['parking_cents'] / 100 : null,
            'estimated_total_amount' => $quote['available'] ? $quote['total_cents'] / 100 : null,
        ]);

        AvailabilityBlock::create([
            'start_date' => $bookingRequest->checkin,
            'end_date' => $bookingRequest->checkout,
            'reason' => 'pending',
            'booking_request_id' => $bookingRequest->id,
        ]);

        try {
            Mail::to(config('apartment.email'))->send(new BookingRequestMail($data, $bookingRequest));
        } catch (\Throwable $e) {
            Log::error('BookingRequestMail failed to send', [
                'error' => $e->getMessage(),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'checkin' => $data['checkin'],
            ]);
        }

        try {
            Mail::to($data['email'])->send(new BookingRequestPendingMail($bookingRequest));
        } catch (\Throwable $e) {
            Log::error('BookingRequestPendingMail failed to send', [
                'error' => $e->getMessage(),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'checkin' => $data['checkin'],
            ]);
        }

        return $request->wantsJson()
            ? response()->json(['success' => true])
            : redirect()->route('booking.thanks');
    }

    public function thanks(): View
    {
        return view('public.booking-thanks');
    }
}
