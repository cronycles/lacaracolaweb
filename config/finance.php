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
        'affitto'        => 'Affitto',
        'caparra'        => 'Caparra / Deposito',
        'pulizie'        => 'Pulizie',
        'utenze'         => 'Utenze',
        'manutenzione'   => 'Manutenzione',
        'arredamento'    => 'Arredamento / Acquisti',
        'assicurazione'  => 'Assicurazione',
        'tasse'          => 'Tasse / Imposte',
        'commissioni'    => 'Commissioni piattaforme',
        'forniture'      => 'Forniture / Materiali',
        'servizi_extra'  => 'Servizi extra',
        'rimborso'       => 'Rimborso',
        'altro'          => 'Altro',
    ],
];
