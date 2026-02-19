<?php
/**
 * Admin settings page
 *
 * @package Entrapolis
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu for plugin settings
 */
function entrapolis_add_admin_menu()
{
    add_menu_page(
        'Entrapolis',
        'Entrapolis',
        'manage_options',
        'entrapolis-settings',
        'entrapolis_settings_page',
        'dashicons-tickets-alt',
        30
    );
}
add_action('admin_menu', 'entrapolis_add_admin_menu');

/**
 * Register plugin settings
 */
function entrapolis_register_settings()
{
    register_setting('entrapolis_settings_group', 'entrapolis_api_token', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));

    register_setting('entrapolis_settings_group', 'entrapolis_uid', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));

    register_setting('entrapolis_settings_group', 'entrapolis_accent_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#22c55e'
    ));

    register_setting('entrapolis_settings_group', 'entrapolis_text_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#ffffff'
    ));

    register_setting('entrapolis_settings_group', 'entrapolis_generic_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#e31e24'
    ));

    register_setting('entrapolis_settings_group', 'entrapolis_generic_text_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#ffffff'
    ));
}
add_action('admin_init', 'entrapolis_register_settings');

/**
 * Settings page HTML
 */
function entrapolis_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings message
    if (isset($_GET['settings-updated'])) {
        add_settings_error('entrapolis_messages', 'entrapolis_message', 'Configuración guardada correctamente', 'updated');
    }

    settings_errors('entrapolis_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?> <span style="font-size: 0.6em; color: #666; font-weight: normal;">v<?php echo esc_html(ENTRAPOLIS_VERSION); ?></span></h1>

        <div class="entrapolis-admin-header">
            <p>Configura tu integración con Entrapolis. Necesitas un API token y tu ID de organización para que el plugin
                funcione correctamente.</p>
        </div>

        <form action="options.php" method="post">
            <?php
            settings_fields('entrapolis_settings_group');
            ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="entrapolis_api_token">API Token</label>
                        </th>
                        <td>
                            <input type="text" id="entrapolis_api_token" name="entrapolis_api_token"
                                value="<?php echo esc_attr(get_option('entrapolis_api_token', '')); ?>" class="regular-text"
                                placeholder="2b8b8b22b0842b1f40380a35a115839a...">
                            <p class="description">
                                Tu token de autenticación de Entrapolis. <strong>Mantén este valor seguro y
                                    privado.</strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="entrapolis_uid">Plugin WordPress UID</label>
                        </th>
                        <td>
                            <input type="text" id="entrapolis_uid" name="entrapolis_uid"
                                value="<?php echo esc_attr(get_option('entrapolis_uid')); ?>" class="regular-text"
                                placeholder="Introduce el UID generado en Entrapolis...">
                            <p class="description">
                                Identificador unico generado en Entrapolis para esta instalacion de WordPress. Obtendras
                                este UID al configurar la integracion en Entrapolis.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="entrapolis_accent_color">Color de Acento</label>
                        </th>
                        <td>
                            <input type="color" id="entrapolis_accent_color" name="entrapolis_accent_color"
                                value="<?php echo esc_attr(get_option('entrapolis_accent_color', '#22c55e')); ?>">
                            <p class="description">
                                Color para resaltar eventos en el calendario y botones (por defecto: verde #22c55e).
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="entrapolis_text_color">Color de Texto</label>
                        </th>
                        <td>
                            <input type="color" id="entrapolis_text_color" name="entrapolis_text_color"
                                value="<?php echo esc_attr(get_option('entrapolis_text_color', '#ffffff')); ?>">
                            <p class="description">
                                Color del texto sobre el color de acento. Usa blanco (#ffffff) para colores oscuros o negro
                                (#000000) para colores claros.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="entrapolis_generic_color">Color Genérico de Eventos</label>
                        </th>
                        <td>
                            <input type="color" id="entrapolis_generic_color" name="entrapolis_generic_color"
                                value="<?php echo esc_attr(get_option('entrapolis_generic_color', '#e31e24')); ?>">
                            <p class="description">
                                Color de fondo para eventos en el grid / formato panel (por defecto: rojo #e31e24).
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="entrapolis_generic_text_color">Color de Texto Genérico</label>
                        </th>
                        <td>
                            <input type="color" id="entrapolis_generic_text_color" name="entrapolis_generic_text_color"
                                value="<?php echo esc_attr(get_option('entrapolis_generic_text_color', '#ffffff')); ?>">
                            <p class="description">
                                Color del texto para eventos en el grid / formato panel (por defecto: blanco #ffffff).
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button('Guardar Configuración'); ?>
        </form>

        <hr>

        <h2>Limpiar Caché</h2>
        <p>Si los cambios no se ven reflejados, limpia la caché del plugin.</p>
        <form method="post" action="">
            <?php wp_nonce_field('entrapolis_clear_cache_action'); ?>
            <input type="hidden" name="entrapolis_clear_cache" value="1">
            <?php submit_button('Limpiar Caché', 'secondary', 'submit', false); ?>
        </form>

        <hr>
        <div class="entrapolis-admin-info">
            <h1>Uso de Shortcodes</h1>


            <h3>Listado de eventos (Grid)</h3>
            <code>[entrapolis_events limit="4" detail_page="detalle" lang="es"]</code>
            <p>Muestra un grid con los próximos eventos. Parámetros opcionales:</p>
            <ul>
                <li><code>limit</code>: Número máximo de eventos a mostrar (por defecto 4, 0 = sin límite)</li>
                <li><code>detail_page</code>: Slug de la página de detalle</li>
                <li><code>lang</code>: Idioma de los textos y fechas(<code>ca</code>, <code>es</code>, <code>en</code>)</li>
            </ul>

            <h3>Listado de eventos (Tabla)</h3>
            <code>[entrapolis_events_list limit="10" detail_page="detalle" lang="es"]</code>
            <p>Muestra una tabla con los eventos ordenados en columnas: imagen, título, fechas y botón de detalle.
                Parámetros opcionales:</p>
            <ul>
                <li><code>limit</code>: Número máximo de eventos a mostrar (por defecto 10, 0 = sin límite)</li>
                <li><code>detail_page</code>: Slug de la página de detalle</li>
                <li><code>lang</code>: Idioma de los textos y fechas(<code>ca</code>, <code>es</code>, <code>en</code>)</li>
            </ul>
            <p><strong>Características:</strong></p>
            <ul>
                <li>Muestra imagen miniatura (150x105px)</li>
                <li>Si un evento tiene múltiples fechas, las muestra todas en lista</li>
                <li>Si solo tiene una fecha, la muestra formateada en línea</li>
                <li>Botón "Veure detall" que enlaza a la página de detalle</li>
                <li>Responsive con scroll horizontal en móviles</li>
            </ul>

            <h3>Detalle de evento</h3>
            <code>[entrapolis_event id="123" lang="es"]</code>
            <p>Muestra el detalle de un evento específico.</p>
            <br>
            <code>[entrapolis_event lang="es"]</code>
            <p>Muestra el evento que se ha indicado desde el listado/calendario</p>

            <h3>Calendario</h3>
            <code>[entrapolis_calendar detail_page="detalle" lang="es"]</code>
            <p>Muestra un calendario interactivo con los eventos. Parámetros opcionales:</p>
            <ul>
                <li><code>detail_page</code>: Slug de la página de detalle</li>
                <li><code>lang</code>: Idioma de los textos y fechas (<code>ca</code>, <code>es</code>, <code>en</code>)
                </li>
            </ul>

            <h3>Billboard (Hero de Evento)</h3>
            <code>[entrapolis_billboard event_id="12345" detail_page="detalle" lang="es"]</code>
            <p>Muestra un evento destacado en formato hero a pantalla completa con imagen de fondo.</p>
            <p><strong>Parámetros:</strong></p>
            <ul>
                <li><code>event_id</code>: ID del evento a destacar (requerido)</li>
                <li><code>detail_page</code>: Slug de la página de detalle (opcional)</li>
                <li><code>lang</code>: Idioma de los textos y fechas (<code>ca</code>, <code>es</code>, <code>en</code>)
                </li>
            </ul>
            <p><strong>Características:</strong></p>
            <ul>
                <li>Diseño a pantalla completa con imagen de fondo</li>
                <li>Título en mayúsculas con texto blanco sobre fondo negro semi-transparente</li>
                <li>Botón de acción con colores configurables y traducido</li>
                <li>Ideal para destacar eventos principales en página de inicio</li>
            </ul>
        </div>
    </div>

    <style>
        .entrapolis-admin-header {
            background: #fff;
            border-left: 4px solid #2271b1;
            padding: 12px 20px;
            margin: 20px 0;
        }

        .entrapolis-admin-info {
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .entrapolis-admin-info h2 {
            margin-top: 0;
        }

        .entrapolis-admin-info h3 {
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .entrapolis-admin-info code {
            background: #fff;
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            display: inline-block;
            margin: 5px 0;
        }

        .entrapolis-admin-info ul {
            margin-left: 20px;
        }
    </style>
    <?php
}

/**
 * Add settings link on plugin page
 */
function entrapolis_add_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=entrapolis-settings">Configuración</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Get the plugin basename dynamically
$plugin_file = defined('ENTRAPOLIS_PLUGIN_FILE') ? ENTRAPOLIS_PLUGIN_FILE : __DIR__ . '/../entrapolis-integration.php';
add_filter('plugin_action_links_' . plugin_basename($plugin_file), 'entrapolis_add_settings_link');
