<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Ticket #{{ $venta->id }} · {{ $venta->negocio->nombre_comercial }}</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #e7e5e4;
            color: #000;
        }

        body {
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            line-height: 1.35;
        }

        .print-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 16px;
            background: #1c1917;
            color: #fff;
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: 13px;
        }

        .print-bar p {
            margin: 2px 0 0;
            color: #d6d3d1;
            font-size: 12px;
        }

        .print-bar strong {
            font-size: 14px;
        }

        .print-actions {
            display: flex;
            gap: 8px;
        }

        .print-button,
        .back-link {
            display: inline-block;
            border: 0;
            border-radius: 8px;
            padding: 8px 14px;
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .print-button {
            background: #059669;
            color: #fff;
        }

        .back-link {
            border: 1px solid #78716c;
            color: #fff;
        }

        .ticket {
            width: 80mm;
            margin: 18px auto 40px;
            padding: 5mm 4mm;
            background: #fff;
            box-shadow: 0 6px 24px rgb(0 0 0 / 0.18);
        }

        .ticket-header {
            text-align: center;
        }

        .logo {
            max-width: 28mm;
            max-height: 20mm;
            margin: 0 auto 2mm;
            object-fit: contain;
            filter: grayscale(100%);
        }

        .business-name {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .slogan {
            margin: 1mm 0 0;
            font-size: 11px;
        }

        .sep {
            margin: 2.5mm 0;
            border-top: 1px dashed #000;
        }

        .meta-row,
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 3mm;
        }

        .meta-row + .meta-row {
            margin-top: 0.8mm;
        }

        .meta-row span:last-child {
            overflow-wrap: anywhere;
            text-align: right;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items th {
            padding: 0 0 1mm;
            border-bottom: 1px dashed #000;
            font-size: 10px;
            font-weight: 700;
            text-align: left;
            text-transform: uppercase;
        }

        .items td {
            padding: 1.5mm 0;
            vertical-align: top;
        }

        .items th:last-child,
        .items td:last-child {
            padding-left: 2mm;
            text-align: right;
        }

        .col-qty {
            width: 9mm;
        }

        .col-amount {
            width: 20mm;
        }

        .item-name {
            display: block;
            overflow-wrap: anywhere;
        }

        .item-unit {
            display: block;
            font-size: 10px;
        }

        .total-row {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .cash-row {
            display: flex;
            justify-content: space-between;
            gap: 3mm;
            margin-top: 1mm;
        }

        .thanks {
            margin: 0;
            text-align: center;
            font-size: 11px;
        }

        .credit {
            margin: 2.5mm 0 0;
            text-align: center;
            font-size: 10px;
            line-height: 1.4;
        }

        .credit p {
            margin: 0;
        }

        @media print {
            .print-bar {
                display: none !important;
            }

            html,
            body {
                width: 80mm;
                background: #fff;
            }

            .ticket {
                width: 80mm;
                margin: 0;
                padding: 3mm 4mm;
                box-shadow: none;
            }
        }

        @page {
            size: 80mm auto;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <div>
            <strong>Ticket de venta #{{ $venta->id }}</strong>
            <p>Elige tu impresora térmica, papel de 80 mm y márgenes: Ninguno.</p>
        </div>
        <div class="print-actions">
            <a href="{{ route('ventas.index') }}" class="back-link">Volver a ventas</a>
            <button type="button" class="print-button" onclick="window.print()">Imprimir</button>
        </div>
    </div>

    <main class="ticket" aria-label="Ticket de venta #{{ $venta->id }}">
        <header class="ticket-header">
            @if ($venta->negocio->logo)
                <img src="{{ asset('storage/'.$venta->negocio->logo) }}"
                     alt="Logo de {{ $venta->negocio->nombre_comercial }}"
                     class="logo">
            @endif
            <h1 class="business-name">{{ $venta->negocio->nombre_comercial }}</h1>
            @if ($venta->negocio->eslogan)
                <p class="slogan">{{ $venta->negocio->eslogan }}</p>
            @endif
        </header>

        <div class="sep"></div>

        <div class="meta-row">
            <span>Ticket</span>
            <span>#{{ $venta->id }}</span>
        </div>
        <div class="meta-row">
            <span>Fecha</span>
            <span>{{ $venta->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="meta-row">
            <span>Vendedor</span>
            <span>{{ $venta->user?->name ?? '—' }}</span>
        </div>
        <div class="meta-row">
            <span>Pago</span>
            <span>{{ $venta->metodo_pago?->etiqueta() ?? 'Efectivo' }}</span>
        </div>

        <div class="sep"></div>

        <table class="items">
            <thead>
                <tr>
                    <th class="col-qty" scope="col">Cant.</th>
                    <th scope="col">Descripción</th>
                    <th class="col-amount" scope="col">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($venta->detalles as $detalle)
                    <tr>
                        <td class="col-qty">{{ $detalle->cantidad }}</td>
                        <td>
                            <span class="item-name">{{ $detalle->nombre }}</span>
                            <span class="item-unit">{{ $detalle->cantidad }} x Bs {{ number_format((float) $detalle->precio_unitario, 2) }}</span>
                        </td>
                        <td class="col-amount">Bs {{ number_format((float) $detalle->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="sep"></div>

        <div class="total-row">
            <span>Total</span>
            <span>Bs {{ number_format((float) $venta->total, 2) }}</span>
        </div>

        @if ($venta->metodo_pago === \App\Enums\MetodoPago::Efectivo)
            @if ($venta->monto_recibido !== null)
                <div class="cash-row">
                    <span>Recibido</span>
                    <span>Bs {{ number_format((float) $venta->monto_recibido, 2) }}</span>
                </div>
            @endif
            @if ($venta->cambio !== null)
                <div class="cash-row">
                    <span>Cambio</span>
                    <span>Bs {{ number_format((float) $venta->cambio, 2) }}</span>
                </div>
            @endif
        @else
            <div class="cash-row">
                <span>Pago</span>
                <span>Pagado por QR</span>
            </div>
        @endif

        <div class="sep"></div>

        <p class="thanks">¡Gracias por su compra!</p>

        <footer class="credit">
            <p>Sistema desarrollado por SofCruz</p>
            <p>Contacto: 69175481</p>
        </footer>
    </main>

    @if (request()->boolean('imprimir'))
        <script>
            window.addEventListener('load', function () {
                window.setTimeout(function () {
                    window.print();
                }, 200);
            });
        </script>
    @endif
</body>
</html>
