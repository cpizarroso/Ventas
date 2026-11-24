<?php

return [
    'imagenes_predeterminadas' => public_path('img/productos-predeterminados'),
    'url_imagenes_predeterminadas' => '/img/productos-predeterminados',

    /** Tamaños cuadrados disponibles para las imágenes predeterminadas. */
    'tamanos_predeterminadas' => [256, 512, 800],

    /** Tamaño que usa el catálogo de productos cuando no se indica otro. */
    'tamano_predeterminada_defecto' => 512,

    /** Lado mínimo exigido a la imagen maestra para generar los tres derivados. */
    'maestra_lado_minimo' => 800,
];
