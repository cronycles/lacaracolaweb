<?php

declare(strict_types=1);

// Italian validation messages — only the rules used in BookingController
return [
    'required'       => 'Il campo :attribute è obbligatorio.',
    'date'           => 'Il campo :attribute deve essere una data valida.',
    'after_or_equal' => 'La data :attribute deve essere oggi o una data futura.',
    'after'          => 'Il check-out deve essere successivo al check-in.',
    'integer'        => 'Il campo :attribute deve essere un numero intero.',
    'email'          => 'Inserire un indirizzo email valido.',
    'string'         => 'Il campo :attribute deve essere un testo.',
    'boolean'        => 'Il campo :attribute non è valido.',
    'confirmed'      => 'Il campo :attribute non corrisponde.',
    'current_password' => 'La password è incorretta.',
    'password_different' => 'La nuova password non può essere uguale a quella precedente.',
    'min'            => [
        'numeric' => 'Il valore minimo per :attribute è :min.',
        'string'  => 'Il campo :attribute deve contenere almeno :min caratteri.',
    ],
    'max'            => [
        'numeric' => 'Il valore massimo per :attribute è :max.',
        'string'  => 'Il campo :attribute non può superare :max caratteri.',
    ],

    'attributes' => [
        'checkin'  => 'check-in',
        'checkout' => 'check-out',
        'adults'   => 'adulti',
        'children' => 'bambini',
        'babies'   => 'neonati',
        'pets'     => 'animali domestici',
        'first_name' => 'nome',
        'last_name' => 'cognome',
        'email'    => 'email',
        'phone'    => 'telefono',
        'phone_prefix' => 'prefisso telefonico',
        'message'  => 'messaggio',
        'current_password' => 'password attuale',
        'password' => 'password',
        'password_confirmation' => 'conferma password',
    ],
];
