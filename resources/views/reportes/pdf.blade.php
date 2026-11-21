<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de ventas</title>
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
                <th class="marca sin-borde" colspan="12">{{ $negocio?->nombre_comercial ?? 'JOTAPE' }}</th>
            </tr>
            @if ($negocio?->eslogan)
                <tr>
                    <th class="eslogan sin-borde" colspan="12">{{ $negocio->eslogan }}</th>
                </tr>
            @endif
            <tr>
                <th class="titulo sin-borde" colspan="12">REPORTE DE VENTAS</th>
            </tr>
            <tr>
                <th class="etiqueta" colspan="2">Usuario que genera el reporte</th>
                <td colspan="10">{{ $usuario?->name ?? '—' }}</td>
            </tr>
            <tr>
                <th class="etiqueta" colspan="2">Fecha inicial</th>
                <td colspan="10">{{ $desde->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th class="etiqueta" colspan="2">Fecha final</th>
                <td colspan="10">{{ $hasta->format('d/m/Y') }}</td>
            </tr>
            @if ($cajeroSeleccionado !== null)
                <tr>
                    <th class="etiqueta" colspan="2">Cajero</th>
                    <td colspan="10">{{ $cajeroSeleccionado->name }}</td>
                </tr>
            @endif
            @if ($busqueda !== '')
                <tr>
                    <th class="etiqueta" colspan="2">Búsqueda</th>
                    <td colspan="10">{{ $busqueda }}</td>
                </tr>
            @endif
            <tr>
                <td class="resumen" colspan="6">Ventas: {{ $cantidadVentas }}</td>
                <td class="resumen" colspan="6">Total vendido: Bs {{ number_format($totalVendido, 2) }}</td>
            </tr>
            <tr>
                <th class="columna">Venta</th>
                <th class="columna">Fecha</th>
                <th class="columna">Hora</th>
                <th class="columna">Vendedor</th>
                <th class="columna">Tipo</th>
                <th class="columna">Llevar</th>
                <th class="columna">Promociones</th>
                <th class="columna derecha">Ítems</th>
                <th class="columna">Detalle</th>
                <th class="columna derecha">Total</th>
                <th class="columna">Método</th>
                <th class="columna derecha">Recibido</th>
            </tr>
        </thead>
        <tbody>
            @if ($ventas->isEmpty())
                <tr>
                    <td class="sin-datos" colspan="12">No hay ventas en el período seleccionado.</td>
                </tr>
            @else
                @foreach ($ventas as $venta)
                    <tr>
                        <td>#{{ $venta->numeroTicket() }}</td>
                        <td>{{ $venta->created_at->format('d/m/Y') }}</td>
                        <td>{{ $venta->created_at->format('H:i') }}</td>
                        <td>{{ $venta->user?->name ?? '—' }}</td>
                        <td>{{ $venta->etiquetaTipoReporte() }}</td>
                        <td>{{ $venta->para_llevar ? 'Sí' : 'No' }}</td>
                        <td>{{ $venta->promocionesNombres() ?: '—' }}</td>
                        <td class="derecha">{{ $venta->detalles->sum('cantidad') }}</td>
                        <td>
                            @foreach ($venta->detalles as $detalle)
                                <span>{{ $detalle->cantidad }} × {{ $detalle->nombre }}</span>@if (! $loop->last), @endif
                            @endforeach
                        </td>
                        <td class="derecha">Bs {{ number_format((float) $venta->total, 2) }}</td>
                        <td>{{ $venta->metodo_pago?->etiqueta() ?? 'Efectivo' }}</td>
                        <td class="derecha">
                            {{ $venta->monto_recibido !== null ? number_format((float) $venta->monto_recibido, 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</body>
</html>
