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
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

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

    /** Persist the submitted guest-reporting data for every guest currently on the booking. */
    public function store(Request $request, string $token): RedirectResponse
    {
        $booking = $this->resolveValidBooking($token);

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
        $data = $request->validate($this->guestReportingValidationRules($request, $countryCodes));

        foreach ($data['guests'] as $guestData) {
            $this->persistGuestPerson($guestData, $booking);
        }

        return redirect()
            ->route('checkin.show', $token)
            ->with('success', __('app.checkin_saved_message'));
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

    /** Explicit "Confirm & submit" step — requires every guest's required fields to be complete. */
    public function confirm(Request $request, string $token): RedirectResponse
    {
        $booking = $this->resolveValidBooking($token);

        $guests = $booking->allGuests()->values();
        $totalGuests = $guests->count();
        $missing = [];

        foreach ($guests as $i => $guest) {
            $tipo = GuestClassifier::defaultTipoFor($i, $totalGuests);
            $requiresDoc = GuestClassifier::requiresDocument($tipo);

            $incomplete = empty($guest->gender)
                || empty($guest->birth_date)
                || empty($guest->birth_municipality)
                || empty($guest->birth_country_code)
                || empty($guest->nationality_code)
                || ($guest->birth_country_code === 'IT' && empty($guest->birth_province))
                || ($requiresDoc && (
                    empty($guest->document_type)
                    || empty($guest->document_number)
                    || empty($guest->document_issue_country_code)
                    || ($guest->document_issue_country_code === 'IT' && empty($guest->document_issue_place))
                ));

            if ($incomplete) {
                $missing[] = $guest->full_name;
            }
        }

        if (! empty($missing)) {
            return redirect()
                ->route('checkin.show', $token)
                ->with('error', __('app.checkin_incomplete_guests', ['guests' => implode(', ', $missing)]));
        }

        $booking->forceFill(['checkin_completed_at' => now()])->save();

        return redirect()
            ->route('checkin.show', $token)
            ->with('success', __('app.checkin_confirmed_message'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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
