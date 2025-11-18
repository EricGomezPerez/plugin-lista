<?php
/**
 * Plugin Name: Entrapolis 
 * Description: Primer Plugin
 * Version: 0.1.0
 * Author: Perception
 */

if (!defined('ABSPATH')) {
    exit;
}

// Configurar locale en español
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'spanish');

define('ENTRAPOLIS_API_BASE', 'http://www.entrapolis.com');
define('ENTRAPOLIS_ORG_ID', 2910);
define('ENTRAPOLIS_API_TOKEN', '2b8b8b22b0842b1f40380a35a115839a8531f7e18451855321112b4a117a85a1');

/**
 * Enqueue styles
 */
function entrapolis_enqueue_styles()
{
    wp_enqueue_style(
        'entrapolis-styles',
        plugins_url('entrapolis-styles.css', __FILE__),
        array(),
        '0.1.0'
    );
}
add_action('wp_enqueue_scripts', 'entrapolis_enqueue_styles');

/**
 * Helper per cridar a l'API d'Entrapolis
 */
function entrapolis_api_post($endpoint, $body = array())
{
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

/*
 * Shortcode per llistar esdeveniments
 * Usage: [entrapolis_events org="ORG_ID" detail_page="detail-page-slug"]
 */

function entrapolis_shortcode_events($atts)
{
    $atts = shortcode_atts(array(
        'org' => ENTRAPOLIS_ORG_ID,
        'detail_page' => '',
    ), $atts, 'entrapolis_events');

    $org_id = intval($atts['org']);
    $detail_page_slug = sanitize_text_field($atts['detail_page']);

    $cache_key = 'entrapolis_events_' . $org_id;
    $events = get_transient($cache_key);

    if ($events === false) {
        $result = entrapolis_api_post('/api/events/', array(
            'application_id' => $org_id,
        ));

        if (is_wp_error($result)) {
            return '<div class="entrapolis-error">Error carregant esdeveniments: ' . esc_html($result->get_error_message()) . '</div>';
        }

        if (empty($result['events']) || !is_array($result['events'])) {
            return '<div class="entrapolis-empty">No hi ha esdeveniments disponibles.</div>';
        }

        $events = $result['events'];
        set_transient($cache_key, $events, 5 * 60);
    }

    ob_start();
    ?>
    <div class="entrapolis-events-list">
        <?php foreach ($events as $event):
            $id = intval($event['id']);
            $title = esc_html($event['title']);
            $date = esc_html($event['date_readable']);
            $image = !empty($event['image']) ? str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $event['image']) : '';
            $url = !empty($event['url']) ? $event['url'] : '';
            $url_widget = !empty($event['url_widget']) ? $event['url_widget'] : '';

            $detail_url = '';
            if ($detail_page_slug) {
                $detail_url = home_url('/' . $detail_page_slug . '/?entrapolis_event=' . $id);
            }
            ?>
            <div class="entrapolis-event-item">
                <?php if ($image): ?>
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo $title; ?>" class="entrapolis-event-image">

                <?php endif; ?>

                <h3 class="entrapolis-event-title"><?php echo $title; ?></h3>
                <p class="entrapolis-event-date"><?php echo $date; ?></p>

                <div class="entrapolis-event-actions">
                    <?php if ($detail_url): ?>
                        <a href="<?php echo esc_url($detail_url); ?>" class="entrapolis-btn">Detall</a>
                    <?php endif; ?>
                    <?php if ($url): ?>
                        <a href="<?php echo esc_url($url); ?>" class="entrapolis-btn" target="_blank">Més informació</a>
                    <?php endif; ?>
                    <?php if ($url_widget): ?>
                        <a href="<?php echo esc_url($url_widget); ?>" class="entrapolis-btn entrapolis-btn-primary"
                            target="_blank">Comprar entrades</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('entrapolis_events', 'entrapolis_shortcode_events');

/*
 * Shortcode per mostrar detall d'un esdeveniment
 * Usage: [entrapolis_event id="EVENT_ID"]
 */
function entrapolis_shortcode_event_detail($atts)
{
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'entrapolis_event');

    $event_id = intval($atts['id']);

    if (!$event_id && isset($_GET['entrapolis_event'])) {
        $event_id = intval($_GET['entrapolis_event']);
    }

    if (!$event_id) {
        return '<div class="entrapolis-error">Cap esdeveniment especificat.</div>';
    }

    $result = entrapolis_api_post('/api/event/', array(
        'events_master_id' => $event_id,
    ));

    if (is_wp_error($result)) {
        return '<div class="entrapolis-error">Error carregant l\'esdeveniment: ' . esc_html($result->get_error_message()) . '</div>';
    }

    if (empty($result['event'])) {
        return '<div class="entrapolis-error">Esdeveniment no trobat.</div>';
    }

    $event = $result['event'];
    $title = esc_html($event['title']);
    $date = esc_html($event['date_readable']);
    $location = isset($event['location']) ? esc_html($event['location']) : '';
    $image = !empty($event['image']) ? str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $event['image']) : '';
    $url_widget = !empty($event['url_widget']) ? $event['url_widget'] : '';
    $url = !empty($event['url']) ? $event['url'] : '';

    ob_start();
    ?>
    <div class="entrapolis-event-detail">
        <div class="entrapolis-event-detail-container">
            <?php if ($image): ?>
                <div class="entrapolis-event-image">
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>">
                </div>
            <?php endif; ?>

            <div class="entrapolis-event-info">
                <h2 class="entrapolis-event-title"><?php echo $title; ?></h2>
                <?php if ($date): ?>
                    <div class="entrapolis-event-date">📅 <?php echo $date; ?></div>
                <?php endif; ?>
                <!-- <?php if ($location): ?>
                    <div class="entrapolis-event-location">📍 <?php echo $location; ?></div>
                <?php endif; ?> -->

                <div class="entrapolis-event-actions">
                    <?php if ($url_widget): ?>
                        <a class="entrapolis-event-buy-link" href="<?php echo esc_url($url_widget); ?>" target="_blank"
                            rel="noopener">
                            <?php esc_html_e('Comprar entrades', 'entrapolis'); ?>
                        </a>
                    <?php elseif ($url): ?>
                        <a class="entrapolis-event-buy-link" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e('Comprar entrades', 'entrapolis'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('entrapolis_event', 'entrapolis_shortcode_event_detail');

/*
 * Shortcode per mostrar el widget de compra d'un esdeveniment
 * Usage: [entrapolis_buy id="EVENT_ID"]
 */
function entrapolis_shortcode_buy($atts)
{
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'entrapolis_buy');

    $event_id = intval($atts['id']);

    if (!$event_id && isset($_GET['entrapolis_event'])) {
        $event_id = intval($_GET['entrapolis_event']);
    }

    if (!$event_id) {
        return '<div class="entrapolis-error">Cap esdeveniment especificat per a la compra.</div>';
    }

    $result = entrapolis_api_post('/api/event/', array(
        'events_master_id' => $event_id,
    ));

    if (is_wp_error($result) || empty($result['event'])) {
        return '<div class="entrapolis-error">Error carregant el widget de compra.</div>';
    }

    $url_widget = !empty($result['event']['url_widget']) ? esc_url($result['event']['url_widget']) : '';

    if (!$url_widget) {
        return '<div class="entrapolis-error">Aquest esdeveniment no té widget de compra.</div>';
    }

    ob_start();
    ?>
    <div class="entrapolis-buy-widget">
        <iframe src="<?php echo $url_widget; ?>" style="width:100%;min-height:600px;border:0;" scrolling="auto"></iframe>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('entrapolis_buy', 'entrapolis_shortcode_buy');

/*
 * Shortcode per mostrar el calendari d'esdeveniments
 * Usage: [entrapolis_calendar org="ORG_ID" months="3"]
 */
function entrapolis_shortcode_calendar($atts)
{
    $atts = shortcode_atts(array(
        'org' => ENTRAPOLIS_ORG_ID,
        'months' => 3,
        'detail_page' => '',
    ), $atts, 'entrapolis_calendar');

    $org_id = intval($atts['org']);
    $months_ahead = intval($atts['months']);

    $cache_key = 'entrapolis_events_' . $org_id;
    $events = get_transient($cache_key);

    if ($events === false) {
        $result = entrapolis_api_post('/api/events/', array(
            'application_id' => $org_id,
        ));

        if (is_wp_error($result) || empty($result['events'])) {
            return '';
        }

        $events = $result['events'];
        set_transient($cache_key, $events, 5 * 60);
    }

    // Agrupar eventos por fecha
    $events_by_date = array();
    foreach ($events as $event) {
        $date_str = $event['date_readable'];
        preg_match('/(\d{4}-\d{2}-\d{2})/', $date_str, $matches);
        if (!empty($matches[1])) {
            $date = $matches[1];
            if (!isset($events_by_date[$date])) {
                $events_by_date[$date] = array();
            }
            $events_by_date[$date][] = $event;
        }
    }

    // Generar meses
    $months_data = array();
    $current_date = new DateTime();

    for ($i = 0; $i < $months_ahead; $i++) {
        $month_date = clone $current_date;
        $month_date->modify("+{$i} months");

        $month_key = $month_date->format('Y-m');
        $month_name = strftime('%B %Y', $month_date->getTimestamp());

        // Días del mes
        $first_day = new DateTime($month_date->format('Y-m-01'));
        $last_day = new DateTime($month_date->format('Y-m-t'));

        $days = array();
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($first_day, $interval, $last_day->modify('+1 day'));

        $days_catalan = array('Dg', 'Dl', 'Dt', 'Dc', 'Dj', 'Dv', 'Ds');
        $today = date('Y-m-d');

        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            $day_of_week = $date->format('w'); // 0 (domingo) a 6 (sábado)
            $days[] = array(
                'date' => $date_str,
                'day_num' => $date->format('j'),
                'day_name' => $days_catalan[$day_of_week],
                'has_events' => isset($events_by_date[$date_str]),
                'events' => isset($events_by_date[$date_str]) ? $events_by_date[$date_str] : array(),
                'is_today' => $date_str === $today,
            );
        }

        $months_data[] = array(
            'key' => $month_key,
            'name' => ucfirst($month_name),
            'days' => $days,
        );
    }

    ob_start();
    ?>
    <div class="entrapolis-calendar" data-current-month="0">
        <div class="entrapolis-calendar-container">
            <?php foreach ($months_data as $index => $month): ?>
                <div class="entrapolis-calendar-month" data-month-index="<?php echo $index; ?>"
                    style="display: <?php echo $index === 0 ? 'flex' : 'none'; ?>;">
                    <div class="entrapolis-calendar-header">
                        <button class="entrapolis-calendar-prev" aria-label="Mes anterior">‹</button>
                        <div class="entrapolis-calendar-month-name"><?php echo esc_html($month['name']); ?></div>
                        <button class="entrapolis-calendar-next" aria-label="Mes següent">›</button>
                    </div>
                    <?php foreach ($month['days'] as $day): 
                        $classes = array('entrapolis-calendar-day');
                        if ($day['has_events']) $classes[] = 'has-events';
                        if ($day['is_today']) $classes[] = 'is-today';
                    ?>
                        <div class="<?php echo implode(' ', $classes); ?>"
                            data-date="<?php echo esc_attr($day['date']); ?>">
                            <div class="entrapolis-calendar-day-name"><?php echo esc_html($day['day_name']); ?></div>
                            <div class="entrapolis-calendar-day-number"><?php echo esc_html($day['day_num']); ?></div>

                            <?php if ($day['has_events']): ?>
                                <div class="entrapolis-calendar-tooltip">
                                    <?php foreach ($day['events'] as $event):
                                        $event_detail_url = '';
                                        if (!empty($atts['detail_page'])) {
                                            $event_detail_url = home_url('/' . sanitize_text_field($atts['detail_page']) . '/?entrapolis_event=' . intval($event['id']));
                                        }
                                        ?>
                                        <a href="<?php echo esc_url($event_detail_url); ?>" class="entrapolis-calendar-tooltip-event">
                                            <?php if (!empty($event['image'])):
                                                $image = str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $event['image']);
                                                ?>
                                                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($event['title']); ?>">
                                            <?php endif; ?>
                                            <div class="entrapolis-calendar-tooltip-content">
                                                <strong><?php echo esc_html($event['title']); ?></strong>
                                                <span><?php echo esc_html($event['date_readable']); ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        (function () {
            const calendar = document.querySelector('.entrapolis-calendar');
            if (!calendar) return;

            const prevBtn = calendar.querySelector('.entrapolis-calendar-prev');
            const nextBtn = calendar.querySelector('.entrapolis-calendar-next');
            const monthName = calendar.querySelector('.entrapolis-calendar-month-name');
            const months = calendar.querySelectorAll('.entrapolis-calendar-month');

            const monthNames = <?php echo json_encode(array_column($months_data, 'name')); ?>;
            let currentMonth = 0;

            function updateCalendar() {
                months.forEach((month, index) => {
                    month.style.display = index === currentMonth ? 'flex' : 'none';
                });

                const prevBtns = calendar.querySelectorAll('.entrapolis-calendar-prev');
                const nextBtns = calendar.querySelectorAll('.entrapolis-calendar-next');

                prevBtns.forEach(btn => btn.disabled = currentMonth === 0);
                nextBtns.forEach(btn => btn.disabled = currentMonth === months.length - 1);
            }

            calendar.addEventListener('click', (e) => {
                if (e.target.classList.contains('entrapolis-calendar-prev')) {
                    if (currentMonth > 0) {
                        currentMonth--;
                        updateCalendar();
                    }
                } else if (e.target.classList.contains('entrapolis-calendar-next')) {
                    if (currentMonth < months.length - 1) {
                        currentMonth++;
                        updateCalendar();
                    }
                }
            });

            updateCalendar();
        })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('entrapolis_calendar', 'entrapolis_shortcode_calendar');
