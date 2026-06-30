<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Guest Reporting Driver
    |--------------------------------------------------------------------------
    | This key identifies which driver is active. To swap drivers, change
    | GUEST_REPORTING_DRIVER in .env — no application code changes needed.
    | See openspec/changes/guest-reporting/design.md § "How to Add a New Driver".
    */
    'default' => env('GUEST_REPORTING_DRIVER', 'polizia_stato'),

    /*
    |--------------------------------------------------------------------------
    | Driver Configurations
    |--------------------------------------------------------------------------
    | Each driver has its own credential block. The manager passes only the
    | relevant block to the instantiated driver.
    */
    'drivers' => [
        'polizia_stato' => [
            // Credentials for alloggiatiweb.poliziadistato.it
            'utente'           => env('GUEST_REPORTING_UTENTE'),
            'password'         => env('GUEST_REPORTING_PASSWORD'),
            'ws_key'           => env('GUEST_REPORTING_WS_KEY'),
            // If set, uses GestioneAppartamenti_* SOAP methods and Tabella 2 (176-char) record format.
            // Leave empty/null for single-structure mode (Tabella 1, 170-char format).
            'id_appartamento'  => env('GUEST_REPORTING_ID_APPARTAMENTO'),
        ],
    ],
];
