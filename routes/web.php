<?php

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\ApartmentController;
use App\Http\Controllers\Public\MapController;
use App\Http\Controllers\Public\ExperiencesController;
use App\Http\Controllers\Public\ReviewsController;
use App\Http\Controllers\Public\RulesController;
use App\Http\Controllers\Public\UsefulPlacesController;
use App\Http\Controllers\Public\BookingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Admin\LoginController;
use Illuminate\Support\Facades\Route;

// --- Admin auth (no middleware guard — these are the login/logout endpoints) ---
Route::get('/admin/login', [LoginController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --- Locale switch ---
Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');
Route::get('/locale', [LocaleController::class, 'set'])->name('locale.set');

// --- Map of URL slugs per locale ---
$routeConfig = config('routes');
$locales = $routeConfig['locales'];
$slugs = $routeConfig['slugs'];

// --- Public routes with locale prefix (IT/EN/FR/DE) ---
// Generate routes for each locale with locale-specific URL slugs
foreach ($locales as $locale) {
    Route::prefix($locale)->name("{$locale}.")->group(function () use ($locale, $slugs) {
        $slug = $slugs[$locale];

        Route::get('/' . $slug['home'], [HomeController::class, 'index'])->name('home');
        Route::get('/' . $slug['apartment'], [ApartmentController::class, 'index'])->name('apartment');
        Route::get('/' . $slug['map'], [MapController::class, 'index'])->name('map');
        Route::get('/' . $slug['experiences'], [ExperiencesController::class, 'index'])->name('experiences');
        Route::get('/' . $slug['reviews'], [ReviewsController::class, 'index'])->name('reviews');
        Route::get('/' . $slug['rules'], [RulesController::class, 'index'])->name('rules');
        Route::get('/' . $slug['useful_places'], [UsefulPlacesController::class, 'index'])->name('useful-places');

        // Booking availability request (flow B)
        Route::post('/' . $slug['availability'], [BookingController::class, 'requestAvailability'])->name('booking.request');
        Route::post('/' . $slug['availability'] . '/quote', [BookingController::class, 'quote'])->name('booking.quote');
        Route::get('/' . $slug['thanks'], [BookingController::class, 'thanks'])->name('booking.thanks');
    });
}

// --- Redirect old URLs without locale prefix (for SEO and backward compatibility) ---
// When a user accesses the old URL, redirect to the localized URL with their preferred locale
Route::redirect('/appartamento', function () {
    $locale = session('locale', 'it');
    return redirect(route_locale('apartment', [], $locale), 301);
});
Route::redirect('/dove-siamo', function () {
    $locale = session('locale', 'it');
    return redirect(route_locale('map', [], $locale), 301);
});
Route::redirect('/esperienze', function () {
    $locale = session('locale', 'it');
    return redirect(route_locale('experiences', [], $locale), 301);
});
Route::redirect('/recensioni', function () {
    $locale = session('locale', 'it');
    return redirect(route_locale('reviews', [], $locale), 301);
});
Route::redirect('/regole-casa', function () {
    $locale = session('locale', 'it');
    return redirect(route_locale('rules', [], $locale), 301);
});
Route::redirect('/posti-utili', function () {
    $locale = session('locale', 'it');
    return redirect(route_locale('useful-places', [], $locale), 301);
});
Route::redirect('/disponibilita', function () {
    $locale = session('locale', 'it');
    return redirect(route_locale('booking.thanks', [], $locale), 301);
}, 301);

// --- Fallback: redirect root to localized home ---
Route::get('/', function () {
    $locale = session('locale', 'it');
    return redirect(route_locale('home', [], $locale), 307);
});
