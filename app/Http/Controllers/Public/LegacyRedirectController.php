<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Handles legacy URLs (without locale prefix) redirecting to the locale-aware equivalents.
 * These routes must use controller methods (not Closures) to be compatible with route:cache.
 */
class LegacyRedirectController extends Controller
{
    public function appartamento(): RedirectResponse
    {
        return $this->localRedirect('apartment', 301);
    }

    public function doveSiamo(): RedirectResponse
    {
        return $this->localRedirect('map', 301);
    }

    public function esperienze(): RedirectResponse
    {
        return $this->localRedirect('experiences', 301);
    }

    public function recensioni(): RedirectResponse
    {
        return $this->localRedirect('reviews', 301);
    }

    public function regoleCasa(): RedirectResponse
    {
        return $this->localRedirect('rules', 301);
    }

    public function postiUtili(): RedirectResponse
    {
        return $this->localRedirect('useful-places', 301);
    }

    public function disponibilita(): RedirectResponse
    {
        return $this->localRedirect('booking.thanks', 301);
    }

    // --- Legacy redirects for the renamed booking URL slug (formerly "disponibilita"/"availability") ---
    public function bookingThanksItLegacy(): RedirectResponse
    {
        return redirect(route_locale('booking.thanks', [], 'it'), 301);
    }

    public function bookingThanksEnLegacy(): RedirectResponse
    {
        return redirect(route_locale('booking.thanks', [], 'en'), 301);
    }

    public function bookingThanksFrLegacy(): RedirectResponse
    {
        return redirect(route_locale('booking.thanks', [], 'fr'), 301);
    }

    public function bookingThanksDeLegacy(): RedirectResponse
    {
        return redirect(route_locale('booking.thanks', [], 'de'), 301);
    }

    public function home(): RedirectResponse
    {
        return redirect(route_locale('home', [], 'it'), 301);
    }

    public function contattaci(): RedirectResponse
    {
        return $this->localRedirect('contact', 301);
    }

    private function localRedirect(string $routeName, int $status): RedirectResponse
    {
        $locale = session('locale', 'it');

        return redirect(route_locale($routeName, [], $locale), $status);
    }
}
