<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\StayDiscountRuleController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\InterhomePdfImportController;
use App\Http\Controllers\Admin\PersonController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\FinancialEntryController;
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
    Route::post('/prezzi/simulazione', [PricingController::class, 'simulate'])->name('pricing.simulate');
    Route::post('/prezzi/variazione-bulk', [PricingController::class, 'bulkAdjust'])->name('pricing.bulk-adjust');
    Route::resource('prezzi', PricingController::class)->names('pricing');
    Route::resource('sconti-soggiorno', StayDiscountRuleController::class)
        ->parameters(['sconti-soggiorno' => 'stay_discount_rule'])
        ->names('stay-discounts');

    // Bookings
    Route::get('/prenotazioni/import-pdf', [InterhomePdfImportController::class, 'index'])->name('bookings.import-pdf');
    Route::post('/prenotazioni/import-pdf/preview', [InterhomePdfImportController::class, 'preview'])->name('bookings.import-pdf.preview');
    Route::post('/prenotazioni/import-pdf/confirm', [InterhomePdfImportController::class, 'confirm'])->name('bookings.import-pdf.confirm');
    Route::patch('/prenotazioni/{prenotazioni}/annulla', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::patch('/prenotazioni/{prenotazioni}/ripristina', [BookingController::class, 'restore'])->name('bookings.restore');
    
    // Personal blocks (owner/maintenance)
    Route::get('/prenotazioni/blocco/{block}', [BookingController::class, 'showBlock'])->name('bookings.show-block');
    Route::get('/prenotazioni/blocco/{block}/edit', [BookingController::class, 'editBlock'])->name('bookings.edit-block');
    Route::put('/prenotazioni/blocco/{block}', [BookingController::class, 'updateBlock'])->name('bookings.update-block');
    Route::delete('/prenotazioni/blocco/{block}', [BookingController::class, 'destroyBlock'])->name('bookings.destroy-block');
    
    Route::resource('prenotazioni', BookingController::class)->names('bookings');

    // People / guests
    Route::resource('ospiti', PersonController::class)->names('people');
    Route::get('/ospiti/{person}/soggiorni', [PersonController::class, 'stays'])->name('people.stays');

    // Newsletter subscribers
    Route::get('/newsletter', [NewsletterController::class, 'index'])->name('newsletter');
    Route::patch('/newsletter/{person}/toggle', [NewsletterController::class, 'toggle'])->name('newsletter.toggle');

    // Finance / accounting
    Route::resource('contabilita', FinancialEntryController::class)
        ->parameters(['contabilita' => 'entry'])
        ->names('finance');

    // Settings (booking mode switch)
    Route::get('/impostazioni', [SettingsController::class, 'index'])->name('settings');
    Route::put('/impostazioni', [SettingsController::class, 'update'])->name('settings.update');

    // Account security (password change)
    Route::get('/impostazioni/sicurezza', [SettingsController::class, 'accountSecurity'])->name('account-security');
    Route::post('/impostazioni/sicurezza/password', [SettingsController::class, 'updatePassword'])->name('account-security.update-password');

});
