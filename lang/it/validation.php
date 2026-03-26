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
    'min'            => [
        'numeric' => 'Il valore minimo per :attribute è :min.',
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
        'name'     => 'nome',
        'email'    => 'email',
        'phone'    => 'telefono',
        'message'  => 'messaggio',
    ],
];
