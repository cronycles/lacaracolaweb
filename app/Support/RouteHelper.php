<?php

declare(strict_types=1);

namespace App\Support;

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
     * Generate a localized route URL.
     *
     * @param  string  $name  Route name without locale prefix (e.g. 'apartment', 'map')
     * @param  array   $params  Additional route parameters
     * @param  string|null  $locale  Override locale (defaults to current locale)
     * @return string  Full URL
     */
    public static function locale(string $name, array $params = [], ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $routeName = "{$locale}.{$name}";
        $params['locale'] = $locale;

        return route($routeName, $params);
    }
}

/**
 * Global helper function shortcut.
 */
if (!function_exists('route_locale')) {
    function route_locale(string $name, array $params = [], ?string $locale = null): string {
        return \App\Support\RouteHelper::locale($name, $params, $locale);
    }
}
