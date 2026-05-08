<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram Webhook Secret
    |--------------------------------------------------------------------------
    |
    | A secret path segment appended to the webhook URL to prevent
    | unauthorized POST requests. Store it in .env; never commit it.
    |
    */

    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Reminder Lead Days
    |--------------------------------------------------------------------------
    |
    | How many days before check-in / check-out the daily reminder command
    | should send its notification. Both default to 1 day.
    |
    */

    'checkin_lead_days'  => (int) env('TELEGRAM_CHECKIN_LEAD_DAYS', 1),
    'checkout_lead_days' => (int) env('TELEGRAM_CHECKOUT_LEAD_DAYS', 1),

];
