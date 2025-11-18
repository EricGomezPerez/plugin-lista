<?php
/**
 * Funciones auxiliares del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reemplazar URL de imágenes de Entrapolis por CDN
 */
function entrapolis_get_cdn_image($image_url)
{
    if (empty($image_url)) {
        return '';
    }
    return str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $image_url);
}

/**
 * Obtener URL de detalle de evento
 */
function entrapolis_get_detail_url($event_id, $detail_page_slug = '')
{
    if (empty($detail_page_slug)) {
        return '';
    }
    return home_url('/' . $detail_page_slug . '/?entrapolis_event=' . $event_id);
}

/**
 * Enqueue de estilos y scripts
 */
function entrapolis_enqueue_assets()
{
    // Estilos comunes
    wp_enqueue_style(
        'entrapolis-common',
        ENTRAPOLIS_PLUGIN_URL . 'assets/css/entrapolis-common.css',
        array(),
        '0.2.0'
    );

    // Estilos del calendario
    wp_enqueue_style(
        'entrapolis-calendar',
        ENTRAPOLIS_PLUGIN_URL . 'assets/css/entrapolis-calendar.css',
        array('entrapolis-common'),
        '0.2.0'
    );

    // Estilos del listado de eventos
    wp_enqueue_style(
        'entrapolis-events-list',
        ENTRAPOLIS_PLUGIN_URL . 'assets/css/entrapolis-events-list.css',
        array('entrapolis-common'),
        '0.2.0'
    );

    // Estilos del detalle de evento
    wp_enqueue_style(
        'entrapolis-event-detail',
        ENTRAPOLIS_PLUGIN_URL . 'assets/css/entrapolis-event-detail.css',
        array('entrapolis-common'),
        '0.2.0'
    );

    // Estilos del widget de compra
    wp_enqueue_style(
        'entrapolis-buy-widget',
        ENTRAPOLIS_PLUGIN_URL . 'assets/css/entrapolis-buy-widget.css',
        array('entrapolis-common'),
        '0.2.0'
    );

    // Scripts
    wp_enqueue_script(
        'entrapolis-calendar-js',
        ENTRAPOLIS_PLUGIN_URL . 'assets/js/entrapolis-calendar.js',
        array(),
        '0.2.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'entrapolis_enqueue_assets');
