<?php

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\ApartmentController;
use App\Http\Controllers\Public\MapController;
use App\Http\Controllers\Public\ExperiencesController;
use App\Http\Controllers\Public\ReviewsController;
use App\Http\Controllers\Public\RulesController;
use App\Http\Controllers\Public\TermsController;
use App\Http\Controllers\Public\UsefulPlacesController;
use App\Http\Controllers\Public\BookingController;
use App\Http\Controllers\Public\CheckinController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\LegacyRedirectController;
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
        Route::get('/' . $slug['terms'], [TermsController::class, 'index'])->name('terms');
        Route::get('/' . $slug['useful_places'], [UsefulPlacesController::class, 'index'])->name('useful-places');

        // Contact form
        Route::get('/' . $slug['contact'], [ContactController::class, 'index'])->name('contact');
        Route::post('/' . $slug['contact'], [ContactController::class, 'send'])->name('contact.send');

        // Booking availability request (flow B)
        Route::post('/' . $slug['availability'], [BookingController::class, 'requestAvailability'])->name('booking.request');
        Route::post('/' . $slug['availability'] . '/quote', [BookingController::class, 'quote'])->name('booking.quote');
        Route::get('/' . $slug['thanks'], [BookingController::class, 'thanks'])->name('booking.thanks');
    });
}

// --- Public online check-in (token-based, not locale-prefixed) ---
Route::get('/check-in/{token}', [CheckinController::class, 'show'])->name('checkin.show');
Route::post('/check-in/{token}/companions', [CheckinController::class, 'addCompanion'])->name('checkin.companions.store');
Route::post('/check-in/{token}/confirm', [CheckinController::class, 'confirm'])->name('checkin.confirm');

// --- Redirect old URLs without locale prefix (for SEO and backward compatibility) ---
Route::get('/appartamento', [LegacyRedirectController::class, 'appartamento']);
Route::get('/dove-siamo', [LegacyRedirectController::class, 'doveSiamo']);
Route::get('/esperienze', [LegacyRedirectController::class, 'esperienze']);
Route::get('/recensioni', [LegacyRedirectController::class, 'recensioni']);
Route::get('/regole-casa', [LegacyRedirectController::class, 'regoleCasa']);
Route::get('/posti-utili', [LegacyRedirectController::class, 'postiUtili']);
Route::get('/disponibilita', [LegacyRedirectController::class, 'disponibilita']);

Route::get('/contattaci', [LegacyRedirectController::class, 'contattaci']);

// --- Legacy redirects for the renamed booking URL slug (formerly "disponibilita"/"availability") ---
Route::get('/it/disponibilita/grazie', [LegacyRedirectController::class, 'bookingThanksItLegacy']);
Route::get('/en/availability/thank-you', [LegacyRedirectController::class, 'bookingThanksEnLegacy']);
Route::get('/fr/disponibilite/merci', [LegacyRedirectController::class, 'bookingThanksFrLegacy']);
Route::get('/de/verfugbarkeit/danke', [LegacyRedirectController::class, 'bookingThanksDeLegacy']);

// --- Fallback: redirect root to localized home ---
Route::get('/', [LegacyRedirectController::class, 'home']);
