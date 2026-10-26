<?php

return [
    'legal' => [
        'titular' => env('LEGAL_TITULAR', 'SofCruz'),
        'contacto' => env('LEGAL_CONTACTO', 'agenciasofcruz@gmail.com'),
        'ciudad' => env('LEGAL_CIUDAD', 'Santa Cruz de la Sierra'),
        'pais' => env('LEGAL_PAIS', 'Bolivia'),
    ],

    'terminos' => [
        'version' => env('TERMINOS_VERSION', '1.0'),
        'vigente_desde' => env('TERMINOS_VIGENTE_DESDE', '2026-10-26'),
    ],

    'superadmin' => [
        'email' => env('SUPERADMIN_EMAIL', 'superadmin@jibas.test'),
        'username' => env('SUPERADMIN_USERNAME', 'superadmin'),
        'password' => env('SUPERADMIN_PASSWORD', 'password'),
    ],
];
