<?php

declare(strict_types=1);

/*
 * Categorie per le voci di contabilità.
 *
 * Usate nel form di inserimento/modifica, nella validazione del controller
 * e in futuro per statistiche, grafici e filtri per categoria.
 *
 * Formato: 'chiave_db' => 'Etichetta visualizzata'
 * La chiave viene salvata nel database; l'etichetta è solo per la UI.
 */

return [
    'categories' => [
        'pulizie'        => 'Pulizie / Biancheria',
        'utenze'         => 'Utenze (acqua, luce, gas, internet, hosting)',
        'manutenzione'   => 'Manutenzione / Arredamento / Assistenza',
        'amministrazione'  => 'Amministrazione',
        'assicurazione'  => 'Assicurazione',
        'tasse'          => 'Tasse',
        'ingresso'       => 'Ingresso Puntuale',
        'altro'          => 'Altro',
    ],

    /*
     * Default values for the "Dichiarazione dei redditi" flags on new bookings.
     * income, cleaning, linen default to true (typically declared on tax return).
     * parking defaults to false (usually not declared as rental income).
     * Override any value here to change the default for all future bookings.
     */
    'tax_declaration_defaults' => [
        'income'   => true,
        'cleaning' => true,
        'linen'    => true,
        'parking'  => false,
    ],
];
