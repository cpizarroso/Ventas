<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de cajas</title>
    <style>
        @page { margin: 28px 24px 42px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 8px; color: #1c1917; }
        .informe { width: 100%; border-collapse: collapse; font-size: 7.5px; }
        .informe thead { display: table-header-group; }
        .informe tr { page-break-inside: avoid; }
        .informe th, .informe td { border: 1px solid #d6d3d1; padding: 3px 4px; }
        .sin-borde { border: 0; }
        .marca { text-align: center; font-size: 13px; font-weight: bold; letter-spacing: 1px; padding: 0 0 2px; }
        .eslogan { text-align: center; font-size: 8px; color: #57534e; padding: 0 0 2px; }
        .titulo { text-align: center; font-size: 15px; font-weight: bold; letter-spacing: 1px; padding: 8px 0 10px; }
        .etiqueta { background: #f5f5f4; text-align: left; font-weight: bold; width: 26%; }
        .resumen { background: #f5f5f4; font-weight: bold; }
        .columna { background: #f5f5f4; text-align: left; font-weight: bold; }
        .derecha { text-align: right; }
        .sin-datos { text-align: center; padding: 20px; color: #57534e; }
    </style>
</head>
<body>
    <table class="informe">
        <thead>
            <tr>
                <th class="marca sin-borde" colspan="11">{{ $negocio?->nombre_comercial ?? 'JOTAPE' }}</th>
            </tr>
            @if ($negocio?->eslogan)
                <tr>
                    <th class="eslogan sin-borde" colspan="11">{{ $negocio->eslogan }}</th>
                </tr>
            @endif
            <tr>
                <th class="titulo sin-borde" colspan="11">REPORTE DE CAJAS</th>
            </tr>
            <tr>
                <th class="etiqueta" colspan="2">Usuario que genera el reporte</th>
                <td colspan="9">{{ $usuario?->name ?? '—' }}</td>
            </tr>
            <tr>
                <th class="etiqueta" colspan="2">Fecha inicial</th>
                <td colspan="9">{{ $desde->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th class="etiqueta" colspan="2">Fecha final</th>
                <td colspan="9">{{ $hasta->format('d/m/Y') }}</td>
            </tr>
            @if ($estado !== null)
                <tr>
                    <th class="etiqueta" colspan="2">Estado</th>
                    <td colspan="9">{{ $estado === 'abierta' ? 'Abiertas' : 'Cerradas' }}</td>
                </tr>
            @endif
            <tr>
                <td class="resumen" colspan="4">Cajas: {{ $cantidadCajas }}</td>
                <td class="resumen" colspan="4">Total vendido: Bs {{ number_format($totalVendido, 2) }}</td>
                <td class="resumen" colspan="3">Diferencia: Bs {{ number_format($diferencia, 2) }}</td>
            </tr>
            <tr>
                <th class="columna">Caja</th>
                <th class="columna">Estado</th>
                <th class="columna">Usuario</th>
                <th class="columna">Apertura</th>
                <th class="columna">Cierre</th>
                <th class="columna derecha">Monto inicial</th>
                <th class="columna derecha">Ventas</th>
                <th class="columna derecha">Total vendido</th>
                <th class="columna derecha">Efectivo</th>
                <th class="columna derecha">QR</th>
                <th class="columna derecha">Diferencia</th>
            </tr>
        </thead>
        <tbody>
            @if ($hayCajas)
                {!! $filasHtml !!}
            @else
                <tr>
                    <td class="sin-datos" colspan="11">No hay cajas en el período seleccionado.</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
