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
    | if the values are 0 the reminders will not be sent at all.
    |
    */

    'checkin_lead_days' => 1,
    'checkout_lead_days' => 1,

];
