<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

/**
 * Extracts locale from URL prefix and resolves the user's preferred locale.
 * Priority: route parameter (URL prefix) > session > Accept-Language header > Italian fallback.
 */
class ResolveLocaleFromRoute
{
    private const SUPPORTED = ['it', 'en', 'fr', 'de'];
    private const FALLBACK   = 'it';
    private const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): mixed
    {
        // Extract locale from route if present (e.g. {locale}/appartamento)
        $locale = $request->route('locale');

        // If no route locale, try session or browser preference
        if (!$locale || !in_array($locale, self::SUPPORTED, true)) {
            // 1. Explicit session value (set by language switcher)
            if ($session = Session::get(self::SESSION_KEY)) {
                $locale = $this->validate($session);
            }
            // 2. Browser Accept-Language header (primary language only)
            else {
                $accepted = $request->getLanguages();
                if (!empty($accepted)) {
                    $primary = strtolower(substr($accepted[0], 0, 2));
                    $locale = in_array($primary, self::SUPPORTED, true) ? $primary : self::FALLBACK;
                } else {
                    $locale = self::FALLBACK;
                }
            }
        }

        App::setLocale($locale);
        Session::put(self::SESSION_KEY, $locale);

        return $next($request);
    }

    private function validate(string $locale): string
    {
        return in_array($locale, self::SUPPORTED, true) ? $locale : self::FALLBACK;
    }
}
