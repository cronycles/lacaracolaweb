<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Parse booking emails from IMAP inbox every hour.
// Requires the cPanel cron: * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
Schedule::command('emails:parse-bookings')->hourly()->withoutOverlapping();
