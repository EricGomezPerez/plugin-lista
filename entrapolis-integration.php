<?php
/**
 * Plugin Name: Entrapolis
 * Plugin URI: https://github.com/your-org/entrapolis-plugin-lista
 * Description: Integración con Entrapolis — shortcodes para listar esdeveniments, calendari i widget de compra.
 * Version: 0.1.0
 * Author: Perception
 * Author URI: https://perception.es
 * Text Domain: entrapolis-plugin-lista
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin paths
define('ENTRAPOLIS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ENTRAPOLIS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ENTRAPOLIS_PLUGIN_FILE', __FILE__);

// Load configuration and helper functions
require_once ENTRAPOLIS_PLUGIN_DIR . 'includes/config.php';

// Load cache cleaner
require_once ENTRAPOLIS_PLUGIN_DIR . 'includes/cache-cleaner.php';

// Load admin settings (only in admin area)
if (is_admin()) {
    require_once ENTRAPOLIS_PLUGIN_DIR . 'includes/admin.php';
}

// Load shortcodes
require_once ENTRAPOLIS_PLUGIN_DIR . 'includes/shortcodes.php';

/**
 * Enqueue plugin styles
 */
function entrapolis_enqueue_styles()
{
    wp_enqueue_style(
        'entrapolis-styles',
        ENTRAPOLIS_PLUGIN_URL . 'assets/css/entrapolis-styles.css',
        array(),
        '0.1.5'
    );

    wp_enqueue_style(
        'entrapolis-styles-calendar',
        ENTRAPOLIS_PLUGIN_URL . 'assets/css/entrapolis-styles-calendar.css',
        array('entrapolis-styles'),
        '0.1.1'
    );

    wp_enqueue_style(
        'entrapolis-styles-detail',
        ENTRAPOLIS_PLUGIN_URL . 'assets/css/entrapolis-styles-detail.css',
        array('entrapolis-styles'),
        '0.1.1'
    );

    wp_enqueue_style(
        'entrapolis-styles-button',
        ENTRAPOLIS_PLUGIN_URL . 'assets/css/entrapolis-styles-button.css',
        array('entrapolis-styles'),
        '0.1.1'
    );
}
add_action('wp_enqueue_scripts', 'entrapolis_enqueue_styles');
