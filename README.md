# Entrapolis Plugin

Plugin de WordPress para integrar eventos de Entrapolis mediante shortcodes personalizables. Incluye listado de eventos (grid y tabla), calendario responsive, página de detalle y sistema de compra con ventana emergente.

## Características

- **5 Shortcodes configurables**: Grid, Lista, Detalle, Compra y Calendario
- **Colores personalizables**: Configura accent color y text color desde el panel de administración
- **Calendario dual responsive**: Vista horizontal en desktop, grid mensual en móvil (Lunes-Domingo)
- **Compra con overlay**: Ventana popup centrada con overlay oscuro y botón cerrar
- **Load more con AJAX**: Carga paginada de eventos en grid y lista
- **Cache inteligente**: WordPress transients con TTL de 5 minutos
- **Click en fila**: Toda la fila de la lista redirige al detalle del evento
- **Diseño limpio**: Sin cards elevadas, integrado con el diseño de la página

## Estructura del Plugin

```
entrapolis-plugin-lista/
├── entrapolis-integration.php          # Archivo principal (carga módulos, CSS dinámico)
├── includes/
│   ├── config.php                      # Configuración, API helpers, AJAX handlers
│   ├── admin.php                       # Panel de administración con 4 settings
│   ├── cache-cleaner.php               # Limpieza manual de cache
│   ├── shortcodes.php                  # Loader de shortcodes
│   └── shortcodes/
│       ├── shortcode_events_grid.php   # [entrapolis_events] Grid 2 columnas
│       ├── shortcode_events_list.php   # [entrapolis_events_list] Tabla
│       ├── shortcode_events_detail.php # [entrapolis_event] Detalle dos columnas
│       ├── shortcode_events_buy.php    # [entrapolis_buy] Widget compra
│       └── shortcode_events_calendar.php # [entrapolis_calendar] Calendario dual
├── assets/
│   └── css/
│       ├── entrapolis-styles.css       # Estilos grid (v0.1.5)
│       ├── entrapolis-styles-list.css  # Estilos tabla (v0.1.3)
│       ├── entrapolis-styles-calendar.css # Estilos calendario dual
│       ├── entrapolis-styles-detail.css   # Estilos detalle + overlay
│       └── entrapolis-styles-button.css   # Estilos botones load more
├── README.md
├── LICENSE
└── .gitignore
```

## Instalación

1. Subir el directorio `entrapolis-plugin-lista` a `wp-content/plugins/`
2. Activar el plugin desde el panel de administración de WordPress
3. Ir a **WordPress Admin → Entrapolis** y configurar:

### Configuración API (Requerido)

- **API Token**: Token de autenticación de Entrapolis
- **ID de Organización**: ID numérico de tu organización

### Personalización (Opcional)

- **Accent Color**: Color principal de botones y elementos activos (default: `#22c55e`)
- **Text Color**: Color del texto en elementos coloreados (default: `#ffffff`)

### Métodos alternativos de configuración

**Opción 2**: Añadir en `wp-config.php`:
```php
define('ENTRAPOLIS_API_TOKEN', 'tu_token_aqui');
define('ENTRAPOLIS_ORG_ID', 2910);
```

**Opción 3**: Variables de entorno del servidor:
```bash
export ENTRAPOLIS_API_TOKEN="tu_token_aqui"
```

**Prioridad**: Admin Settings > wp-config.php > Variables de entorno

## Uso de Shortcodes

### 1. Grid de Eventos (2 columnas con hover)
```
[entrapolis_events org="2910" detail_page="evento" limit="4"]
```
**Parámetros:**
- `org`: ID de organización (opcional, usa el del admin)
- `detail_page`: Slug de la página de detalle
- `limit`: Eventos por carga (default: 4)

**Características:**
- Grid responsive 2 columnas
- Hover con elevación
- Botón "Load more" con AJAX
- Agrupación por título + imagen

### 2. Lista de Eventos (tabla)
```
[entrapolis_events_list org="2910" detail_page="evento" limit="10"]
```
**Características:**
- Tabla con 4 columnas: imagen, título, fechas, acción
- Click en fila → redirige al detalle
- Botón "Comprar entrades" → abre popup con overlay
- Load more con AJAX
- Scroll horizontal en móvil

### 3. Detalle de Evento (dos columnas)
```
[entrapolis_event]
```
**Detecta automáticamente** el evento desde `?entrapolis_event=ID` en la URL

**Características:**
- Layout: 45% imagen izquierda, 55% contenido derecha
- Secciones: título, categoría, ubicación, descripción, fechas
- Botón compra → ventana popup centrada (900x700px)
- Overlay oscuro con botón cerrar (×)
- Responsive: apila verticalmente en móvil

### 4. Calendario (dual responsive)
```
[entrapolis_calendar org="2910" months="3" detail_page="evento"]
```
**Parámetros:**
- `months`: Meses a mostrar (default: 3)

**Características:**
- **Desktop**: Vista horizontal (días en fila)
- **Móvil**: Grid mensual 7 columnas (Lunes-Domingo)
- Navegación prev/next
- Días con eventos destacados en accent color
- Click en día → va al detalle del primer evento

### 5. Widget de Compra
```
[entrapolis_buy event_id="12345"]
```
**Características:**
- Iframe incrustado 90% ancho, 1000px alto
- Widget oficial de Entrapolis

---

### 6. Billboard (Hero de Evento)
```
[entrapolis_billboard event_id="12345" detail_page="detalle"]
```
**Parámetros:**
- `event_id` (requerido): ID del evento a destacar
- `detail_page` (opcional): Slug de la página de detalle

**Características:**
- Diseño a pantalla completa (altura de viewport - 150px)
- Imagen de fondo del evento a tamaño completo
- Caja de contenido con fondo negro semi-transparente (50% opacidad)
- Título en mayúsculas (3.5rem) con texto blanco para máxima legibilidad
- Descripción del evento en texto blanco
- Botón de acción con colores configurables del admin
- Posicionamiento absoluto en esquina inferior izquierda
- Responsive y optimizado para todos los tamaños de pantalla

**Uso recomendado:**
- Página de inicio para destacar evento principal
- Landings de campañas específicas
- Promoción de eventos especiales o destacados

## Características Técnicas

### API Integration
- **Endpoint**: `https://www.entrapolis.com/api/events/` (POST)
- **Autenticación**: Header `X-Ep-Auth-Token`
- **Cache**: WordPress transients (5 minutos)
- **Limpieza manual**: Panel de administración

### Colores Dinámicos
Los colores configurados en el admin se aplican automáticamente a:
- Días con eventos en calendario
- Botones "Load more"
- Botones de detalle
- Botones de compra
- Hover states (calculado automáticamente: RGB -30/-50)

### Sistema de Compra
**Página de detalle**:
- Botón → `window.open()` centrado (900x700px)
- Overlay oscuro (rgba 0,0,0,0.7) cubre toda la pantalla
- Botón cerrar (×) posicionado a 70px desde arriba
- Detecta cierre automático de ventana

**Lista de eventos**:
- Click en fila → detalle del evento
- Botón "Comprar entrades" → mismo sistema popup + overlay

### AJAX Load More
**Handlers**:
- `wp_ajax_entrapolis_load_more_grid`
- `wp_ajax_entrapolis_load_more_list`

**Respuesta**:
```json
{
  "success": true,
  "data": {
    "html": "...",
    "has_more": true
  }
}
```

### Responsive Design
- **Desktop**: Grid 2 columnas, calendario horizontal
- **Tablet** (< 768px): Grid 1 columna, calendario grid mensual
- **Móvil**: Tabla con scroll horizontal, overlay 90% ancho

## Desarrollo

### Versiones CSS
- `entrapolis-styles.css`: v0.1.5
- `entrapolis-styles-list.css`: v0.1.3
- `entrapolis-styles-calendar.css`: v0.1.1
- `entrapolis-styles-detail.css`: v0.1.1
- `entrapolis-styles-button.css`: v0.1.1

### Estructura de datos (API)
```php
$event = [
  'id' => 12345,
  'title' => 'Título del evento',
  'image' => 'https://www.entrapolis.com/path/image.jpg',
  'date_readable' => '2024-12-25 20:00:00',
  'category' => 'Teatre',
  'description' => 'Descripción...',
  'location' => 'Teatre Principal',
  'url' => 'https://...',
  'url_widget' => 'https://www.entrapolis.com/entradas/.../widget'
];
```

### Agrupación de eventos
Los eventos se agrupan por `title + image` para mostrar múltiples fechas del mismo evento como un único item con lista de fechas.

## Seguridad

- ✅ Nunca comitees `ENTRAPOLIS_API_TOKEN` en el repositorio
- ✅ `.gitignore` incluido para proteger credenciales
- ✅ Sanitización con `esc_html()`, `esc_url()`, `esc_attr()`
- ✅ Nonces no necesarios (endpoints públicos read-only)

## Preparar para GitHub

```powershell
cd path\to\entrapolis-plugin-lista
git init
git add .
git commit -m "Initial plugin release"
git remote add origin https://github.com/YOUR_USER/entrapolis-plugin-lista.git
git push -u origin main
```

**Crear release ZIP**:
1. GitHub → Releases → Draft a new release
2. Tag: `v0.1.0`
3. Upload ZIP del directorio del plugin

## Testing en Móvil (localhost)

### Opción 1: Misma red WiFi
```powershell
ipconfig  # Obtén tu IP local (ej: 192.168.1.100)
```

Edita `wp-config.php`:
```php
define('WP_HOME', 'http://192.168.1.100');
define('WP_SITEURL', 'http://192.168.1.100');
```

Accede desde móvil: `http://192.168.1.100`

### Opción 2: ngrok (túnel público)
```powershell
ngrok http 80
```
Copia la URL generada y actualiza `wp-config.php`

## Contribuir

Pull requests bienvenidos. Por favor:
- No incluyas tokens o credenciales
- Mantén la estructura modular
- Documenta nuevos shortcodes o parámetros
- Actualiza versiones CSS si modificas estilos

## Soporte

- API Entrapolis: https://www.entrapolis.com/api/events/
- WordPress Codex: https://codex.wordpress.org/

## Licencia

GPLv2 o posterior. Ver `LICENSE`.
