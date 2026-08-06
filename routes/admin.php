<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\BookingGuestController;
use App\Http\Controllers\Admin\BookingRequestController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinancialAttachmentController;
use App\Http\Controllers\Admin\FinancialEntryController;
use App\Http\Controllers\Admin\GuestReportingController;
use App\Http\Controllers\Admin\InterhomePdfImportController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\PersonController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StayDiscountRuleController;
use App\Http\Controllers\Admin\TaxDeclarationController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Admin area — protected by auth middleware
// URL prefix /admin, no visible link on public site
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // ── Accessible by all authenticated users ────────────────────────────────

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Calendar — read-only view
    Route::get('/calendario', [CalendarController::class, 'index'])->name('calendar');

    // Account security — available to all (password change)
    Route::get('/impostazioni/sicurezza', [SettingsController::class, 'accountSecurity'])->name('account-security');
    Route::post('/impostazioni/sicurezza/password', [SettingsController::class, 'updatePassword'])->name('account-security.update-password');

    // ── manage_bookings ──────────────────────────────────────────────────────
    // Specific booking routes must be registered BEFORE the {prenotazioni} wildcard

    Route::middleware('permission:import_pdf')->group(function () {
        // PDF import (must be before {prenotazioni} wildcard)
        Route::get('/prenotazioni/import-pdf', [InterhomePdfImportController::class, 'index'])->name('bookings.import-pdf');
        Route::post('/prenotazioni/import-pdf/preview', [InterhomePdfImportController::class, 'preview'])->name('bookings.import-pdf.preview');
        Route::post('/prenotazioni/import-pdf/confirm', [InterhomePdfImportController::class, 'confirm'])->name('bookings.import-pdf.confirm');
    });

    Route::middleware('permission:manage_bookings')->group(function () {
        // Create (must be before {prenotazioni} wildcard)
        Route::get('/prenotazioni/create', [BookingController::class, 'create'])->name('bookings.create');
        Route::post('/prenotazioni', [BookingController::class, 'store'])->name('bookings.store');

        // ── Pending booking requests queue ───────────────────────────────────
        Route::get('/richieste', [BookingRequestController::class, 'index'])->name('booking-requests.index');
        Route::post('/richieste/{bookingRequest}/conferma', [BookingRequestController::class, 'confirm'])->name('booking-requests.confirm');
        Route::post('/richieste/{bookingRequest}/rifiuta', [BookingRequestController::class, 'decline'])->name('booking-requests.decline');
        Route::delete('/richieste/{bookingRequest}', [BookingRequestController::class, 'destroy'])->name('booking-requests.destroy');
    });

    // ── manage_calendar ──────────────────────────────────────────────────────
    // Personal block write routes — specific paths before wildcards

    Route::middleware('permission:manage_calendar')->group(function () {
        Route::post('/blocchi', [CalendarController::class, 'storeBlock'])->name('calendar.block');
        Route::delete('/blocchi/{block}', [CalendarController::class, 'destroyBlock'])->name('calendar.block.destroy');

        // Specific block routes before {prenotazioni} wildcard
        Route::get('/prenotazioni/blocco/{block}/edit', [BookingController::class, 'editBlock'])->name('bookings.edit-block');
        Route::put('/prenotazioni/blocco/{block}', [BookingController::class, 'updateBlock'])->name('bookings.update-block');
        Route::delete('/prenotazioni/blocco/{block}', [BookingController::class, 'destroyBlock'])->name('bookings.destroy-block');
    });

    // Bookings — read-only (list + detail) — open to all authenticated
    Route::get('/prenotazioni', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/prenotazioni/blocco/{block}', [BookingController::class, 'showBlock'])->name('bookings.show-block');
    Route::get('/prenotazioni/{prenotazioni}', [BookingController::class, 'show'])->name('bookings.show');

    // Booking write actions requiring manage_bookings
    Route::middleware('permission:manage_bookings')->group(function () {
        Route::patch('/prenotazioni/{prenotazioni}/annulla', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::patch('/prenotazioni/{prenotazioni}/ripristina', [BookingController::class, 'restore'])->name('bookings.restore');
        Route::post('/prenotazioni/{prenotazioni}/notify-telegram', [BookingController::class, 'notifyTelegram'])->name('bookings.notify-telegram');
        Route::post('/prenotazioni/{prenotazioni}/send-confirmation', [BookingController::class, 'sendConfirmationEmail'])->name('bookings.send-confirmation');
        Route::get('/prenotazioni/{prenotazioni}/edit', [BookingController::class, 'edit'])->name('bookings.edit');
        Route::put('/prenotazioni/{prenotazioni}', [BookingController::class, 'update'])->name('bookings.update');
        Route::patch('/prenotazioni/{prenotazioni}', [BookingController::class, 'update']);
        Route::delete('/prenotazioni/{prenotazioni}', [BookingController::class, 'destroy'])->name('bookings.destroy');

        // ── Guest Reporting (Segnalazione Ospiti) ────────────────────────────
        // History must be before {prenotazioni} wildcard
        Route::get('/guest-reporting', [GuestReportingController::class, 'index'])->name('guest-reporting.index');
        Route::get('/prenotazioni/{prenotazioni}/guest-reporting', [GuestReportingController::class, 'show'])->name('guest-reporting.show');
        Route::post('/prenotazioni/{prenotazioni}/guest-reporting/test', [GuestReportingController::class, 'saveAndTest'])->name('guest-reporting.test');
        Route::post('/prenotazioni/{prenotazioni}/guest-reporting/send', [GuestReportingController::class, 'saveAndSend'])->name('guest-reporting.send');
        Route::delete('/prenotazioni/{prenotazioni}/guest-reporting/reports', [GuestReportingController::class, 'destroyReports'])->name('guest-reporting.reports.destroy');

        // ── Booking additional guests (pivot) ────────────────────────────────
        Route::post('/prenotazioni/{prenotazioni}/ospiti', [BookingGuestController::class, 'store'])->name('bookings.guests.store');
        Route::delete('/prenotazioni/{prenotazioni}/ospiti/{person}', [BookingGuestController::class, 'destroy'])->name('bookings.guests.destroy');
    });

    // ── manage_people ────────────────────────────────────────────────────────
    // Specific people routes before {ospiti} wildcard

    Route::middleware('permission:manage_people')->group(function () {
        Route::get('/ospiti/create', [PersonController::class, 'create'])->name('people.create');
        Route::post('/ospiti', [PersonController::class, 'store'])->name('people.store');
    });

    // Ospiti — read-only (list + detail + stays) — open to all authenticated
    Route::get('/ospiti', [PersonController::class, 'index'])->name('people.index');
    Route::get('/ospiti/{ospiti}', [PersonController::class, 'show'])->name('people.show');
    Route::get('/ospiti/{person}/soggiorni', [PersonController::class, 'stays'])->name('people.stays');

    // People write actions
    Route::middleware('permission:manage_people')->group(function () {
        Route::get('/ospiti/{ospiti}/edit', [PersonController::class, 'edit'])->name('people.edit');
        Route::put('/ospiti/{ospiti}', [PersonController::class, 'update'])->name('people.update');
        Route::patch('/ospiti/{ospiti}', [PersonController::class, 'update']);
        Route::delete('/ospiti/{ospiti}', [PersonController::class, 'destroy'])->name('people.destroy');
    });

    // ── view_accounting ──────────────────────────────────────────────────────

    Route::middleware('permission:view_accounting')->group(function () {
        Route::resource('contabilita', FinancialEntryController::class)
            ->parameters(['contabilita' => 'entry'])
            ->names('finance');
        Route::get('/dichiarazione-redditi', [TaxDeclarationController::class, 'index'])->name('tax-declaration.index');

        // Attachment routes (upload/download/delete for FinancialEntry and Booking)
        Route::post('/allegati/{type}/{id}', [FinancialAttachmentController::class, 'store'])
            ->where('type', 'entry|booking')
            ->name('finance.attachments.store');
        Route::get('/allegati/{attachment}/download', [FinancialAttachmentController::class, 'download'])
            ->name('finance.attachments.download');
        Route::delete('/allegati/{attachment}', [FinancialAttachmentController::class, 'destroy'])
            ->name('finance.attachments.destroy');
    });

    // ── manage_pricing ───────────────────────────────────────────────────────

    Route::middleware('permission:manage_pricing')->group(function () {
        Route::post('/prezzi/simulazione', [PricingController::class, 'simulate'])->name('pricing.simulate');
        Route::post('/prezzi/variazione-bulk', [PricingController::class, 'bulkAdjust'])->name('pricing.bulk-adjust');
        Route::resource('prezzi', PricingController::class)->names('pricing');
        Route::resource('sconti-soggiorno', StayDiscountRuleController::class)
            ->parameters(['sconti-soggiorno' => 'stay_discount_rule'])
            ->names('stay-discounts');
    });

    // ── manage_settings ──────────────────────────────────────────────────────

    Route::middleware('permission:manage_settings')->group(function () {
        Route::get('/impostazioni', [SettingsController::class, 'index'])->name('settings');
        Route::put('/impostazioni', [SettingsController::class, 'update'])->name('settings.update');
    });

    // ── manage_newsletter ────────────────────────────────────────────────────

    Route::middleware('permission:manage_newsletter')->group(function () {
        Route::get('/newsletter', [NewsletterController::class, 'index'])->name('newsletter');
        Route::patch('/newsletter/{person}/toggle', [NewsletterController::class, 'toggle'])->name('newsletter.toggle');
    });

    // ── manage_users ─────────────────────────────────────────────────────────

    Route::middleware('permission:manage_users')->group(function () {
        Route::resource('utenti', UserController::class)->names('users');
    });

    // ── manage_reviews ───────────────────────────────────────────────────────

    Route::middleware('permission:manage_reviews')->group(function () {
        Route::get('/recensioni', [ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/recensioni/prenotazione/{booking}/crea', [ReviewController::class, 'create'])->name('reviews.create');
        Route::post('/recensioni/prenotazione/{booking}', [ReviewController::class, 'store'])->name('reviews.store');
        Route::get('/recensioni/{review}/modifica', [ReviewController::class, 'edit'])->name('reviews.edit');
        Route::put('/recensioni/{review}', [ReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/recensioni/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    });
});

