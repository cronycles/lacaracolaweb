<?php

declare(strict_types=1);

// Apartment static configuration
// All stable, non-dynamic content that does not need DB storage lives here.
// Dynamic data (availability, pricing, bookings, guests) is in the database.

return [

    // --- Branding ---
    // Public brand name shown across the website.
    'name'    => 'La Caracola',
    // Short marketing sentence used in hero/meta contexts.
    'tagline' => 'A due passi dal mare — Andora, Liguria',
    // Contact email shown in public and used for booking requests.
    'email'   => env('APARTMENT_EMAIL', 'info@lacaracolaandora.com'),
    // Contact phone shown in public pages/footer.
    'phone'   => env('APARTMENT_PHONE', ''),

    // --- Address ---
    'address' => [
        // Street and civic number of the apartment.
        'street'   => 'Via Aurelia 64',
        // City/locality.
        'city'     => 'Andora',
        // Province/area code.
        'province' => 'Savona',
        // Postal code.
        'zip'      => '17051',
        // Region used in SEO text and schema.
        'region'   => 'Liguria',
        // Country name used in schema/address blocks.
        'country'  => 'Italy',
        // Latitude for map pin and schema.org geo data.
        'lat'      => 43.95255349558721,
        // Longitude for map pin and schema.org geo data.
        'lng'      => 8.153381834769661,
    ],

    // --- Property specs ---
    'specs' => [
        // Max bed places.
        'beds'     => 6,
        // Number of bedrooms.
        'bedrooms' => 2,
        // Number of bathrooms.
        'bathrooms' => 1,
        // Total apartment square meters (optional).
        'sqm'      => null,
        // Floor number.
        'floor'    => 1,
        // Whether sea view is available.
        'sea_view' => true,
    ],

    // --- Amenities (icon + translation key) ---
    'amenities' => [
        ['icon' => '🌊', 'key' => 'amenity_sea_view'],
        ['icon' => '🌿', 'key' => 'amenity_garden'],
        ['icon' => '🪑', 'key' => 'amenity_balcony'],
        ['icon' => '📶', 'key' => 'amenity_wifi'],
        ['icon' => '📺', 'key' => 'amenity_tv'],
        ['icon' => '🍳', 'key' => 'amenity_kitchen'],
        ['icon' => '🫙', 'key' => 'amenity_dishwasher'],
        ['icon' => '☕', 'key' => 'amenity_coffee'],
        ['icon' => '🥐', 'key' => 'amenity_toaster'],
        ['icon' => '📡', 'key' => 'amenity_microwave'],
        ['icon' => '🫧', 'key' => 'amenity_mosquito_nets'],
        ['icon' => '🛁', 'key' => 'amenity_bathroom'],
        ['icon' => '🛏️', 'key' => 'amenity_sofa_bed'],
    ],

    // --- Booking defaults (overridden from admin area) ---
    'booking' => [
        // Global minimum nights for public availability request form.
        'min_nights'    => 3,
        // Fixed cleaning fee (EUR) added to the stay estimate.
        'cleaning_fee'  => 100,
        // Optional cutoff date (YYYY-MM-DD). If set, prices are hidden from this date onwards.
        // Examples: null (always show), '2027-01-01' (hide prices for stays on/after 2027-01-01).
        'hide_price_from' => '2027-01-01',
        // Informational check-in time shown to users/operations.
        'checkin_time'  => '15:00',
        // Informational check-out time shown to users/operations.
        'checkout_time' => '10:00',
        // Deposit percentage for commercial reference.
        'deposit_pct'   => 30,
    ],

    // --- INTERHOME integration ---
    'interhome' => [
        // Official Interhome listing code.
        'code'     => 'IT1850.726.1',
        // External Interhome URL for Flow C when used.
        'listing'  => env('INTERHOME_LISTING_URL', ''),
    ],

    // --- External booking platforms (for badge/link display) ---
    'platforms' => [
        // Airbnb listing URL (optional).
        'airbnb'  => env('AIRBNB_LISTING_URL', ''),
        // Booking.com listing URL (optional).
        'booking' => env('BOOKING_LISTING_URL', ''),
    ],

    // Allowed guest countries shown in admin people form (ISO-like code => label).
    'guest_countries' => [
        'IT' => 'Italia',
        'FR' => 'Francia',
        'DE' => 'Germania',
        'ES' => 'Spagna',
        'GB-SCT' => 'Scozia',
        'GB' => 'Gran Bretagna',
        'IE' => 'Irlanda',
        'NL' => 'Paesi Bassi',
        'CH' => 'Svizzera',
    ],

    // --- Images ---
    // Change these paths when real photos are available.
    // All paths are relative to public/ — use asset() helper in views.
    // Set a path to null to fall back to the placeholder URL defined in the view.
    'images' => [
        // Hero slider (home page) — 3 slides
        'hero' => [
            'images/hero-1.jpg',
            'images/hero-2.jpg',
            'images/hero-3.jpg',
        ],
        // Apartment gallery — add or remove items freely
        'gallery' => [
            'images/apartment-1.jpg',
            'images/apartment-2.jpg',
            'images/apartment-3.jpg',
            'images/apartment-4.jpg',
            'images/apartment-5.jpg',
            'images/apartment-6.jpg',
        ],
        // Open Graph / social sharing image
        'og' => 'images/og-default.png',
    ],

    // --- Home page highlight features (icon + lang key) ---
    'features' => [
        ['icon' => '🌊', 'key' => 'feature_sea'],
        ['icon' => '🌿', 'key' => 'feature_garden'],
        ['icon' => '🪑', 'key' => 'feature_balcony'],
        ['icon' => '🛏️', 'key' => 'feature_beds'],
    ],

    // --- House rules (icon + lang keys for title and body text) ---
    'rules' => [
        ['icon' => '🗑️', 'title_key' => 'app.rules_trash_title',    'text_key' => 'app.rules_trash_text'],
        ['icon' => '🌡️', 'title_key' => 'app.rules_heating_title',  'text_key' => 'app.rules_heating_text'],
        ['icon' => '🌙', 'title_key' => 'app.rules_quiet_title',    'text_key' => 'app.rules_quiet_text'],
        ['icon' => '🔑', 'title_key' => 'app.rules_checkout_title', 'text_key' => 'app.rules_checkout_text'],
        ['icon' => '🔐', 'title_key' => 'app.rules_keys_title',     'text_key' => 'app.rules_keys_text'],
    ],

    // --- Useful nearby places (supermarkets / restaurants) ---
    'useful_places' => [
        'supermarkets' => [
            ['name' => 'Conad Andora',    'address' => 'Via Aurelia',         'distance' => '5 min a piedi'],
            ['name' => 'Esselunga',       'address' => 'Via Aurelia, Albenga','distance' => '15 min in auto'],
            ['name' => 'CRAI Andora',     'address' => 'Via Roma',            'distance' => '3 min a piedi', 'note_key' => 'places_crai_note'],
        ],
        'restaurants' => [
            ['name' => 'Trattoria La Salsa',   'address' => 'Via Aurelia',         'distance' => '5 min a piedi', 'desc_key' => 'places_salsa_desc'],
            ['name' => 'Pizzeria Da Mario',    'address' => 'Lungomare Andora',    'distance' => '8 min a piedi', 'desc_key' => 'places_pizza_desc'],
            ['name' => 'Bar Singlefin',        'address' => 'Lungomare Andora',    'distance' => '5 min a piedi', 'desc_key' => 'places_singlefin_desc'],
            ['name' => 'Centottanta',          'address' => 'Via Roma',             'distance' => '4 min a piedi', 'desc_key' => 'places_cento90_desc'],
            ['name' => 'Gelateria del Porto',  'address' => 'Porto Turistico',     'distance' => '10 min a piedi', 'desc_key' => 'places_gelato_desc', 'note_key' => 'places_gelato_note'],
        ],
    ],

    // --- SEO defaults per locale (override in lang files for more control) ---
    'seo' => [
        'it' => [
            'title'       => 'La Caracola | Appartamento vacanza ad Andora — Due passi dal mare',
            'description' => 'Affitta La Caracola ad Andora, Savona. Appartamento sul mare in Liguria: 2 camere, 6 posti letto, balcone con vista mare e giardino. Soggiorno minimo 3 notti.',
        ],
        'en' => [
            'title'       => 'La Caracola | Holiday apartment in Andora — Steps from the sea',
            'description' => 'Rent La Caracola in Andora, Liguria. Seaside apartment: 2 bedrooms, 6 guests, sea-view balcony and garden. Minimum stay 3 nights.',
        ],
        'fr' => [
            'title'       => 'La Caracola | Appartement de vacances à Andora — À deux pas de la mer',
            'description' => 'Louez La Caracola à Andora, Ligurie. Appartement bord de mer : 2 chambres, 6 personnes, balcon vue mer et jardin. Séjour minimum 3 nuits.',
        ],
        'de' => [
            'title'       => 'La Caracola | Ferienwohnung in Andora — Direkt am Meer',
            'description' => 'Mieten Sie La Caracola in Andora, Ligurien. Meerblick-Wohnung: 2 Schlafzimmer, 6 Personen, Balkon mit Meerblick und Garten. Mindestaufenthalt 3 Nächte.',
        ],
    ],

];
