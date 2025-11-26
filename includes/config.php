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
Configuracion de Locales en catalan
*/

setlocale(LC_TIME, 'ca_ES.UTF-8', 'ca_ES', 'catalan');

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

/**
 * Get accent color from settings
 */
function entrapolis_get_accent_color()
{
    return get_option('entrapolis_accent_color', '#22c55e');
}

/**
 * Get text color from settings
 */
function entrapolis_get_text_color()
{
    return get_option('entrapolis_text_color', '#ffffff');
}

/**
 * AJAX handler for loading more events (grid view)
 */
function entrapolis_ajax_load_more_grid()
{
    $org_id = isset($_POST['org_id']) ? intval($_POST['org_id']) : 0;
    $detail_page = isset($_POST['detail_page']) ? sanitize_text_field($_POST['detail_page']) : '';
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 4;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

    if (!$org_id) {
        wp_send_json_error('Invalid organization ID');
        return;
    }

    $cache_key = 'entrapolis_events_' . $org_id;
    $events = get_transient($cache_key);

    if ($events === false) {
        $result = entrapolis_api_post('/api/events/', array(
            'application_id' => $org_id,
        ));

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }

        if (empty($result['events']) || !is_array($result['events'])) {
            wp_send_json_error('No events available');
            return;
        }

        $events = $result['events'];
        set_transient($cache_key, $events, 5 * 60);
    }

    // Agrupar eventos
    $grouped_events = array();
    foreach ($events as $event) {
        $key = $event['title'] . '|' . $event['image'];
        if (!isset($grouped_events[$key])) {
            $grouped_events[$key] = array(
                'title' => $event['title'],
                'image' => $event['image'],
                'url' => $event['url'],
                'url_widget' => $event['url_widget'],
                'category' => isset($event['category']) ? $event['category'] : 'Generic',
                'description' => isset($event['description']) ? $event['description'] : '',
                'dates' => array(),
                'ids' => array(),
            );
        }
        $grouped_events[$key]['dates'][] = $event['date_readable'];
        $grouped_events[$key]['ids'][] = $event['id'];
    }

    $total_events = count($grouped_events);
    $events_slice = array_slice($grouped_events, $offset, $limit);
    $has_more = ($offset + $limit) < $total_events;

    $category_colors = entrapolis_get_category_colors();
    $months_catalan = entrapolis_get_catalan_months();

    ob_start();
    foreach ($events_slice as $event):
        $id = intval($event['ids'][0]);
        $title = esc_html($event['title']);
        $category = esc_html($event['category']);
        $description = esc_html($event['description']);

        if (strlen($description) > 150) {
            $description = substr($description, 0, 150) . '…';
        }

        $image = !empty($event['image']) ? str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $event['image']) : '';
        $category_color = isset($category_colors[$category]) ? $category_colors[$category] : '#707070';

        $first_date = $event['dates'][0];
        preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $first_date, $matches);

        if ($matches) {
            $year = $matches[1];
            $month_num = intval($matches[2]);
            $day = intval($matches[3]);
            $hour = $matches[4];
            $minute = $matches[5];
            $month_name = isset($months_catalan[$month_num]) ? $months_catalan[$month_num] : $month_num;
            $formatted_date = "$day de $month_name de $year $hour:$minute";
        } else {
            $formatted_date = $first_date;
        }

        $detail_url = '';
        if ($detail_page) {
            $detail_url = home_url('/' . $detail_page . '/?entrapolis_event=' . $id);
        }
        ?>
        <div class="entrapolis-event-card">
            <a class="entrapolis-event-link" href="<?php echo esc_url($detail_url); ?>">
                <figure class="entrapolis-event-figure" style="background-image: url('<?php echo esc_url($image); ?>');">
                    <figcaption class="entrapolis-event-caption" style="background-color:<?php echo $category_color; ?>;">
                        <h3 class="entrapolis-event-date"><?php echo $formatted_date; ?></h3>
                        <h2 class="entrapolis-event-title"><?php echo $title; ?></h2>
                        <?php if ($description): ?>
                            <p class="entrapolis-event-excerpt"><?php echo $description; ?></p>
                        <?php endif; ?>
                    </figcaption>
                </figure>
            </a>
        </div>
    <?php endforeach;
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html,
        'has_more' => $has_more
    ));
}
add_action('wp_ajax_entrapolis_load_more_grid', 'entrapolis_ajax_load_more_grid');
add_action('wp_ajax_nopriv_entrapolis_load_more_grid', 'entrapolis_ajax_load_more_grid');

/**
 * AJAX handler for loading more events (list view)
 */
function entrapolis_ajax_load_more_list()
{
    $org_id = isset($_POST['org_id']) ? intval($_POST['org_id']) : 0;
    $detail_page = isset($_POST['detail_page']) ? sanitize_text_field($_POST['detail_page']) : '';
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 4;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

    if (!$org_id) {
        wp_send_json_error('Invalid organization ID');
        return;
    }

    $cache_key = 'entrapolis_events_' . $org_id;
    $events = get_transient($cache_key);

    if ($events === false) {
        $result = entrapolis_api_post('/api/events/', array(
            'application_id' => $org_id,
        ));

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }

        if (empty($result['events']) || !is_array($result['events'])) {
            wp_send_json_error('No events available');
            return;
        }

        $events = $result['events'];
        set_transient($cache_key, $events, 5 * 60);
    }

    // Agrupar eventos
    $grouped_events = array();
    foreach ($events as $event) {
        $key = $event['title'] . '|' . $event['image'];
        if (!isset($grouped_events[$key])) {
            $grouped_events[$key] = array(
                'title' => $event['title'],
                'image' => $event['image'],
                'url' => $event['url'],
                'url_widget' => $event['url_widget'],
                'category' => isset($event['category']) ? $event['category'] : 'Generic',
                'description' => isset($event['description']) ? $event['description'] : '',
                'dates' => array(),
                'ids' => array(),
            );
        }
        $grouped_events[$key]['dates'][] = $event['date_readable'];
        $grouped_events[$key]['ids'][] = $event['id'];
    }

    $total_events = count($grouped_events);
    $events_slice = array_slice($grouped_events, $offset, $limit);
    $has_more = ($offset + $limit) < $total_events;

    $months_catalan = entrapolis_get_catalan_months();

    ob_start();
    foreach ($events_slice as $event):
        $id = intval($event['ids'][0]);
        $title = esc_html($event['title']);
        $image = !empty($event['image']) ? str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $event['image']) : '';

        $buy_url = !empty($event['url_widget']) ? $event['url_widget'] : (!empty($event['url']) ? $event['url'] : '');

        $detail_url = '';
        if ($detail_page) {
            $detail_url = home_url('/' . $detail_page . '/?entrapolis_event=' . $id);
        }

        // Formatear todas las fechas
        $formatted_dates = array();
        foreach ($event['dates'] as $date) {
            preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date, $matches);
            if ($matches) {
                $year = $matches[1];
                $month_num = intval($matches[2]);
                $day = intval($matches[3]);
                $hour = $matches[4];
                $minute = $matches[5];
                $month_name = isset($months_catalan[$month_num]) ? $months_catalan[$month_num] : $month_num;
                $formatted_dates[] = "$day de $month_name de $year a les $hour:$minute";
            } else {
                $formatted_dates[] = $date;
            }
        }
        ?>
        <tr class="entrapolis-event-row" data-detail-url="<?php echo esc_url($detail_url); ?>" style="cursor: pointer;">
            <td class="col-image">
                <?php if ($image): ?>
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" class="entrapolis-list-image">
                <?php endif; ?>
            </td>
            <td class="col-title">
                <strong><?php echo $title; ?></strong>
            </td>
            <td class="col-dates">
                <?php if (count($formatted_dates) === 1): ?>
                    <span class="single-date"><?php echo esc_html($formatted_dates[0]); ?></span>
                <?php else: ?>
                    <ul class="dates-list">
                        <?php foreach ($formatted_dates as $date): ?>
                            <li><?php echo esc_html($date); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </td>
            <td class="col-action">
                <?php if ($buy_url): ?>
                    <a href="javascript:void(0);" class="entrapolis-btn-buy" data-buy-url="<?php echo esc_url($buy_url); ?>">
                        Comprar entrades
                    </a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach;
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html,
        'has_more' => $has_more
    ));
}
add_action('wp_ajax_entrapolis_load_more_list', 'entrapolis_ajax_load_more_list');
add_action('wp_ajax_nopriv_entrapolis_load_more_list', 'entrapolis_ajax_load_more_list');
