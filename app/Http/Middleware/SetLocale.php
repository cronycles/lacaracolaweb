<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

/**
 * Detects and applies the user's preferred locale.
 * Priority: session > query param > browser Accept-Language header > Italian fallback.
 */
class SetLocale
{
    private const SUPPORTED = ['it', 'en', 'fr', 'de'];

    private const FALLBACK = 'it';

    private const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);
        Session::put(self::SESSION_KEY, $locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        // 1. Explicit session value (set by language switcher)
        if ($session = Session::get(self::SESSION_KEY)) {
            return $this->validate($session);
        }

        // 2. Browser Accept-Language header — only the primary (most-preferred) language.
        // Iterating all accepted languages would pick up secondary preferences (e.g. 'en')
        // for a Spanish user, causing the wrong locale instead of the Italian fallback.
        $accepted = $request->getLanguages();
        if (! empty($accepted)) {
            $primary = strtolower(substr($accepted[0], 0, 2));
            if (in_array($primary, self::SUPPORTED, true)) {
                return $primary;
            }
        }

        return self::FALLBACK;
    }

    private function validate(string $locale): string
    {
        return in_array($locale, self::SUPPORTED, true) ? $locale : self::FALLBACK;
    }
}
