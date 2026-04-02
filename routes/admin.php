<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\InterhomePdfImportController;
use App\Http\Controllers\Admin\PersonController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\SettingsController;
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
    Route::get('/prenotazioni/import-pdf', [InterhomePdfImportController::class, 'index'])->name('bookings.import-pdf');
    Route::post('/prenotazioni/import-pdf/preview', [InterhomePdfImportController::class, 'preview'])->name('bookings.import-pdf.preview');
    Route::post('/prenotazioni/import-pdf/confirm', [InterhomePdfImportController::class, 'confirm'])->name('bookings.import-pdf.confirm');
    Route::resource('prenotazioni', BookingController::class)->names('bookings');

    // People / guests
    Route::resource('ospiti', PersonController::class)->names('people');
    Route::get('/ospiti/{person}/soggiorni', [PersonController::class, 'stays'])->name('people.stays');

    // Newsletter subscribers
    Route::get('/newsletter', [NewsletterController::class, 'index'])->name('newsletter');
    Route::patch('/newsletter/{person}/toggle', [NewsletterController::class, 'toggle'])->name('newsletter.toggle');

    // Settings (booking mode switch)
    Route::get('/impostazioni', [SettingsController::class, 'index'])->name('settings');
    Route::put('/impostazioni', [SettingsController::class, 'update'])->name('settings.update');

    // Account security (password change)
    Route::get('/impostazioni/sicurezza', [SettingsController::class, 'accountSecurity'])->name('account-security');
    Route::post('/impostazioni/sicurezza/password', [SettingsController::class, 'updatePassword'])->name('account-security.update-password');

});
