<?php
/**
 * Shortcodes Loader - Load all individual shortcode files
 *
 * @package Entrapolis
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once ENTRAPOLIS_PLUGIN_DIR . 'includes/shortcodes/shortcode_events_list.php';
require_once ENTRAPOLIS_PLUGIN_DIR . 'includes/shortcodes/shortcode_events_grid.php';
require_once ENTRAPOLIS_PLUGIN_DIR . 'includes/shortcodes/shortcode_events_detail.php';
require_once ENTRAPOLIS_PLUGIN_DIR . 'includes/shortcodes/shortcode_events_calendar.php';