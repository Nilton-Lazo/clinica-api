<?php

return [
    'correlativo_min_digits' => (int) env('CODIGO_CORRELATIVO_MIN_DIGITS', 3),

    'correlativo_max_digits' => (int) env('CODIGO_CORRELATIVO_MAX_DIGITS', 10),

    'profiles' => [
        'maestro' => [
            'min_digits' => (int) env('CODIGO_CORRELATIVO_MIN_DIGITS', 3),
            'max_digits' => (int) env('CODIGO_CORRELATIVO_MAX_DIGITS', 10),
        ],
        'documento_largo' => [
            'min_digits' => 10,
            'max_digits' => 10,
        ],
        'serie_numeracion' => [
            'min_digits' => 3,
            'max_digits' => 3,
        ],
        'numero_comprobante' => [
            'min_digits' => 7,
            'max_digits' => 7,
        ],
    ],
];
