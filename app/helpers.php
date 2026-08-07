<?php

if (!function_exists('route_locale')) {
    function route_locale(string $name, array $params = [], ?string $locale = null): string
    {
        return \App\Support\RouteHelper::locale($name, $params, $locale);
    }
}

if (!function_exists('sql_year_expr')) {
    /**
     * Cross-driver SQL expression extracting the year from a date/datetime
     * column or expression. MySQL/Postgres support `YEAR(...)` directly;
     * SQLite (used for local/CI testing) has no `YEAR()` function and needs
     * `strftime('%Y', ...)` cast to an integer instead.
     */
    function sql_year_expr(string $column): string
    {
        return \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', {$column}) AS INTEGER)"
            : "YEAR({$column})";
    }
}
