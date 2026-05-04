<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Load admin routes separately (auth-protected)
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apply locale detection (from route prefix or session/header) to all web requests
        $middleware->web(append: [
            \App\Http\Middleware\ResolveLocaleFromRoute::class,
        ]);

        // Unauthenticated users on admin routes => redirect to admin login
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        // Permission-based authorization for admin route groups
        $middleware->alias([
            'permission' => \App\Http\Middleware\RequirePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
