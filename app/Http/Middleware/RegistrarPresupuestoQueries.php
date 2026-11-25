<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional development guard: records a warning when a request runs too many
 * queries, repeats the same SQL (a likely N+1) or hits a slow query.
 */
final class RegistrarPresupuestoQueries
{
    public function handle(Request $request, Closure $next): Response
    {
        $config = config('observabilidad.queries');

        if (! $config['enabled']) {
            return $next($request);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $inicio = microtime(true);

        try {
            return $next($request);
        } finally {
            $consultas = DB::getQueryLog();
            DB::disableQueryLog();

            $this->registrar($request, $consultas, (microtime(true) - $inicio) * 1000, $config);
        }
    }

    /**
     * @param  array<int, array{query: string, bindings: array<int, mixed>, time: float}>  $consultas
     * @param  array{enabled: bool, max: int, slow_ms: float, repeat: int, channel: string}  $config
     */
    private function registrar(Request $request, array $consultas, float $duracionMs, array $config): void
    {
        $coleccion = collect($consultas);

        $repetidas = $coleccion
            ->countBy(fn (array $consulta): string => $consulta['query'])
            ->filter(fn (int $veces): bool => $veces > $config['repeat'])
            ->sortDesc();

        $lentas = $coleccion
            ->sortByDesc('time')
            ->take(3)
            ->filter(fn (array $consulta): bool => (float) $consulta['time'] > $config['slow_ms']);

        if (count($consultas) <= $config['max'] && $repetidas->isEmpty() && $lentas->isEmpty()) {
            return;
        }

        Log::channel($config['channel'])->warning('Presupuesto de queries excedido', [
            'ruta' => $request->method().' '.$request->path(),
            'consultas' => count($consultas),
            'total_ms' => round((float) $coleccion->sum('time'), 1),
            'peticion_ms' => round($duracionMs, 1),
            'max_permitido' => $config['max'],
            'sql_repetida' => $repetidas
                ->take(3)
                ->map(fn (int $veces, string $sql): array => ['veces' => $veces, 'sql' => $sql])
                ->values()
                ->all(),
            'sql_lenta' => $lentas
                ->map(fn (array $consulta): array => [
                    'ms' => round((float) $consulta['time'], 1),
                    'sql' => $consulta['query'],
                ])
                ->values()
                ->all(),
        ]);
    }
}
