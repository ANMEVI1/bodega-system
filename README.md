# NEXO Bodega

Panel administrativo y sitio público para una bodega. Proyecto Laravel 13 independiente; no requiere ni modifica el proyecto móvil.

## Requisitos

- Laragon con PHP 8.3 o superior y Composer.
- Node.js 22+ y npm.
- SQLite para empezar sin configurar MySQL. La base local está en `database/database.sqlite`.

El proyecto usa Laravel 13, Laravel Socialite, Vite y Tailwind CSS 4. Conserva el tema oscuro, acentos violeta/fucsia y la tipografía Plus Jakarta Sans del diseño móvil, con navegación y tablas adaptadas a escritorio.

## Instalación en Laragon

1. Inicia Laragon y selecciona PHP 8.3 en **Menu → PHP → Version**. Esta selección aplica a Laragon; no hace falta desinstalar otras versiones.
2. Abre una terminal en esta carpeta y verifica `php -v` (PHP 8.3+) y `composer --version`.
3. Si no existen las dependencias, ejecuta `composer install` y `npm install`.
4. Copia `.env.example` a `.env` solo si aún no tienes `.env`, y luego ejecuta `php artisan key:generate`.
5. Ejecuta `php artisan migrate --seed` para crear las tablas y el contenido editorial inicial en la base SQLite del proyecto.
6. Ejecuta `npm run build`.
7. Ejecuta `php artisan serve` y visita la URL local que muestre Laravel. Para usar un virtual host de Laragon, apúntalo a la carpeta `public` del proyecto.
8. Entra a `/setup` para crear el primer administrador. El formulario se cierra una vez que existe una cuenta.

La configuración local usa SQLite, sesiones en archivos y el driver de correo `log`; no se conecta a bases de datos externas. No reemplaces una base existente ni ejecutes `migrate:fresh` si ya guardaste información.

## Ejecución aislada sin Docker

En Windows puedes usar un PHP 8.3 portátil guardado dentro de este proyecto. No modifica el PHP ni la base de datos de XAMPP ni agrega nada al `PATH` global. Requiere PowerShell, acceso a Internet y Node.js/npm.

1. Desde PowerShell en la carpeta del proyecto, ejecuta `.\scripts\setup-isolated.ps1`. Descarga PHP desde `windows.php.net`, Composer desde `getcomposer.org`, instala dependencias locales, crea `.env.isolated` y una base SQLite separada.
2. Ejecuta `.\scripts\start-isolated.ps1` y visita `http://127.0.0.1:8000`.
3. Usa `Ctrl+C` para detener el servidor. Los binarios y la configuración aislada se guardan bajo `.tools` y `.env.isolated`, excluidos de Git.
4. Abre `http://127.0.0.1:8000/setup` para crear el primer administrador.

## Ejecución aislada con Docker (recomendada para conservar XAMPP)

Esta alternativa usa PHP 8.3 dentro de Docker, SQLite en un volumen propio y publica el sitio solo en `http://localhost:8000`. No instala ni cambia PHP, MySQL, Apache o la configuración de XAMPP.

1. Instala e inicia Docker Desktop para Windows.
2. Desde PowerShell, en la carpeta del proyecto, compila los recursos y levanta Laravel:

   ```powershell
   docker compose run --rm assets
   docker compose up --build
   ```

3. Visita `http://localhost:8000`. La primera ejecución instala Composer, crea la base SQLite del contenedor y aplica migraciones y contenido inicial.
4. Abre `http://localhost:8000/setup` para crear el primer administrador; el formulario se cierra al crear la cuenta.

Detén el servicio con `Ctrl+C` y vuelve a iniciarlo con `docker compose up`. Los datos SQLite y la clave de la aplicación viven en el volumen `bodega_storage`, independiente de XAMPP. `docker compose down` conserva esos datos; evita `docker compose down --volumes` si quieres mantenerlos. Para eliminar el contenedor e imagen sin borrar datos: `docker compose down`.

## Funcionalidades

- Inicio público editable: banner, presentación, productos disponibles, preguntas frecuentes, contacto y enlace a WhatsApp.
- Acceso de administrador con correo/contraseña, inicio Google opcional y recuperación por enlace de correo.
- Resumen financiero, cartera CRM de clientes con datos fiscales, contacto, ubicación, origen, historial de productos y mensajes predefinidos por WhatsApp, inventario con sugerencia de precio, proveedores e historial de compras.
- Registro de ventas y compras con actualización transaccional del inventario.
- Utilidad por producto basada en costo de compra más gastos directos por unidad; gastos generales e impuestos no están incluidos.
- Formularios de contacto y bandeja privada para responder por WhatsApp.
- Asistente NIA local basado en comandos para consultas de ventas y altas simples de clientes/productos; no requiere ni conecta una API de IA externa.
- Exportación CSV compatible con Excel para ventas, clientes y productos; las ventas se pueden imprimir o guardar como PDF desde el navegador.

## Integraciones opcionales

### Acceso Google

Crea credenciales OAuth para la URL de callback `http://TU-DOMINIO/auth/google/callback` y define `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI` en `.env`. Google solo puede iniciar sesión para una cuenta administradora ya creada; no crea cuentas automáticamente.

### Recuperación de acceso

Configura un proveedor de correo SMTP en `.env` para enviar enlaces de restablecimiento. En desarrollo, el driver `log` escribe el mensaje localmente en `storage/logs`; no envía correos reales.

## Verificación

```powershell
php artisan test
npm run build
php artisan route:list
```
