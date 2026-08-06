<?php

declare(strict_types=1);

// French validation messages — only the rules used in BookingController
return [
    'required'       => 'Le champ :attribute est obligatoire.',
    'date'           => 'Le champ :attribute doit être une date valide.',
    'after_or_equal' => "La date :attribute doit être aujourd'hui ou une date future.",
    'after'          => 'Le check-out doit être après le check-in.',
    'integer'        => 'Le champ :attribute doit être un entier.',
    'email'          => 'Veuillez saisir une adresse e-mail valide.',
    'string'         => 'Le champ :attribute doit être une chaîne de caractères.',
    'boolean'        => 'Le champ :attribute est invalide.',
    'confirmed'      => 'Le champ :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'password_different' => 'Le nouveau mot de passe ne peut pas être identique au précédent.',
    'min'            => [
        'numeric' => 'La valeur minimale pour :attribute est :min.',
        'string'  => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'max'            => [
        'numeric' => 'La valeur maximale pour :attribute est :max.',
        'string'  => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],

    'attributes' => [
        'checkin'  => 'check-in',
        'checkout' => 'check-out',
        'adults'   => 'adultes',
        'children' => 'enfants',
        'name'     => 'nom',
        'email'    => 'e-mail',
        'phone'    => 'téléphone',
        'phone_prefix' => 'indicatif téléphonique',
        'message'  => 'message',
        'current_password' => 'mot de passe actuel',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
    ],
];
