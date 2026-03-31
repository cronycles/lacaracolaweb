<?php

if (!function_exists('route_locale')) {
    function route_locale(string $name, array $params = [], ?string $locale = null): string
    {
        return \App\Support\RouteHelper::locale($name, $params, $locale);
    }
}
