<?php

use App\Http\Controllers\CalendarExportController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/calendar/export', CalendarExportController::class)->name('calendar.export');
Route::post('/telegram/webhook/{secret}', [TelegramWebhookController::class, 'handle']);
