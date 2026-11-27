@props(['exception' => null])

@php
    $codigo = method_exists($exception, 'getStatusCode') ? (int) $exception->getStatusCode() : 500;

    $catalogo = [
        400 => ['titulo' => 'Solicitud no válida', 'mensaje' => 'No pudimos entender los datos enviados. Revisa el formulario e inténtalo de nuevo.'],
        401 => ['titulo' => 'Inicio de sesión requerido', 'mensaje' => 'Necesitas iniciar sesión para continuar.'],
        402 => ['titulo' => 'Pago requerido', 'mensaje' => 'Esta acción requiere una suscripción o pago vigente. Comunícate con el administrador.'],
        403 => ['titulo' => 'Acceso denegado', 'mensaje' => 'No tienes permiso para realizar esta acción. Si crees que es un error, comunícate con el administrador.'],
        404 => ['titulo' => 'Página no encontrada', 'mensaje' => 'La página o el recurso que buscas no existe o fue movido.'],
        419 => ['titulo' => 'Sesión caducada', 'mensaje' => 'Tu sesión de seguridad expiró. Recarga la página y vuelve a intentarlo.'],
        429 => ['titulo' => 'Demasiadas solicitudes', 'mensaje' => 'Has realizado demasiados intentos. Espera un momento y vuelve a intentarlo.'],
        500 => ['titulo' => 'Error del servidor', 'mensaje' => 'Ocurrió un problema inesperado. Ya fuimos notificados; inténtalo de nuevo en unos minutos.'],
        503 => ['titulo' => 'Servicio no disponible', 'mensaje' => 'El servicio está en mantenimiento o no está disponible temporalmente. Vuelve a intentarlo más tarde.'],
    ];

    $detalle = $catalogo[$codigo] ?? ($codigo >= 500
        ? ['titulo' => 'No pudimos atender tu solicitud', 'mensaje' => 'Hay un problema temporal con el servidor. Vuelve a intentarlo en unos minutos.']
        : ['titulo' => 'No pudimos completar la solicitud', 'mensaje' => 'La solicitud no se pudo procesar. Revisa lo intentado o vuelve a la pantalla principal.']);

    // Los errores 5xx nunca deben filtrar mensajes internos (rutas, SQL, etc.).
    // En producción tampoco se muestra el mensaje original de errores 4xx.
    if ($codigo < 500 && config('app.debug')) {
        $mensajePersonalizado = method_exists($exception, 'getMessage') ? trim($exception->getMessage()) : '';

        $mensajesGenericos = [
            'This action is unauthorized.',
            'Unauthenticated.',
            'Forbidden',
            'Forbidden.',
            'Not Found',
            'Bad Request',
            'Unauthorized',
            'Payment Required',
            'Too Many Requests',
            'Internal Server Error',
            'Service Unavailable',
        ];

        if ($mensajePersonalizado !== '' && ! in_array($mensajePersonalizado, $mensajesGenericos, true)) {
            $detalle['mensaje'] = $mensajePersonalizado;
        }
    }

    $esRecarga = $codigo === 419;
    $iniciarSesion = auth()->guest() && in_array($codigo, [401, 403], true);

    $accion = match (true) {
        $esRecarga => ['etiqueta' => 'Recargar página', 'url' => null],
        $iniciarSesion => ['etiqueta' => 'Iniciar sesión', 'url' => route('login')],
        default => ['etiqueta' => 'Volver a ventas', 'url' => route('ventas.index')],
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
    <title>{{ $detalle['titulo'] }} · {{ config('app.name') }}</title>
    <script>
        (function () {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <style>
        :root {
            color-scheme: light;
            --fondo: #f5f5f4;
            --panel: #ffffff;
            --texto: #1c1917;
            --suave: #57534e;
            --borde: #e7e5e4;
            --acento: #b45309;
            --acento-claro: #fef3c7;
            --acento-texto: #92400e;
        }

        html.dark {
            color-scheme: dark;
            --fondo: #1c1917;
            --panel: #292524;
            --texto: #f5f5f4;
            --suave: #a8a29e;
            --borde: #44403c;
            --acento: #fbbf24;
            --acento-claro: #78350f;
            --acento-texto: #fcd34d;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: var(--fondo);
            color: var(--texto);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        main { width: 100%; max-width: 32rem; }

        .marca {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .75rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .icono {
            width: 2.5rem;
            height: 2.5rem;
            display: grid;
            place-items: center;
            border-radius: .75rem;
            background: var(--acento-claro);
            color: var(--acento-texto);
        }

        .tarjeta {
            background: var(--panel);
            border: 1px solid var(--borde);
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 12px 32px rgba(0, 0, 0, .06);
        }

        .codigo {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.05em;
            color: var(--acento-texto);
        }

        h1 { margin: .875rem 0 .5rem; font-size: 1.5rem; }

        p {
            margin: 0 auto 1.75rem;
            max-width: 26rem;
            color: var(--suave);
            line-height: 1.6;
        }

        .accion {
            display: inline-block;
            border: 0;
            border-radius: .75rem;
            padding: .75rem 1.25rem;
            background: var(--acento);
            color: #1c1917;
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .accion:hover { filter: brightness(1.08); }

        footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: .75rem;
            color: var(--suave);
        }

        footer a { color: inherit; text-underline-offset: 4px; }
    </style>
</head>
<body>
    <main>
        <div class="marca">
            <span class="icono" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l1.5-5h15L21 9" />
                    <path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" />
                    <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                    <path d="M9 20v-5h6v5" />
                </svg>
            </span>
            <span>{{ config('app.name') }}</span>
        </div>

        <section class="tarjeta">
            <div class="codigo">{{ $codigo }}</div>
            <h1>{{ $detalle['titulo'] }}</h1>
            <p>{{ $detalle['mensaje'] }}</p>

            @if ($esRecarga)
                <button type="button" class="accion" onclick="window.location.reload()">{{ $accion['etiqueta'] }}</button>
            @else
                <a class="accion" href="{{ $accion['url'] }}">{{ $accion['etiqueta'] }}</a>
            @endif
        </section>

        <footer>
            <p>
                &copy; {{ date('Y') }} SofCruz. Todos los derechos reservados.
                <span aria-hidden="true">·</span>
                <a href="{{ route('terminos.index') }}">Términos y privacidad</a>
            </p>
        </footer>
    </main>
</body>
</html>
