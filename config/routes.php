<?php

declare(strict_types=1);

/**
 * Multilingual route slug mapping.
 * Each locale has its own URL slugs for SEO.
 */
return [
    'locales' => ['it', 'en', 'fr', 'de'],
    'fallback' => 'it',

    'slugs' => [
        'it' => [
            'home'           => '',
            'apartment'      => 'appartamento',
            'map'            => 'dove-siamo',
            'experiences'    => 'esperienze',
            'reviews'        => 'recensioni',
            'rules'          => 'regole-casa',
            'useful_places'  => 'posti-utili',
            'availability'   => 'disponibilita',
            'thanks'         => 'disponibilita/grazie',
        ],
        'en' => [
            'home'           => '',
            'apartment'      => 'apartment',
            'map'            => 'location',
            'experiences'    => 'experiences',
            'reviews'        => 'reviews',
            'rules'          => 'house-rules',
            'useful_places'  => 'useful-places',
            'availability'   => 'availability',
            'thanks'         => 'availability/thank-you',
        ],
        'fr' => [
            'home'           => '',
            'apartment'      => 'appartement',
            'map'            => 'localisation',
            'experiences'    => 'experiences',
            'reviews'        => 'avis',
            'rules'          => 'regles-maison',
            'useful_places'  => 'lieux-utiles',
            'availability'   => 'disponibilite',
            'thanks'         => 'disponibilite/merci',
        ],
        'de' => [
            'home'           => '',
            'apartment'      => 'wohnung',
            'map'            => 'lage',
            'experiences'    => 'erlebnisse',
            'reviews'        => 'bewertungen',
            'rules'          => 'hausregeln',
            'useful_places'  => 'nutzliche-orte',
            'availability'   => 'verfugbarkeit',
            'thanks'         => 'verfugbarkeit/danke',
        ],
    ],
];
