<?php

namespace App\Providers;

use App\Contracts\GuestReportingDriverInterface;
use App\Services\GuestReporting\GuestReportingManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            GuestReportingDriverInterface::class,
            GuestReportingManager::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The default Tailwind pagination view relies on utility classes that
        // aren't compiled for the admin panel's plain-CSS stylesheet.
        Paginator::defaultView('vendor.pagination.admin');
        Paginator::defaultSimpleView('vendor.pagination.admin');
    }
}
