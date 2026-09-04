<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

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

        if (! in_array($locale, self::SUPPORTED)) {
            return redirect('/');
        }

        Session::put('locale', $locale);

        // Redirect to home page in the new locale
        return redirect(route_locale('home', [], $locale));
    }

    /**
     * Switch the user's locale and redirect to the equivalent page in the new
     * locale (POST method). A plain redirect()->back() would keep the previous
     * URL's locale prefix (e.g. /it/...), which the ResolveLocaleFromRoute
     * middleware then uses to override the session locale we just set here —
     * so we must rebuild the URL with the new locale's prefix/slug instead.
     */
    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(self::SUPPORTED)],
        ]);

        $newLocale = $data['locale'];
        Session::put('locale', $newLocale);

        return redirect($this->resolveLocalizedRedirectUrl($request, $newLocale));
    }

    /**
     * Given the referring page, find the equivalent route in the new locale.
     * Falls back to the localized home page if the referer can't be resolved.
     */
    private function resolveLocalizedRedirectUrl(Request $request, string $newLocale): string
    {
        $referer = $request->headers->get('referer');

        if ($referer) {
            try {
                $refererRequest = Request::create($referer);
                $matchedRoute = app('router')->getRoutes()->match($refererRequest);
                $routeName = $matchedRoute->getName();

                if ($routeName && str_contains($routeName, '.')) {
                    [, $baseName] = explode('.', $routeName, 2);
                    $newRouteName = "{$newLocale}.{$baseName}";

                    if (Route::has($newRouteName)) {
                        $params = $matchedRoute->parameters();

                        $url = route($newRouteName, $params);
                        $fragment = parse_url($referer, PHP_URL_FRAGMENT);

                        return $fragment ? "{$url}#{$fragment}" : $url;
                    }
                }
            } catch (HttpExceptionInterface|Throwable) {
                // Referer didn't match a known route — fall through to home.
            }
        }

        return route_locale('home', [], $newLocale);
    }
}
