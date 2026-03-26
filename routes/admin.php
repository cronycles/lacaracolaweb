<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\PersonController;
use App\Http\Controllers\Admin\NewsletterController;
use Illuminate\Support\Facades\Route;

// Admin area — protected by auth middleware
// URL prefix /admin, no visible link on public site
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Calendar & availability
    Route::get('/calendario', [CalendarController::class, 'index'])->name('calendar');
    Route::post('/blocchi', [CalendarController::class, 'storeBlock'])->name('calendar.block');
    Route::delete('/blocchi/{block}', [CalendarController::class, 'destroyBlock'])->name('calendar.block.destroy');

    // Pricing rules
    Route::resource('prezzi', PricingController::class)->names('pricing');

    // Bookings
    Route::resource('prenotazioni', BookingController::class)->names('bookings');

    // People / guests
    Route::resource('ospiti', PersonController::class)->names('people');
    Route::get('/ospiti/{person}/soggiorni', [PersonController::class, 'stays'])->name('people.stays');

    // Newsletter subscribers
    Route::get('/newsletter', [NewsletterController::class, 'index'])->name('newsletter');
    Route::patch('/newsletter/{person}/toggle', [NewsletterController::class, 'toggle'])->name('newsletter.toggle');
});
