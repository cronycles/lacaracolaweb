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
            'terms'          => 'condizioni-generali-prenotazione',
            'useful_places'  => 'posti-utili',
            'availability'   => 'disponibilita',
            'thanks'         => 'disponibilita/grazie',
            'contact'        => 'contattaci',
        ],
        'en' => [
            'home'           => '',
            'apartment'      => 'apartment',
            'map'            => 'location',
            'experiences'    => 'experiences',
            'reviews'        => 'reviews',
            'rules'          => 'house-rules',
            'terms'          => 'terms-and-conditions',
            'useful_places'  => 'useful-places',
            'availability'   => 'availability',
            'thanks'         => 'availability/thank-you',
            'contact'        => 'contact',
        ],
        'fr' => [
            'home'           => '',
            'apartment'      => 'appartement',
            'map'            => 'localisation',
            'experiences'    => 'experiences',
            'reviews'        => 'avis',
            'rules'          => 'regles-maison',
            'terms'          => 'conditions-generales',
            'useful_places'  => 'lieux-utiles',
            'availability'   => 'disponibilite',
            'thanks'         => 'disponibilite/merci',
            'contact'        => 'contactez-nous',
        ],
        'de' => [
            'home'           => '',
            'apartment'      => 'wohnung',
            'map'            => 'lage',
            'experiences'    => 'erlebnisse',
            'reviews'        => 'bewertungen',
            'rules'          => 'hausregeln',
            'terms'          => 'allgemeine-geschaeftsbedingungen',
            'useful_places'  => 'nutzliche-orte',
            'availability'   => 'verfugbarkeit',
            'thanks'         => 'verfugbarkeit/danke',
            'contact'        => 'kontakt',
        ],
    ],
];
