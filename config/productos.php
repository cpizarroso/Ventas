<?php

return [
    'imagenes_predeterminadas' => public_path('img/productos-predeterminados'),
    'url_imagenes_predeterminadas' => '/img/productos-predeterminados',

    /** Tamaños cuadrados disponibles para las imágenes predeterminadas. */
    'tamanos_predeterminadas' => [256, 512, 800],

    /** Tamaño que usa el catálogo de productos cuando no se indica otro. */
    'tamano_predeterminada_defecto' => 512,

    /** Lado de la imagen maestra generada (los derivados salen desde aquí). */
    'maestra_lado_objetivo' => 800,

    /** Lado mínimo del área recortada; por debajo se ampliará la maestra. */
    'recorte_lado_minimo' => 64,

    /** Umbral de stock bajo cuando el negocio no define uno. */
    'stock_min_defecto' => 10,

    /** Permitir cobrar ventas que dejan stock negativo cuando no hay preferencia guardada. */
    'permitir_stock_negativo' => true,
];
