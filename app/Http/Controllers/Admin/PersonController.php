<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Person;
use App\Services\GuestReporting\Data\ItalianMunicipalities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function index(Request $request): View|RedirectResponse|JsonResponse
    {
        $query = Person::query();

        $search = trim((string) $request->input('q', ''));
        $search = preg_replace('/[\p{C}\p{Z}]+/u', ' ', $search) ?? '';
        $search = trim($search);

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // JSON autocomplete endpoint (used by booking guest-search widget)
        if ($request->expectsJson() || $request->input('format') === 'json') {
            $people = $query->orderBy('last_name')->limit(10)->get();

            return response()->json($people->map(fn (Person $p) => [
                'id'         => $p->id,
                'full_name'  => $p->full_name,
                'birth_date' => $p->birth_date?->format('d/m/Y'),
            ]));
        }

        $people = $query->orderBy('last_name')->paginate(25)->withQueryString();

        // Prevent false "empty" pages when query string contains an out-of-range page.
        if ($people->isEmpty() && $people->total() > 0 && $people->currentPage() > 1) {
            $params = [];
            if ($search !== '') {
                $params['q'] = $search;
            }

            return redirect()->route('admin.people.index', $params);
        }

        return view('admin.people.index', compact('people'));
    }

    public function create(): View
    {
        $attachBookingId = request()->integer('attach_booking_id') ?: null;
        $returnTo = $attachBookingId
            ? route('admin.bookings.show', $attachBookingId)
            : null;

        return view('admin.people.form', [
            'person'          => new Person(),
            'comuniNames'     => ItalianMunicipalities::allValidNames(),
            'countries'       => Country::whereNotNull('iso2')->orderBy('name_it')->pluck('name_it', 'iso2')->toArray(),
            'returnTo'        => $returnTo,
            'attachBookingId' => $attachBookingId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $newsletterSubscribed = $request->boolean('newsletter_subscribed');
        unset($data['newsletter_subscribed']);
        $person = Person::create($data);
        $this->syncNewsletterPreference($person, $newsletterSubscribed);

        // If invoked from a booking page, attach the new person and redirect back
        $attachBookingId = $request->integer('attach_booking_id') ?: null;
        if ($attachBookingId) {
            $booking = Booking::find($attachBookingId);
            if ($booking && $booking->person_id !== $person->id) {
                $booking->additionalGuests()->syncWithoutDetaching([$person->id]);
            }
            return redirect()
                ->route('admin.bookings.show', $attachBookingId)
                ->with('success', "{$person->full_name} creato e aggiunto alla prenotazione.");
        }

        return redirect()->route('admin.people.show', $person)->with('success', 'Ospite aggiunto.');
    }

    public function show(Person $ospiti): View
    {
        $ospiti->load('bookings');

        return view('admin.people.show', ['person' => $ospiti]);
    }

    public function edit(Person $ospiti): View
    {
        return view('admin.people.form', [
            'person'      => $ospiti,
            'comuniNames' => ItalianMunicipalities::allValidNames(),
            'countries'   => Country::whereNotNull('iso2')->orderBy('name_it')->pluck('name_it', 'iso2')->toArray(),
            'returnTo'    => $this->resolveReturnTo(request(), $ospiti),
        ]);
    }

    public function update(Request $request, Person $ospiti): RedirectResponse
    {
        $data = $this->validated($request, $ospiti->id);
        $newsletterSubscribed = $request->boolean('newsletter_subscribed');
        unset($data['newsletter_subscribed']);
        $ospiti->update($data);
        $this->syncNewsletterPreference($ospiti, $newsletterSubscribed);

        return redirect($this->resolveReturnTo($request, $ospiti))
            ->with('success', 'Ospite aggiornato.');
    }

    public function destroy(Person $ospiti): RedirectResponse
    {
        $ospiti->delete();

        return redirect()->route('admin.people.index')->with('success', 'Ospite eliminato.');
    }

    /** Stays for a given person — filterable by date range */
    public function stays(Request $request, Person $person): View
    {
        $query = Booking::where('person_id', $person->id)->orderByDesc('checkin');

        if ($from = $request->input('from')) {
            $query->where('checkout', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->where('checkin', '<=', $to);
        }

        $bookings = $query->paginate(20)->withQueryString();

        return view('admin.people.stays', compact('person', 'bookings'));
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $countryCodes = Country::whereNotNull('iso2')->pluck('iso2')->all();

        return $request->validate([
            'first_name'      => ['required', 'string', 'max:80'],
            'last_name'       => ['required', 'string', 'max:80'],
            'email'           => ['nullable', 'email', 'max:150', "unique:people,email,{$ignoreId}"],
            'phone'           => ['nullable', 'string', 'max:30'],
            'birth_date'      => ['nullable', 'date'],
            'country_code'    => ['nullable', 'string', 'max:10', Rule::in($countryCodes)],
            'document_type'   => ['nullable', 'string', Rule::in(['passport', 'id_card', 'driving_license', 'residence_permit', 'other'])],
            'document_number' => ['nullable', 'string', 'max:60'],
            'newsletter_subscribed' => ['nullable', 'boolean'],
            // Guest reporting fields
            'gender'                       => ['nullable', 'string', Rule::in(['M', 'F'])],
            'birth_municipality'           => [
                'nullable', 'string', 'max:100',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('birth_country_code') === 'IT' && filled($value)
                        && ItalianMunicipalities::findCode($value) === null) {
                        $fail("'{$value}' non è un comune italiano riconosciuto. Verificare la dicitura.");
                    }
                },
            ],
            'birth_province'               => ['nullable', 'string', 'max:2'],
            'birth_country_code'           => ['nullable', 'string', Rule::in($countryCodes)],
            'nationality_code'             => ['nullable', 'string', Rule::in($countryCodes)],
            'document_issue_place'         => [
                'nullable', 'string', 'max:100', 'required_if:document_issue_country_code,IT',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('document_issue_country_code') === 'IT' && filled($value)
                        && ItalianMunicipalities::findCode($value) === null) {
                        $fail("'{$value}' non è un comune italiano riconosciuto per il luogo di rilascio.");
                    }
                },
            ],
            'document_issue_country_code'  => ['nullable', 'string', Rule::in($countryCodes)],
        ]);
    }

    private function resolveReturnTo(Request $request, Person $person): string
    {
        $returnTo = $request->input('return_to');

        if (! is_string($returnTo) || $returnTo === '') {
            return route('admin.people.index');
        }

        $indexUrl = route('admin.people.index');
        $showUrl = route('admin.people.show', $person);

        if (str_starts_with($returnTo, $indexUrl) || $returnTo === $showUrl) {
            return $returnTo;
        }

        return $indexUrl;
    }

    private function syncNewsletterPreference(Person $person, bool $newsletterSubscribed): void
    {
        if ($newsletterSubscribed) {
            $person->subscribeToNewsletter();

            return;
        }

        $person->unsubscribeFromNewsletter();
    }
}
