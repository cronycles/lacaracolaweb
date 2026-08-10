<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Concerns\PersistsGuestReportingData;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Person;
use App\Services\GuestReporting\Data\ItalianMunicipalities;
use App\Services\GuestReporting\GuestClassifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Public, token-based "online check-in" flow. Guests reach this via an
 * unguessable link (no authentication) and can fill/edit their own and
 * their travel companions' AlloggiatiWeb data. Data is only ever saved here
 * — it is never submitted to the guest-reporting SOAP driver; the owner
 * still reviews/sends it from admin/guest-reporting exactly as today.
 */
class CheckinController extends Controller
{
    use PersistsGuestReportingData;

    /** Show the check-in form for a valid, non-expired token. */
    public function show(Request $request, string $token): View
    {
        $booking = Booking::where('checkin_token', $token)->first();

        if (! $booking) {
            abort(404);
        }

        App::setLocale($this->resolveLocale($request, $booking));

        if ($this->isTokenInvalid($booking)) {
            return view('public.checkin-expired');
        }

        $booking->load('person', 'additionalGuests');

        $guests = $booking->allGuests()->values();
        $totalGuests = $guests->count();

        return view('public.checkin', [
            'booking'      => $booking,
            'guests'       => $guests,
            'totalGuests'  => $totalGuests,
            'canAddCompanion' => $totalGuests < $booking->total_guests,
            'comuniNames'  => ItalianMunicipalities::allValidNames(),
            'countries'    => Country::whereNotNull('iso2')->orderBy('name_it')->pluck('name_it', 'iso2')->toArray(),
        ]);
    }

    /** Create and attach a new travel companion (name only), enforcing the total-guest cap. */
    public function addCompanion(Request $request, string $token): RedirectResponse
    {
        $booking = $this->resolveValidBooking($token);

        $currentTotal = 1 + $booking->additionalGuests()->count();
        if ($currentTotal >= $booking->total_guests) {
            return redirect()
                ->route('checkin.show', $token)
                ->with('error', __('app.checkin_companion_cap_reached'));
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
        ]);

        $person = Person::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
        ]);

        $booking->additionalGuests()->syncWithoutDetaching([$person->id]);

        return redirect()
            ->route('checkin.show', $token)
            ->with('success', __('app.checkin_companion_added'));
    }

    /** Save the submitted data and explicitly confirm the online check-in. */
    public function confirm(Request $request, string $token): RedirectResponse
    {
        $booking = $this->resolveValidBooking($token);

        if ($booking->allGuests()->count() < $booking->total_guests) {
            return redirect()
                ->route('checkin.show', $token)
                ->withInput()
                ->with('error', __('app.checkin_guest_count_error'));
        }

        try {
            $data = $this->validatedGuestData($request, $booking);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('checkin.show', $token)
                ->withErrors($exception->validator)
                ->withInput();
        }

        try {
            DB::transaction(function () use ($data, $booking): void {
                foreach ($data['guests'] as $guestData) {
                    $this->persistGuestPerson($guestData, $booking);
                }

                $booking->forceFill(['checkin_completed_at' => now()])->save();
            });
        } catch (Throwable $exception) {
            Log::error('Online check-in confirmation failed.', [
                'booking_id' => $booking->id,
                'exception'  => $exception,
            ]);

            return redirect()
                ->route('checkin.show', $token)
                ->withInput()
                ->with('error', __('app.checkin_save_error'));
        }

        return redirect()
            ->route('checkin.show', $token)
            ->with('success', __('app.checkin_confirmed_message'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Normalize the submitted rows and validate them against the current booking guests. */
    private function validatedGuestData(Request $request, Booking $booking): array
    {
        $guests = $booking->allGuests()->values();
        $totalGuests = $guests->count();
        $rawGuests = $request->input('guests', []);
        $guestsInput = [];

        foreach ($guests as $i => $guest) {
            $rowInput = is_array($rawGuests[$i] ?? null) ? $rawGuests[$i] : [];
            $guestsInput[$i] = array_merge($rowInput, [
                'person_id'       => $guest->id,
                'tipo_alloggiato' => GuestClassifier::defaultTipoFor($i, $totalGuests),
                'include'         => 1,
            ]);
        }

        $request->merge(['guests' => $guestsInput]);

        $countryCodes = Country::whereNotNull('iso2')->pluck('iso2')->all();

        return $request->validate($this->guestReportingValidationRules($request, $countryCodes));
    }

    /** Resolve a booking by token, aborting with 404 if the token is unknown, expired, or canceled. */
    private function resolveValidBooking(string $token): Booking
    {
        $booking = Booking::where('checkin_token', $token)->first();

        if (! $booking || $this->isTokenInvalid($booking)) {
            abort(404);
        }

        return $booking;
    }

    private function isTokenInvalid(Booking $booking): bool
    {
        return $booking->isCanceled()
            || ! $booking->checkin_token_expires_at
            || $booking->checkin_token_expires_at->isPast();
    }

    /**
     * Resolve the display locale: an explicit `?lang=` override (not persisted),
     * falling back to the booking's saved locale, then the app's default.
     */
    private function resolveLocale(Request $request, Booking $booking): string
    {
        $supported = config('routes.locales', ['it', 'en', 'fr', 'de']);

        $lang = $request->query('lang');
        if (is_string($lang) && in_array($lang, $supported, true)) {
            return $lang;
        }

        if ($booking->locale && in_array($booking->locale, $supported, true)) {
            return $booking->locale;
        }

        return config('routes.fallback', 'it');
    }
}
