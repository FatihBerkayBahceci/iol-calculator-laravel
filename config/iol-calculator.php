<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IOL Calculator Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the IOL Calculator package.
    |
    */

    'default_formula' => env('IOL_DEFAULT_FORMULA', 'SRK/T'),

    'formulas' => [
        'enabled' => [
            'SRK/T',
            'SRK II',
            'Holladay 1',
            'Holladay 2',
            'Hoffer Q',
            'Haigis',
            'Barrett Universal II',
            'Hill-RBF',
            'Kane',
            'Ladas',
            'EVO',
            'Olsen'
        ]
    ],

    'constants' => [
        'a_constant' => env('IOL_A_CONSTANT', 118.4),
        'surgeon_factor' => env('IOL_SURGEON_FACTOR', 1.68),
        'iol_position' => env('IOL_POSITION', 5.25),
    ],

    'keratometer' => [
        'refractive_index' => env('KERATOMETER_INDEX', 1.3375),
    ],

    'toric' => [
        'sia' => env('TORIC_SIA', 0.5),
        'incision_axis' => env('TORIC_INCISION_AXIS', 180),
    ],

    'database' => [
        'table_prefix' => env('IOL_TABLE_PREFIX', 'iol_'),
    ],
];