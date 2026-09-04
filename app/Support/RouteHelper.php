<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Helper function to generate localized route URLs.
 * Automatically includes the locale parameter.
 *
 * Usage: route_locale('apartment', ['id' => 1])
 * Output: /it/appartamento (or /en/apartment, /fr/appartement, etc. based on current locale)
 */
class RouteHelper
{
    /**
     * Generate alternate URLs for the current localized public route.
     *
     * @return array<string, string>
     */
    public static function alternates(): array
    {
        $routeName = Route::currentRouteName();

        if (! is_string($routeName) || ! preg_match('/^[^.]+\.(.+)$/', $routeName, $matches)) {
            return [];
        }

        $routeNameWithoutLocale = $matches[1];
        $excludedRoutes = ['booking.thanks'];

        if (in_array($routeNameWithoutLocale, $excludedRoutes, true)) {
            return [];
        }

        $locales = config('routes.locales', []);
        $alternates = [];
        $route = request()->route();
        $parameters = is_object($route) && method_exists($route, 'parameters')
            ? $route->parameters()
            : [];
        unset($parameters['locale']);

        foreach ($locales as $locale) {
            $localizedRouteName = "{$locale}.{$routeNameWithoutLocale}";

            if (! Route::has($localizedRouteName)) {
                continue;
            }

            try {
                $alternates[$locale] = route($localizedRouteName, $parameters);
            } catch (Throwable) {
                // A route with incompatible parameters is not an equivalent page.
            }
        }

        return $alternates;
    }

    /**
     * Generate a localized route URL.
     *
     * @param  string  $name  Route name without locale prefix (e.g. 'apartment', 'map')
     * @param  array  $params  Additional route parameters
     * @param  string|null  $locale  Override locale (defaults to current locale)
     * @return string Full URL
     */
    public static function locale(string $name, array $params = [], ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $routeName = "{$locale}.{$name}";

        return route($routeName, $params);
    }
}
