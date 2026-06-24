<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

/**
 * Extracts locale from URL prefix and resolves the user's preferred locale.
 * Priority: URL path prefix (/en/, /it/ …) > session > Accept-Language header > Italian fallback.
 */
class ResolveLocaleFromRoute
{
    private const SUPPORTED = ['it', 'en', 'fr', 'de'];
    private const FALLBACK   = 'it';
    private const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): mixed
    {
        // Extract locale from URL path prefix (e.g. /en/apartment → 'en')
        $urlSegment = $request->segment(1);
        if ($urlSegment && in_array($urlSegment, self::SUPPORTED, true)) {
            $locale = $urlSegment;
        }
        // If no URL locale prefix, try session or browser preference
        elseif ($session = Session::get(self::SESSION_KEY)) {
            $locale = $this->validate($session);
        } else {
            $accepted = $request->getLanguages();
            if (!empty($accepted)) {
                $primary = strtolower(substr($accepted[0], 0, 2));
                $locale = in_array($primary, self::SUPPORTED, true) ? $primary : self::FALLBACK;
            } else {
                $locale = self::FALLBACK;
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
