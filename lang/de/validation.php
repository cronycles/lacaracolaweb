<?php

declare(strict_types=1);

// German validation messages — only the rules used in BookingController
return [
    'required'       => 'Das Feld :attribute ist erforderlich.',
    'date'           => 'Das Feld :attribute muss ein gültiges Datum sein.',
    'after_or_equal' => 'Das Datum :attribute muss heute oder ein zukünftiges Datum sein.',
    'after'          => 'Das Check-out muss nach dem Check-in liegen.',
    'integer'        => 'Das Feld :attribute muss eine ganze Zahl sein.',
    'email'          => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
    'string'         => 'Das Feld :attribute muss eine Zeichenkette sein.',
    'boolean'        => 'Das Feld :attribute ist ungültig.',
    'confirmed'      => 'Das Feld :attribute stimmt nicht überein.',
    'current_password' => 'Das Passwort ist falsch.',
    'password_different' => 'Das neue Passwort kann nicht mit dem vorherigen identisch sein.',
    'min'            => [
        'numeric' => 'Der Mindestwert für :attribute ist :min.',
        'string'  => 'Das Feld :attribute muss mindestens :min Zeichen enthalten.',
    ],
    'max'            => [
        'numeric' => 'Der Höchstwert für :attribute ist :max.',
        'string'  => 'Das Feld :attribute darf nicht mehr als :max Zeichen enthalten.',
    ],

    'attributes' => [
        'checkin'  => 'Check-in',
        'checkout' => 'Check-out',
        'adults'   => 'Erwachsene',
        'children' => 'Kinder',
        'name'     => 'Name',
        'email'    => 'E-Mail',
        'phone'    => 'Telefon',
        'phone_prefix' => 'Telefonvorwahl',
        'message'  => 'Nachricht',
        'current_password' => 'aktuelles Passwort',
        'password' => 'Passwort',
        'password_confirmation' => 'Passwortbestätigung',
    ],
];
