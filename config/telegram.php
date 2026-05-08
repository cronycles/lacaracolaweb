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
    | should send its notification. Change these values directly here.
    |
    */

    'checkin_lead_days'  => 1,
    'checkout_lead_days' => 1,

];
