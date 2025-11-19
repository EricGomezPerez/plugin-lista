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

    register_setting('entrapolis_settings_group', 'entrapolis_org_id', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 2910
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
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

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
                            <label for="entrapolis_org_id">ID de Organización</label>
                        </th>
                        <td>
                            <input type="number" id="entrapolis_org_id" name="entrapolis_org_id"
                                value="<?php echo esc_attr(get_option('entrapolis_org_id', 2910)); ?>" class="small-text"
                                min="1" step="1">
                            <p class="description">
                                El ID de tu organización en Entrapolis (application_id).
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
            <h2>Uso de Shortcodes</h2>

            <h3>Listado de eventos</h3>
            <code>[entrapolis_events limit="4" detail_page="detalle"]</code>
            <p>Muestra un grid con los próximos eventos. Parámetros opcionales:</p>
            <ul>
                <li><code>org</code>: ID de organización (por defecto usa el configurado arriba)</li>
                <li><code>limit</code>: Número máximo de eventos a mostrar</li>
                <li><code>detail_page</code>: Slug de la página de detalle</li>
            </ul>

            <h3>Detalle de evento</h3>
            <code>[entrapolis_event id="123"]</code>
            <p>Muestra el detalle de un evento específico.</p>

            <h3>Widget de compra</h3>
            <code>[entrapolis_buy id="123"]</code>
            <p>Muestra el widget de compra de entradas para un evento.</p>

            <h3>Calendario</h3>
            <code>[entrapolis_calendar detail_page="detalle"]</code>
            <p>Muestra un calendario interactivo con los eventos. Parámetros opcionales:</p>
            <ul>
                <li><code>detail_page</code>: Slug de la página de detalle</li>
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
