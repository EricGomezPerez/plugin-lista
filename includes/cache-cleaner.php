<?php
/**
 * Cache cleaner utility
 *
 * @package Entrapolis
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clear all Entrapolis transients cache
 */
function entrapolis_clear_cache()
{
    global $wpdb;

    // Delete all transients that start with 'entrapolis_'
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE %s 
            OR option_name LIKE %s",
            $wpdb->esc_like('_transient_entrapolis_') . '%',
            $wpdb->esc_like('_transient_timeout_entrapolis_') . '%'
        )
    );

    // Clear object cache if available
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }

    return true;
}

/**
 * Add clear cache button to admin settings page
 */
function entrapolis_add_cache_clear_button()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle cache clear action
    if (isset($_POST['entrapolis_clear_cache']) && check_admin_referer('entrapolis_clear_cache_action')) {
        entrapolis_clear_cache();
        add_settings_error('entrapolis_messages', 'cache_cleared', 'Caché limpiada correctamente', 'updated');
    }
}
add_action('admin_init', 'entrapolis_add_cache_clear_button');
