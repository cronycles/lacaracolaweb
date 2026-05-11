<?php

use App\Console\Commands\SendTelegramBookingReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SendTelegramBookingReminders::class, ['--type' => 'checkin'])
    ->dailyAt(config('apartment.booking.checkin_time'));

Schedule::command(SendTelegramBookingReminders::class, ['--type' => 'checkout'])
    ->dailyAt(config('apartment.booking.checkout_time'));
