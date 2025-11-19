# Entrapolis Plugin (Integración)

Pequeño plugin para integrar contenidos de Entrapolis en WordPress mediante shortcodes: listado de eventos, detalle, widget de compra y calendario.

## Estructura del Plugin

```
entrapolis-plugin-lista/
├── entrapolis-integration.php    # Archivo principal (carga módulos)
├── includes/
│   ├── config.php                # Configuración y funciones helper
│   ├── admin.php                 # Página de administración
│   └── shortcodes.php            # Todos los shortcodes
├── assets/
│   ├── css/                      # Estilos del plugin
│   └── placeholder.txt           # Screenshots y banners
├── README.md
├── LICENSE
└── .gitignore
```

## Instalación

- Subir el directorio `entrapolis-plugin-lista` a `wp-content/plugins/`.
- Activar el plugin desde el panel de administración de WordPress.
- **Configuración del API Token** (3 opciones, por orden de prioridad):

  1. **Método recomendado**: Ir a **WordPress Admin → Entrapolis** y completar:
     - API Token
     - ID de Organización

  2. Alternativa: añadir en `wp-config.php`:
     ```php
     define('ENTRAPOLIS_API_TOKEN', 'tu_token_aqui');
     define('ENTRAPOLIS_ORG_ID', 2910);
     ```

  3. Alternativa: exportar la variable de entorno `ENTRAPOLIS_API_TOKEN` en el servidor.

## Uso (shortcodes)

- Listado de eventos:
  - `[entrapolis_events org="2910" detail_page="detalle-slug" limit="4"]`

- Detalle de evento:
  - `[entrapolis_event id="123"]` (o usar `?entrapolis_event=123` en la URL si `detail_page` está configurado)

- Widget de compra:
  - `[entrapolis_buy id="123"]`

- Calendario:
  - `[entrapolis_calendar org="2910" months="3" detail_page="detalle-slug"]`

## Seguridad y buenas prácticas

- Nunca comitees tu `ENTRAPOLIS_API_TOKEN` en el repositorio.
- Añade tus credenciales en `wp-config.php` o utiliza variables de entorno.
- Usa `.gitignore` incluido para evitar subir archivos locales o secretos.

## Preparar para GitHub

- Inicializa el repositorio localmente:

  ```powershell
  cd path\to\entrapolis-plugin-lista
  git init
  git add .
  git commit -m "Initial plugin import"
  git remote add origin https://github.com/YOUR_USER/entrapolis-plugin-lista.git
  git push -u origin main
  ```

- Crear un release (ZIP) en GitHub: en la página del repo -> Releases -> Draft a new release -> upload zip.

## Contribuir

- Pull requests bienvenidos. Por favor, evita añadir tokens o credenciales en los PRs.

## Licencia

Este plugin se distribuye bajo la licencia GPLv2 o posterior. Consulta `LICENSE`.
