# JIBAS — Sistema de punto de venta

Aplicación Laravel 13 + Livewire 4 para gestión de ventas por categorías, con roles por usuario y multi-negocio.

## Requisitos

- PHP 8.3+
- Composer
- Node.js 20+ (para compilar assets con Vite)
- SQLite (por defecto) o MySQL

## Instalación

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
npm run build        # o npm run dev durante el desarrollo
php artisan serve
```

## URL de desarrollo (WAMP / Apache)

Para evitar errores `ERR_CONNECTION_REFUSED` o `404` por subdirectorio, la app
está configurada con un VirtualHost. Asegúrate de tener en el hosts de Windows:

```
127.0.0.1 jibas.test
```

y en `httpd-vhosts.conf` de Apache:

```apache
<VirtualHost *:80>
  ServerName jibas.test
  DocumentRoot "C:/wamp64/www/jibas/public"
  <Directory "C:/wamp64/www/jibas/public">
    Options +FollowSymLinks
    AllowOverride All
    Require local
  </Directory>
</VirtualHost>
```

Con `APP_URL=http://jibas.test` en `.env`, abre:

```
http://jibas.test/login
```

> Alternativa: `php artisan serve` + `http://127.0.0.1:8000/login` (deja la
> terminal abierta; se cierra el servidor si la cierras).
> Ruta legacy compatible: `http://127.0.0.1/jibas/public/login`.

## Cuentas de prueba (creadas por los seeders)

| Rol          | Email                  | Contraseña  |
| ------------ | ---------------------- | ----------- |
| Superadmin   | superadmin@jibas.test  | password    |
| Administrador| admin@jibas.test       | password    |
| Cajero       | cajero@jibas.test      | password    |

> Las credenciales del superadmin se pueden cambiar con `SUPERADMIN_EMAIL`,
> `SUPERADMIN_USERNAME` y `SUPERADMIN_PASSWORD`. En producción usa una contraseña
> segura y no reutilices los valores de prueba.
>
> Los seeders son idempotentes y **no crean categorías**. Cada negocio debe crear sus
> categorías manualmente desde **Configuración**.

## Roles y permisos

| Acción                                   | Superadmin | Admin | Cajero |
| ---------------------------------------- | ---------- | ----- | ------ |
| Ver y seleccionar categorías (ventas)     | ✔\*        | ✔     | ✔      |
| Ver estado vacío + enlace a configuración | ✔\*        | ✔     | —      |
| Ver/exportar reportes                     | ✔\*        | ✔     | ✔\*\*  |
| Acceder a `/configuracion`                | ✔          | ✔     | ✖ 403  |
| Crear/editar/eliminar categorías          | ✔\*        | ✔     | ✖ 403  |
| Modificar identidad del negocio           | ✔\*        | ✔     | ✖ 403  |

\* El superadmin opera sobre el negocio asignado a su usuario; sin negocio asignado
ve mensajes informativos. Todas las restricciones se aplican en el backend
(policies + scopes de consulta), no solo ocultando botones.

\*\* El cajero solo puede ver y exportar sus propias ventas; el filtro por cajero
no está disponible para ese rol.

## Funcionalidades

### Pantalla principal de ventas (`/ventas`)

- Muestra las categorías **activas** del negocio del usuario autenticado,
  obtenidas dinámicamente de la base de datos (ningún nombre hardcodeado).
- Tarjetas responsivas (móvil/tablet/escritorio) con efectos hover/focus.
- Seleccionar una categoría muestra «Próximamente» (catálogo de productos fuera de alcance por ahora).
- Estado vacío «Todavía no hay categorías creadas» con enlace a configuración
  para administradores y mensaje informativo para cajeros.
- Cabecera con enlace a Ventas, logo/nombre del negocio, nombre del usuario,
  acceso a reportes y configuración (según rol), control de tema claro/oscuro y cierre de sesión.

### Configuración (`/configuracion`, solo admin/superadmin)

- **Identidad del negocio:** nombre comercial (obligatorio), eslogan (opcional),
  logo opcional con previsualización, validación de tipo (jpg/jpeg/png/webp) y
  tamaño (máx. 2 MB), almacenamiento seguro en disco público y opción de quitarlo.
  Sin logo personalizado se muestra un icono predeterminado. El nombre comercial y
  el logo configurados también se muestran en la pantalla de inicio de sesión.
- **QR de pago:** imagen opcional (jpg/jpeg/png/webp, máx. 2 MB) con previsualización,
  reemplazo y eliminación. Se muestra al cajero al seleccionar QR durante el cobro,
  con opción de ampliarla; si no existe, se muestra un aviso y se permite cobrar.
- **Categorías:** crear, editar nombre y eliminar (con diálogo de confirmación).
  Nombres obligatorios (2–80 caracteres) y únicos por negocio.

### Reportes (`/reportes`)

- Filtros por rango de fechas, totales por método de pago y resumen por producto.
- Admin/superadmin ven todas las ventas del negocio y pueden filtrar por cajero.
- El cajero ve y exporta únicamente sus propias ventas, sin selector de cajero.
- Exportación del reporte filtrado en CSV.

### Términos y privacidad (`/terminos`)

- Documento público con Términos y Condiciones, Política de Privacidad y Cookies.
- Incluye aviso de desarrollo con IA (**vibe coding**), responsabilidad del usuario
  sobre imágenes y contenidos subidos, QR/pagos, propiedad intelectual, garantías,
  limitación de responsabilidad, indemnización y ley aplicable.
- Enlaces en el login y en el pie de página de la aplicación.
- Aceptación obligatoria por versión: se registra usuario, versión, fecha, IP y
  navegador en `aceptaciones_terminos`. Al publicar una nueva versión, los usuarios
  deben aceptar nuevamente.
- La versión y los datos legales se configuran en `config/jibas.php` y en las
  variables `TERMINOS_*` y `LEGAL_*` de `.env`.

### Base de datos

- `negocios`: nombre comercial, eslogan, logo, qr_pago.
- `categorias`: negocio (FK), nombre, activa (boolean), orden, timestamps;
  índice único `(negocio_id, nombre)` (la comparación de texto respeta la
  collation de la base de datos; en MySQL `utf8mb4_unicode_ci` es
  insensible a mayúsculas, en SQLite es sensible).
- `users`: añadidos `rol` (superadmin/admin/cajero) y `negocio_id` (nullable).
- `aceptaciones_terminos`: usuario (FK), versión, fecha de aceptación, IP, user agent;
  índice único `(user_id, version)`.

## Pruebas

```bash
php artisan test
```

140 pruebas automatizadas cubren ventas, categorías, productos, identidad del
negocio, QR, reportes, permisos, aislamiento entre negocios, aceptación versionada
de términos y política de privacidad.

## Estructura

```
app/
  Enums/Rol.php
  Http/Controllers/Auth/LoginController.php
  Http/Controllers/VentasController.php
  Http/Controllers/ConfiguracionController.php
  Http/Controllers/TerminosController.php
  Http/Middleware/EnsureTermsAccepted.php
  Livewire/Ventas/PantallaPrincipal.php
  Livewire/Configuracion/IdentidadNegocio.php
  Livewire/Configuracion/CategoriasAdmin.php
  Models/{User,Negocio,Categoria,AceptacionTerminos}.php
  Policies/{CategoriaPolicy,NegocioPolicy}.php
database/
  migrations/*_{create_negocios,create_categorias,add_rol_and_negocio_to_users,create_aceptaciones_terminos}_*
  factories/{Negocio,Categoria,User,AceptacionTerminos}Factory.php
  seeders/{DatabaseSeeder,SuperadminSeeder}.php   # sin categorías
resources/views/
  components/layouts/{app,legal}.blade.php
  auth/login.blade.php
  terminos/{index,aceptar,_documento}.blade.php
  ventas/index.blade.php
  configuracion/index.blade.php
  livewire/...
tests/Feature/
  CategoriasAdminTest.php
  PantallaPrincipalTest.php
  IdentidadNegocioTest.php
  SeedersTest.php
  AuthAccessTest.php
  TerminosTest.php
```

## Tema claro/oscuro

El tema se aplica con la clase `dark` en `<html>` (Tailwind CSS v4),
se persiste en `localStorage` y se restaura antes del primer pintado para
evitar destellos. El botón de conmutación está en la cabecera.
