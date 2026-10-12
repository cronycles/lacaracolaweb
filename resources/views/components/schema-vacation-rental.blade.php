@php
    $schemaAmenities = [
        'amenity_sea_view' => 'seaView',
        'amenity_garden' => 'garden',
        'amenity_balcony' => 'balcony',
        'amenity_wifi' => 'wifi',
        'amenity_tv' => 'tv',
        'amenity_kitchen' => 'kitchen',
        'amenity_dishwasher' => 'dishwasher',
        'amenity_coffee' => 'coffeeMaker',
        'amenity_kettle' => 'kettle',
        'amenity_toaster' => 'toaster',
        'amenity_microwave' => 'microwave',
        'amenity_mosquito_nets' => 'mosquitoNets',
        'amenity_bathroom' => 'ensuiteBathroom',
        'amenity_washing_machine' => 'washingMachine',
        'amenity_pets_allowed' => 'petsAllowed',
        'amenity_private_parking_on_request' => 'parking',
    ];

    $amenities = collect($apartment['amenities'])
        ->filter(fn (array $amenity): bool => isset($schemaAmenities[$amenity['key']]))
        ->map(fn (array $amenity): array => [
            '@type' => 'LocationFeatureSpecification',
            'name' => $schemaAmenities[$amenity['key']],
            'value' => true,
        ])
        ->values()
        ->all();

    $beds = collect($apartment['specs']['beds_detail'])
        ->map(fn (array $bed): array => [
            '@type' => 'BedDetails',
            'numberOfBeds' => $bed['number'],
            'typeOfBed' => $bed['type'],
        ])
        ->all();

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'VacationRental',
        '@id' => route_locale('home') . '#vacation-rental',
        'identifier' => $apartment['schema']['identifier'],
        'additionalType' => 'Apartment',
        'name' => $apartment['name'],
        'description' => config('apartment.seo.' . app()->getLocale() . '.description'),
        'url' => route_locale('home'),
        'mainEntityOfPage' => route_locale('home'),
        'image' => collect($apartment['images']['gallery'])
            ->filter(fn (string $path): bool => file_exists(public_path($path)))
            ->map(fn (string $path): string => asset($path))
            ->values()
            ->all(),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $apartment['address']['street'],
            'addressLocality' => $apartment['address']['city'],
            'addressRegion' => $apartment['address']['province'],
            'postalCode' => $apartment['address']['zip'],
            'addressCountry' => 'IT',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => $apartment['address']['lat'],
            'longitude' => $apartment['address']['lng'],
        ],
        'containsPlace' => [
            '@type' => 'Accommodation',
            'additionalType' => 'EntirePlace',
            'occupancy' => [
                '@type' => 'QuantitativeValue',
                'value' => $apartment['specs']['beds'],
            ],
            'bed' => $beds,
            'floorSize' => [
                '@type' => 'QuantitativeValue',
                'value' => $apartment['specs']['sqm'],
                'unitCode' => 'MTK',
            ],
            'numberOfBathroomsTotal' => $apartment['specs']['bathrooms'],
            'numberOfBedrooms' => $apartment['specs']['bedrooms'],
            'numberOfRooms' => $apartment['specs']['rooms'],
            'amenityFeature' => $amenities,
        ],
        'checkinTime' => $apartment['booking']['checkin_time'] . ':00',
        'checkoutTime' => $apartment['booking']['checkout_time'] . ':00',
    ];

    $publicReviews = collect($reviews ?? []);
    if ($publicReviews->isNotEmpty()) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => round($publicReviews->avg('rating'), 1),
            'reviewCount' => $publicReviews->count(),
            'bestRating' => 10,
            'worstRating' => 1,
        ];

        $schemaReviews = $publicReviews
            ->filter(fn ($review): bool => trim($review->author_name) !== ''
                && trim($review->textForLocale(app()->getLocale())) !== '')
            ->map(fn ($review): array => [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name' => $review->author_name,
                ],
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => $review->rating,
                    'bestRating' => 10,
                    'worstRating' => 1,
                ],
                'reviewBody' => trim($review->textForLocale(app()->getLocale())),
            ])
            ->values()
            ->all();

        if ($schemaReviews !== []) {
            $schema['review'] = $schemaReviews;
        }
    }
@endphp

<script type="application/ld+json">@json($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>