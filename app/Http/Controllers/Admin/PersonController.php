<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function index(Request $request): View|RedirectResponse
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
        return view('admin.people.form', ['person' => new Person()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $person = Person::create($data);

        return redirect()->route('admin.people.show', $person)->with('success', 'Ospite aggiunto.');
    }

    public function show(Person $ospiti): View
    {
        $ospiti->load('bookings');

        return view('admin.people.show', ['person' => $ospiti]);
    }

    public function edit(Person $ospiti): View
    {
        return view('admin.people.form', ['person' => $ospiti]);
    }

    public function update(Request $request, Person $ospiti): RedirectResponse
    {
        $data = $this->validated($request, $ospiti->id);
        $ospiti->update($data);

        return redirect()->route('admin.people.show', $ospiti)->with('success', 'Ospite aggiornato.');
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
        return $request->validate([
            'first_name'      => ['required', 'string', 'max:80'],
            'last_name'       => ['required', 'string', 'max:80'],
            'email'           => ['nullable', 'email', 'max:150', "unique:people,email,{$ignoreId}"],
            'phone'           => ['nullable', 'string', 'max:30'],
            'birth_date'      => ['nullable', 'date'],
            'country_code'    => ['nullable', 'string', 'max:10', Rule::in(array_keys(config('apartment.guest_countries', [])))],
            'document_type'   => ['nullable', 'string', 'max:30'],
            'document_number' => ['nullable', 'string', 'max:60'],
            'newsletter_subscribed' => ['nullable', 'boolean'],
        ]);
    }
}
