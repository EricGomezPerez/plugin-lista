<?php
/**
 * Clase para manejar las llamadas a la API de Entrapolis
 */

if (!defined('ABSPATH')) {
    exit;
}

class Entrapolis_API
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Realizar una petición POST a la API de Entrapolis
     */
    public function post($endpoint, $data = array())
    {
        $url = ENTRAPOLIS_API_BASE . $endpoint;

        $response = wp_remote_post($url, array(
            'body' => json_encode($data),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Error descodificant la resposta JSON');
        }

        return $result;
    }

    /**
     * Obtener eventos de una organización
     */
    public function get_events($org_id, $use_cache = true)
    {
        $cache_key = 'entrapolis_events_' . $org_id;

        if ($use_cache) {
            $events = get_transient($cache_key);
            if ($events !== false) {
                return $events;
            }
        }

        $result = $this->post('/api/events/', array(
            'token' => ENTRAPOLIS_API_TOKEN,
            'application_id' => $org_id,
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        if (empty($result['events']) || !is_array($result['events'])) {
            return array();
        }

        $events = $result['events'];
        set_transient($cache_key, $events, 5 * 60);

        return $events;
    }

    /**
     * Obtener un evento específico
     */
    public function get_event($event_id, $use_cache = true)
    {
        $cache_key = 'entrapolis_event_' . $event_id;

        if ($use_cache) {
            $result = get_transient($cache_key);
            if ($result !== false) {
                return $result;
            }
        }

        $result = $this->post('event/get', array(
            'token' => ENTRAPOLIS_API_TOKEN,
            'id' => $event_id,
        ));

        if (!$result || !isset($result['event'])) {
            return new WP_Error('event_not_found', 'Esdeveniment no trobat');
        }

        set_transient($cache_key, $result, 300);

        return $result;
    }
}
