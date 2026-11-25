<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Presupuesto de queries por petición
    |--------------------------------------------------------------------------
    |
    | Desactivado por defecto. Actívalo solo en desarrollo con
    | QUERY_BUDGET_ENABLED=true para detectar N+1 y consultas lentas sin
    | instalar paquetes de depuración en el servidor.
    |
    */

    'queries' => [
        'enabled' => env('QUERY_BUDGET_ENABLED', false),
        'max' => (int) env('QUERY_BUDGET_MAX', 40),
        'slow_ms' => (float) env('QUERY_BUDGET_SLOW_MS', 150),
        'repeat' => (int) env('QUERY_BUDGET_REPEAT', 5),
        'channel' => env('QUERY_BUDGET_CHANNEL', 'single'),
    ],

];
