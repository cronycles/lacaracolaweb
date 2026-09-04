<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function index(Request $request): View
    {
        $query = Person::where('newsletter_subscribed', true);

        // Filter by guest status
        if ($request->input('filter') === 'guests') {
            $query->has('bookings');
        } elseif ($request->input('filter') === 'non_guests') {
            $query->doesntHave('bookings');
        }

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscribers = $query->withCount('bookings')
            ->orderBy('last_name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.newsletter', compact('subscribers'));
    }

    public function toggle(Person $person): RedirectResponse
    {
        if ($person->newsletter_subscribed) {
            $person->unsubscribeFromNewsletter();
        } else {
            $person->subscribeToNewsletter();
        }

        return redirect()->back()->with('success', 'Iscrizione aggiornata.');
    }
}
