# Migración del Módulo POS + Manos Libres + IA Local (NegoIA → Bodega System)

## 1. Origen y alcance

Este documento describe la migración puntual del módulo **Punto de Venta / Caja (POS)** con **asistente por voz (Modo Manos Libres)** e **interpretación de pedidos con IA local** desde `C:\xampp\htdocs\NegoIA` hacia `c:\xampp\htdocs\bodega-system`.

### Alcance incluido
- Vista standalone de caja en `/punto-de-venta` (con redirección permanente `/pos` → `/punto-de-venta`) accesible desde el apartado público de cliente (`resources/views/layouts/site.blade.php`).
- Interfaz React + TypeScript 1:1 respecto a `NegoIA/frontend/src/pages/PosPage.tsx` y sus componentes (`PosCatalogo`, `PosTicket`, `VentaLibreModal`, `TicketModal`), adaptada con una cabecera limpia (`"Punto de Venta / Caja"`, nombre del negocio y enlace `"← Volver a la tienda"`) sin el sidebar ni el botón global `"Abrir Copiloto IA"` de NegoIA.
- Asistente de voz en caja (`useVoiceAssistant`): dictado por Web Speech API (`es-PE` con fallback `es-ES`), lectura de respuestas con `speechSynthesis`, prevención de auto-eco y confirmación/cancelación por voz en Modo Manos Libres.
- Backend Laravel aditivo (`PosController`, `PosAiService`, modelos `PosSale` y `PosSaleItem`, migración `2026_11_25_000001_create_pos_sales_tables`) conectado a Ollama local.

### Fuera de alcance
- No se migraron el panel administrativo de NegoIA, el Copiloto IA global, autenticación JWT de NegoIA ni módulos ajenos al POS.
- No se modificaron `resources/views/site/home.blade.php`, `resources/js/app.js` ni `resources/css/app.css`.

---

## 2. Dependencias instaladas

### Paquetes npm (`package.json`)
- **Producción:** `react`, `react-dom`, `lucide-react`, `sonner`, `@radix-ui/react-dialog`, `@radix-ui/react-select`, `@tanstack/react-query`, `clsx`, `tailwind-merge`.
- **Desarrollo:** `@vitejs/plugin-react` (`^5`, compatible con `vite@^7.0.7`), `typescript`, `@types/react`, `@types/react-dom`.

### Configuración PHP / Laravel
- Cliente HTTP nativo de Laravel (`Illuminate\Support\Facades\Http`) para consumir la API local de Ollama (`/api/chat`).
- Configuración en `config/services.php` bajo la clave `ollama` y variables de entorno en `.env.example`:
  - `OLLAMA_BASE_URL` (default `http://127.0.0.1:11434`)
  - `OLLAMA_MODEL` (default `qwen2.5:14b`)
  - `OLLAMA_TEMPERATURE` (default `0.1`)
  - `OLLAMA_TIMEOUT_MS` (default `90000`)
  - `OLLAMA_KEEP_ALIVE` (default `30m`)
  - `OLLAMA_NUM_CTX` (default `4096`)

---

## 3. Configuración y flujo de IA local (Ollama)

El servicio `App\Services\PosAiService` procesa los pedidos en lenguaje natural enviados a `POST /api/pos/ai-cart`:

1. **Selección de catálogo para el prompt:** Carga productos activos (`active = true`) y formatea hasta `MAX_PRODUCTOS_PROMPT` (80) líneas con formato `[id | nombre | S/ precio | stock unidad]`. Si el catálogo supera el límite, prioriza productos por puntaje léxico frente a los tokens del mensaje.
2. **System Prompt (`SYSTEM_PROMPT_POS_CART`):** Instruye a `qwen2.5:14b` (configurable vía `OLLAMA_MODEL`) a devolver únicamente un JSON válido (`format: "json"`, `stream: false`) con:
   - `items`: `[{ "productoId": number, "nombre": string, "cantidad": number }]`
   - `noEncontrados`: términos no hallados en catálogo
   - `metodoPago`: `"EFECTIVO" | "YAPE" | "PLIN" | "TRANSFERENCIA" | "TARJETA" | "FIADO" | null`
   - `advertencias` y `mensaje`
3. **Consulta a Ollama:** Realiza `POST {base_url}/api/chat` con hasta 2 intentos ante errores transitorios. Si Ollama no responde devuelve `503` (`IA_OLLAMA_NO_DISPONIBLE`) o `504` (`IA_TIMEOUT`).
4. **Heurística de respaldo y sinónimos peruanos:**
   - Si un ítem devuelto por la IA carece de `productoId` válido o cae en `noEncontrados`, `PosAiService` busca en el catálogo real mediante normalización de tildes, tokens y el mapa `SINONIMOS_PRODUCTO`:
     - `chela` → `cerveza`, `pilsen`, `cristal`, `cusquena`, `heineken`, `corona`
     - `birra` → `cerveza`, `pilsen`, `cristal`, `cusquena`
     - `gaseosa` → `inca kola`, `coca cola`, `pepsi`, `sprite`, `fanta`, `guarana`, `soda`, `cola`
     - `soda` → `gaseosa`, `inca kola`, `coca cola`, `sprite`, `fanta`, `galleta`
   - Interpreta expresiones de cantidad y jerga peruana desde el prompt y la heurística (`medio kilo` = `0.5`, `un cuarto` = `0.25`, `docena` = `12`, `six pack` = `6`, etc.).
5. **Validación de stock y respuesta:** Agrupa ítems repetidos, marca `excedeStock` cuando la cantidad solicitada supera `stockActual`, calcula `subtotalEstimado`, construye `mensaje_voz` apto para TTS y fija `requiere_confirmacion: true` cuando hay ítems válidos.

---

## 4. Flujo de Voz y Modo Manos Libres

Implementado en `resources/js/pos/hooks/useVoiceAssistant.ts` y conectado en `resources/js/pos/components/PosTicket.tsx`:

- **STT (Speech-to-Text):** Utiliza `SpeechRecognition` / `webkitSpeechRecognition` con idioma inicial `es-PE` y fallback automático a `es-ES` ante `language-not-supported`. Muestra transcripción en vivo mientras el micrófono escucha.
- **TTS (Text-to-Speech):** Utiliza `window.speechSynthesis`, priorizando voces `es-PE`, Google Español o Microsoft (`Helena`, `Sabina`, `Camila`, `Raul`). La función `limpiarTextoParaVoz()` convierte montos en soles (`S/ 15.50` → `15 soles con 50`) y elimina markdown/URLs/símbolos.
- **Prevención de auto-eco:**
  - Al iniciar el micrófono (`startListeningInternal`), detiene cualquier locución TTS activa.
  - Al iniciar una locución (`speak`), aborta el micrófono activo y solo reactiva la escucha (`autoListenAfter` con retardo de `250ms`) tras finalizar `utterance.onend` cuando corresponde.
- **Confirmación y cancelación por voz:**
  - Cuando llega una propuesta de IA por voz o con **Manos Libres** activo (`requiere_confirmacion && items.length > 0`), el ticket muestra la tarjeta `¿Agregar al carrito?`, lee `mensaje_voz` en voz alta y abre el micrófono automáticamente.
  - `esConfirmacionVoz()` detecta frases como `"sí"`, `"claro"`, `"confirmo"`, `"dale"`, `"ok"`, `"agrega"`, `"adelante"`, `"listo"`, agregando los productos al carrito y confirmando por TTS.
  - `esCancelacionVoz()` detecta `"no"`, `"cancela"`, `"descarta"`, `"mejor no"`, `"olvida"`, descartando la propuesta.
- **Lectura del cobro:** Al completar `checkout`, si el Modo Manos Libres está activo, lee `res.mensaje_voz` (total, método de pago y vuelto en soles).

---

## 5. Esquema de base de datos y endpoints

### Migración aditiva (`database/migrations/2026_11_25_000001_create_pos_sales_tables.php`)
- **`pos_sales`**:
  - `id`, `reference` (única, formato `POS-yymmdd-XXXXX`), `client_id` (FK nullable a `clients`, `nullOnDelete`), `total` (`decimal(12,2)`), `payment_method` (`EFECTIVO`, `YAPE`, `PLIN`, `TRANSFERENCIA`, `TARJETA`, `FIADO`), `amount_received` (`decimal(12,2)`), `change_amount` (`decimal(12,2)`), `origin` (`MANUAL | VOZ | IA`), `sold_at`, `timestamps`.
- **`pos_sale_items`**:
  - `id`, `pos_sale_id` (FK a `pos_sales`, `cascadeOnDelete`), `product_id` (FK nullable a `products`, `nullOnDelete`), `product_name` (`string(150)`), `quantity` (`decimal(12,3)`), `unit_price` (`decimal(12,2)`), `subtotal` (`decimal(12,2)`), `timestamps`.
- **`clients.pending_balance`**:
  - Columna aditiva `decimal(12,2)` con valor por defecto `0`, incrementada en ventas `FIADO`.
- **Espejo hacia `sales`:** En `PosController::checkout()`, dentro de `DB::transaction`, cada ítem de catálogo (`productoId != null`) decrementa `products.stock` con `lockForUpdate()` y crea su registro espejo en `sales` (`status: 'pending'` si es `FIADO`, `'paid'` en otro caso) para mantener sincronizados los reportes del panel administrativo. Los ítems de **Venta Libre** (`productoId == null`) se registran en `pos_sale_items` sin alterar `products`.

### Endpoints (`routes/web.php`)
| Método | Ruta | Nombre | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/punto-de-venta` | `site.pos` | Renderiza `resources/views/site/pos.blade.php` con el POS React |
| `GET` | `/pos` | `site.pos.alias` | Redirección `301` hacia `/punto-de-venta` |
| `GET` | `/api/pos/catalogo` | `site.pos.catalog` | Retorna productos activos y categorías ordenadas |
| `GET` | `/api/pos/clientes` | `site.pos.clients` | Retorna hasta 100 clientes con `saldoPendiente` y `meta` |
| `POST` | `/api/pos/ai-cart` | `site.pos.ai_cart` | Interpreta texto/voz con Ollama (`throttle:30,1`) |
| `POST` | `/api/pos/checkout` | `site.pos.checkout` | Registra venta POS, stock, fiado y espejo (`throttle:60,1`) |

---

## 6. Inventario de archivos creados y modificados

### Backend y configuración (Fase 1 y Fase 2)
- Modificados:
  - `.env.example` (variables `OLLAMA_*`)
  - `config/services.php` (sección `ollama`)
  - `app/Models/Client.php` (`pending_balance` y relación `posSales`)
  - `routes/web.php` (rutas web y API del POS)
  - `package.json` y `package-lock.json` (dependencias React/TS/Radix/TanStack/Lucide/Sonner)
  - `vite.config.js` (`@vitejs/plugin-react`, entradas `resources/css/pos.css` y `resources/js/pos/main.tsx`, alias `@pos`)
  - `resources/views/layouts/site.blade.php` (enlace `"Punto de Venta"` en nav desktop, nav móvil y footer + normalización de clases Tailwind v4)
- Creados:
  - `app/Exceptions/PosAiException.php`
  - `app/Http/Controllers/PosController.php`
  - `app/Models/PosSale.php`
  - `app/Models/PosSaleItem.php`
  - `app/Services/PosAiService.php`
  - `database/migrations/2026_11_25_000001_create_pos_sales_tables.php`
  - `tests/Feature/PosModuleTest.php`
  - `tsconfig.json`

### Frontend POS aislado (`resources/css/pos.css`, `resources/views/site/pos.blade.php`, `resources/js/pos/`)
- `resources/css/pos.css` (estilos aislados Tailwind v4 + `@media print` 80mm para ticket térmico)
- `resources/views/site/pos.blade.php` (shell standalone con `csrf-token` y `#pos-root`)
- `resources/js/pos/main.tsx`
- `resources/js/pos/PosApp.tsx`
- `resources/js/pos/types.ts`
- `resources/js/pos/api.ts`
- `resources/js/pos/formatters.ts`
- `resources/js/pos/hooks/useVoiceAssistant.ts`
- `resources/js/pos/components/PosCatalogo.tsx`
- `resources/js/pos/components/PosTicket.tsx`
- `resources/js/pos/components/VentaLibreModal.tsx`
- `resources/js/pos/components/TicketModal.tsx`
- `resources/js/pos/components/ui/cn.ts`
- `resources/js/pos/components/ui/Badge.tsx`
- `resources/js/pos/components/ui/Button.tsx`
- `resources/js/pos/components/ui/Dialog.tsx`
- `resources/js/pos/components/ui/Input.tsx`
- `resources/js/pos/components/ui/Select.tsx`
- `docs/MIGRACION_POS_NEGOIA.md`

---

## 7. Cómo ejecutar y usar el módulo

1. **Migrar base de datos (si aún no se ejecutó):**
   ```powershell
   php artisan migrate
   ```
2. **Iniciar Ollama local con el modelo configurado:**
   ```powershell
   ollama serve
   ollama pull qwen2.5:14b
   ```
3. **Compilar assets de frontend (o ejecutar en modo desarrollo):**
   ```powershell
   npm run build
   # o para desarrollo con HMR:
   npm run dev
   ```
4. **Levantar la aplicación Laravel:**
   ```powershell
   php artisan serve
   ```
   Abrir `http://127.0.0.1:8000/punto-de-venta` (o usar el enlace `"Punto de Venta"` en la barra superior / pie de página de la tienda).
5. **Atajos de teclado en Caja POS:**
   - `F2`: Enfocar y seleccionar el buscador de productos.
   - `F4`: Ejecutar **COBRAR** si el carrito tiene ítems y no hay modales abiertos.
   - `ESC`: Cerrar propuesta pendiente de IA o vaciar el carrito actual (cuando no hay modales abiertos).

---

## Auditoría y Verificación (Fase 3)

### Correcciones aplicadas durante la auditoría
- **Normalización de `codigoSku` nullable (`app/Http/Controllers/PosController.php`, `app/Services/PosAiService.php`, `resources/js/pos/components/PosCatalogo.tsx`):** Como `products.sku` es nullable en BD, se normaliza a `''` en backend (`$product->sku ?? ''`) y se añade guarda defensiva (`p.codigoSku ?? ""` y formato condicional en tarjeta) en `PosCatalogo.tsx` para evitar `TypeError` al buscar o renderizar productos sin SKU.
- **Límite de longitud en Venta Libre (`resources/js/pos/components/VentaLibreModal.tsx`):** Se agregó `maxLength={150}` al campo descripción para alinear con la validación backend (`max:150`).
- **Normalización Tailwind v4 (`resources/js/pos/components/PosCatalogo.tsx`):** Reemplazo de `active:scale-[0.98]` por la clase canónica `active:scale-98`.
- **Cobertura de pruebas (`tests/Feature/PosModuleTest.php`):** Agregados tests para `GET /punto-de-venta` (`200 OK`, `#pos-root`, `csrf-token`), presencia de `route('site.pos')` en `GET /` y normalización de `sku = null` a `''` en `/api/pos/catalogo`.

### Resultados medidos tras la última corrección
- `npx tsc --noEmit`: **0 errores**.
- `npm run build`: **Exitoso** (`vite v7.3.8`, `2364 modules transformed`, `0` errores; generado `public/build/assets/pos-BeK_wbO5.css` [`140.92 kB`] y `public/build/assets/main-C9CLG_7l.js` [`478.16 kB`]; único warning preexistente de tamaño >500 kB proviene de `three.module-C07zP_kS.js` del módulo 3D público).
- `php -l` (12 archivos PHP nuevos y modificados): **0 errores de sintaxis**.
- `php artisan view:clear` y `php artisan view:cache`: **Exitoso** (`Compiled views cleared successfully`, `Blade templates cached successfully`).
- `php artisan route:list`: **74 rutas registradas**, **0 errores** (incluye `site.pos`, `site.pos.alias`, `site.pos.catalog`, `site.pos.clients`, `site.pos.ai_cart`, `site.pos.checkout`).
- `php artisan test --filter=PosModuleTest`: **14 passed (107 assertions)** en `1.11s`.
- `php artisan test` (suite completa): **69 passed (719 assertions)**, **3 failed** exclusivamente en el archivo preexistente `tests/Feature/SiteMediaPresentationTest.php` (proveniente de `794b740`, sin modificaciones ni regresiones en los demás módulos).
- **Smoke tests HTTP en vivo (kernel Laravel, sin mutar BD):**
  - `GET /punto-de-venta` → `HTTP 200` (`pos-root=OK`, `csrf=OK`).
  - `GET /api/pos/catalogo` → `HTTP 200` (JSON válido).
  - `GET /api/pos/clientes` → `HTTP 200` (JSON válido).
- **Conectividad Ollama local (`http://127.0.0.1:11434/api/tags`):** **HTTP 200**, modelo `qwen2.5:14b` disponible (`14.8B`, `Q4_K_M`, `8.99 GB`).

---

## 8. Retematización visual oficial (`Bodega Los Olivos`) y Catálogo

### Paleta y tipografía editorial unificada
El módulo POS fue retematizado para alinearse visualmente con la tienda pública (`Bodega Los Olivos`), conservando intactos el layout 65%/35%, el Modo Manos Libres, los atajos `F2`/`F4`/`ESC` y la impresión térmica 80mm (`#ffffff` / `#000000` en `@media print`):
- **Lienzo y superficies:** papel cálido `#F4EFE4` (`--site-paper`), lino `#EAE3D2` (`--site-linen`), hoja `#FAF7EF` (`--site-sheet`) y blanco `#FFFFFF` en tarjetas e inputs.
- **Tinta y acentos:** tinta principal `#192315` (`--site-ink`), texto secundario `#595548` / `#4C493E` (`--site-muted`), terracotta `#A44A28` (`--site-terracotta`) para el bloque IA/Voz, Manos Libres activo, categoría activa y badges de carrito, verde bosque `#263822` (`--site-forest`) para `COBRAR` y stock disponible, ámbar cálido `#B7791F` / `#D8B49A` para stock bajo / `FIADO` / `Libre`, y `#9F2B2B` para alertas, agotados y micrófono activo.
- **Tipografías:** `Newsreader` (`.font-editorial`) en encabezados principales del POS, `Plus Jakarta Sans` en interfaz y `JetBrains Mono` (`.font-mono-num`) en precios, SKUs, cantidades y `<kbd>`.
- **Archivos actualizados:** `resources/views/site/pos.blade.php`, `resources/css/pos.css`, `resources/js/pos/PosApp.tsx`, `PosCatalogo.tsx`, `PosTicket.tsx`, `VentaLibreModal.tsx`, `TicketModal.tsx` y `resources/js/pos/components/ui/{Badge,Button,Dialog,Input,Select}.tsx`.

### Catálogo base (`BodegaCatalogSeeder`)
- Se documenta el seeder `BodegaCatalogSeeder` orientado al catálogo inicial de **40 productos** de bodega para pruebas y operación inmediata en `/punto-de-venta`.
