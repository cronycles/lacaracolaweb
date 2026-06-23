<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    private const SUPPORTED = ['it', 'en', 'fr', 'de'];

    /**
     * Switch locale via GET query parameter (?locale=en)
     * Redirects to home in the new locale.
     */
    public function set(Request $request): RedirectResponse
    {
        $locale = $request->query('locale');

        if (!in_array($locale, self::SUPPORTED)) {
            return redirect('/');
        }

        Session::put('locale', $locale);

        // Redirect to home page in the new locale
        return redirect(route_locale('home', [], $locale));
    }

    /**
     * Switch the user's locale and redirect back (POST method).
     */
    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(self::SUPPORTED)],
        ]);

        Session::put('locale', $data['locale']);

        return redirect()->back();
    }
}
