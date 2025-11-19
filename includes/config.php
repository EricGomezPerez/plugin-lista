<?php
/**
 * Configuration and helper functions
 *
 * @package Entrapolis
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
Configuración de Locale en español
*/
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'spanish');

/*
Definiciones de constantes
*/
define('ENTRAPOLIS_API_BASE', 'http://www.entrapolis.com');

/**
 * Get API token from settings, wp-config, or environment
 */
function entrapolis_get_api_token()
{
    // 1. Check admin settings
    $token = get_option('entrapolis_api_token', '');
    if (!empty($token)) {
        return $token;
    }

    // 2. Check wp-config constant
    if (defined('ENTRAPOLIS_API_TOKEN') && !empty(ENTRAPOLIS_API_TOKEN)) {
        return ENTRAPOLIS_API_TOKEN;
    }

    // 3. Check environment variable
    $env_token = getenv('ENTRAPOLIS_API_TOKEN');
    if ($env_token !== false && $env_token !== '') {
        return $env_token;
    }

    return '';
}

/**
 * Get organization ID from settings or wp-config
 */
function entrapolis_get_org_id()
{
    // 1. Check admin settings
    $org_id = get_option('entrapolis_org_id', '');
    if (!empty($org_id)) {
        return intval($org_id);
    }

    // 2. Check wp-config constant
    if (defined('ENTRAPOLIS_ORG_ID')) {
        return ENTRAPOLIS_ORG_ID;
    }

    // 3. Default fallback
    return 2910;
}

// Define constants for backwards compatibility
define('ENTRAPOLIS_ORG_ID', entrapolis_get_org_id());
define('ENTRAPOLIS_API_TOKEN', entrapolis_get_api_token());

/**
 * Helper per cridar a l'API d'Entrapolis
 */
function entrapolis_api_post($endpoint, $body = array())
{
    if (empty(ENTRAPOLIS_API_TOKEN)) {
        return new WP_Error('entrapolis_no_token', 'Entrapolis API token not configured. Define ENTRAPOLIS_API_TOKEN in wp-config.php or set the environment variable ENTRAPOLIS_API_TOKEN.');
    }
    $url = trailingslashit(ENTRAPOLIS_API_BASE) . ltrim($endpoint, '/');

    $response = wp_remote_post($url, array(
        'timeout' => 10,
        'headers' => array(
            'X-Ep-Auth-Token' => ENTRAPOLIS_API_TOKEN,
        ),
        'body' => $body,
    ));

    if (is_wp_error($response)) {
        return new WP_Error('entrapolis_http_error', $response->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);

    if ($code !== 200) {
        $message = isset($data['result']['message']) ? $data['result']['message'] : 'API error';
        return new WP_Error('entrapolis_api_error', "HTTP $code - $message");
    }

    return $data;
}

/**
 * Get Catalan month names
 */
function entrapolis_get_catalan_months()
{
    return array(
        1 => 'gener',
        2 => 'febrer',
        3 => 'març',
        4 => 'abril',
        5 => 'maig',
        6 => 'juny',
        7 => 'juliol',
        8 => 'agost',
        9 => 'setembre',
        10 => 'octubre',
        11 => 'novembre',
        12 => 'desembre'
    );
}

/**
 * Get Catalan day names (short)
 */
function entrapolis_get_catalan_days()
{
    return array('Dg', 'Dl', 'Dt', 'Dc', 'Dj', 'Dv', 'Ds');
}

/**
 * Get category colors mapping
 */
function entrapolis_get_category_colors()
{
    return array(
        'Teatre' => '#ca1818',
        'Ballet' => '#bf05a4',
        'Música' => '#1a8cff',
        'Teatre Familiar' => '#ea8b00',
        'Dansa' => '#a1248e',
        'Generic' => '#707070',
    );
}
