<?php
/**
 * Plugin Name: Entrapolis
 * Plugin URI: https://blog.entrapolis.com/entrapolis-estrena-mejoras-qr-automatico-y-pronto-integracion-con-wordpress/
 * Description: Integración con Entrapolis — shortcodes para listar eventos, calendario y widget de compra.
 * Version: 0.2.6
 * Author: Entrapolis.com
 * Author URI: https://www.entrapolis.com
 * Text Domain: entrapolis-plugin
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin paths and version
define('ENTRAPOLIS_VERSION', '0.2.6');
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

if (is_admin()) {
  require_once __DIR__ . '/includes/updater.php';
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

    wp_enqueue_style(
        'entrapolis-styles-list',
        ENTRAPOLIS_PLUGIN_URL . 'assets/css/entrapolis-styles-list.css',
        array('entrapolis-styles'),
        '0.1.3'
    );

    wp_enqueue_style(
        'entrapolis-styles-billboard',
        ENTRAPOLIS_PLUGIN_URL . 'assets/css/entrapolis-styles-billboard.css',
        array('entrapolis-styles'),
        '0.1.0'
    );
}
add_action('wp_enqueue_scripts', 'entrapolis_enqueue_styles');

/**
 * Add dynamic CSS for accent color
 */
function entrapolis_dynamic_styles()
{
    $accent_color = entrapolis_get_accent_color();
    $text_color = entrapolis_get_text_color();

    // Calculate darker shades for hover states
    $rgb = sscanf($accent_color, "#%02x%02x%02x");
    $darker_hover = sprintf(
        "#%02x%02x%02x",
        max(0, $rgb[0] - 30),
        max(0, $rgb[1] - 30),
        max(0, $rgb[2] - 30)
    );
    $darkest_hover = sprintf(
        "#%02x%02x%02x",
        max(0, $rgb[0] - 50),
        max(0, $rgb[1] - 50),
        max(0, $rgb[2] - 50)
    );

    $custom_css = "
        /* Calendar horizontal events */
        .entrapolis-calendar-horizontal .entrapolis-calendar-day.has-events {
            background: {$accent_color} !important;
        }
        .entrapolis-calendar-horizontal .entrapolis-calendar-day.has-events .entrapolis-calendar-day-name,
        .entrapolis-calendar-horizontal .entrapolis-calendar-day.has-events .entrapolis-calendar-day-number {
            color: {$text_color} !important;
        }
        .entrapolis-calendar-horizontal .entrapolis-calendar-day.has-events.is-today {
            background: {$darker_hover} !important;
        }
        .entrapolis-calendar-horizontal .entrapolis-calendar-day.has-events:hover,
        .entrapolis-calendar-horizontal .entrapolis-calendar-day.has-events.is-today:hover {
            background: {$darkest_hover} !important;
        }
        
        /* Calendar grid events */
        .entrapolis-calendar-grid .entrapolis-calendar-day.has-events {
            background: {$accent_color} !important;
        }
        .entrapolis-calendar-grid .entrapolis-calendar-day.has-events .entrapolis-calendar-day-number {
            color: {$text_color} !important;
        }
        .entrapolis-calendar-grid .entrapolis-calendar-day.has-events.is-today {
            background: {$darker_hover} !important;
        }
        .entrapolis-calendar-grid .entrapolis-calendar-day.has-events:hover {
            background: {$darkest_hover} !important;
        }
        
        /* Load more button */
        .entrapolis-load-more-btn {
            background: {$accent_color} !important;
            color: {$text_color} !important;
        }
        .entrapolis-load-more-btn:hover {
            background: {$darker_hover} !important;
            color: {$text_color} !important;
        }
        
        /* Primary button */
        .entrapolis-btn-primary {
            background: {$accent_color} !important;
            color: {$text_color} !important;
        }
        .entrapolis-btn-primary:hover {
            background: {$darker_hover} !important;
            color: {$text_color} !important;
        }
        
        /* Detail button */
        .entrapolis-btn-detail {
            background: {$accent_color} !important;
            color: {$text_color} !important;
            border-color: {$accent_color} !important;
        }
        .entrapolis-btn-detail:hover {
            background: {$darker_hover} !important;
            color: {$text_color} !important;
            border-color: {$darker_hover} !important;
        }
        
        /* Buy tickets button in detail page */
        .entrapolis-event-buy-link {
            background: {$accent_color} !important;
            color: {$text_color} !important;
            border-color: {$accent_color} !important;
        }
        .entrapolis-event-buy-link:hover {
            background: {$darker_hover} !important;
            color: {$text_color} !important;
            border-color: {$darker_hover} !important;
        }
        
        /* Buy button in list view */
        .entrapolis-btn-buy {
            background: {$accent_color} !important;
            color: {$text_color} !important;
            border-color: {$accent_color} !important;
        }
        .entrapolis-btn-buy:hover {
            background: {$darker_hover} !important;
            color: {$text_color} !important;
            border-color: {$darker_hover} !important;
        }
        
        /* Billboard button */
        .entrapolis-billboard-btn {
            background: {$accent_color} !important;
            color: {$text_color} !important;
        }
        .entrapolis-billboard-btn:hover {
            background: {$darker_hover} !important;
            color: {$text_color} !important;
        }
    ";

    wp_add_inline_style('entrapolis-styles', $custom_css);
}
add_action('wp_enqueue_scripts', 'entrapolis_dynamic_styles', 20);
