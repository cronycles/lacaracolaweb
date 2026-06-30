<?php

namespace App\Providers;

use App\Contracts\GuestReportingDriverInterface;
use App\Services\GuestReporting\GuestReportingManager;
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
        //
    }
}
