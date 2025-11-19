<?php
/**
 * Shortcodes for Entrapolis plugin
 *
 * @package Entrapolis
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
 * Shortcode per llistar esdeveniments
 * Usage: [entrapolis_events org="ORG_ID" detail_page="detail-page-slug" limit="4"]
 */

function entrapolis_shortcode_events($atts)
{
    $atts = shortcode_atts(array(
        'org' => ENTRAPOLIS_ORG_ID,
        'detail_page' => '',
        'limit' => 4,
    ), $atts, 'entrapolis_events');

    $org_id = intval($atts['org']);
    $detail_page_slug = sanitize_text_field($atts['detail_page']);
    $limit = intval($atts['limit']);

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

    // Agrupar eventos por título e imagen
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

    // Limitar eventos si se especifica
    if ($limit > 0) {
        $grouped_events = array_slice($grouped_events, 0, $limit);
    }

    // Mapeo de categorías a colores
    $category_colors = array(
        'Teatre' => '#ca1818',
        'Ballet' => '#bf05a4',
        'Música' => '#1a8cff',
        'Teatre Familiar' => '#ea8b00',
        'Dansa' => '#a1248e',
        'Generic' => '#707070',
    );

    $months_catalan = array(
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

    ob_start();
    ?>
    <div class="entrapolis-events-wrapper">
        <div class="entrapolis-events-container">
            <div class="entrapolis-events-grid">
                <?php foreach ($grouped_events as $event):
                    $id = intval($event['ids'][0]);
                    $title = esc_html($event['title']);
                    $category = esc_html($event['category']);
                    $description = esc_html($event['description']);

                    // Limitar descripción a 150 caracteres
                    if (strlen($description) > 150) {
                        $description = substr($description, 0, 150) . '…';
                    }

                    $image = !empty($event['image']) ? str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $event['image']) : '';

                    // Obtener color de categoría
                    $category_color = isset($category_colors[$category]) ? $category_colors[$category] : '#707070';

                    // Formatear primera fecha
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
                    if ($detail_page_slug) {
                        $detail_url = home_url('/' . $detail_page_slug . '/?entrapolis_event=' . $id);
                    }
                    ?>
                    <div class="entrapolis-event-card">
                        <a class="entrapolis-event-link" href="<?php echo esc_url($detail_url); ?>">
                            <figure class="entrapolis-event-figure"
                                style="background-image: url('<?php echo esc_url($image); ?>');">
                                <figcaption class="entrapolis-event-caption"
                                    style="background-color:<?php echo $category_color; ?>;">
                                    <h3 class="entrapolis-event-date"><?php echo $formatted_date; ?></h3>
                                    <h2 class="entrapolis-event-title"><?php echo $title; ?></h2>
                                    <?php if ($description): ?>
                                        <p class="entrapolis-event-excerpt"><?php echo $description; ?></p>
                                    <?php endif; ?>
                                </figcaption>
                            </figure>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
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
    $title = $event['title'];
    $image = $event['image'];

    // Obtener todos los eventos con el mismo título e imagen
    $cache_key = 'entrapolis_events_' . ENTRAPOLIS_ORG_ID;
    $all_events = get_transient($cache_key);

    if ($all_events === false) {
        $all_result = entrapolis_api_post('/api/events/', array(
            'application_id' => ENTRAPOLIS_ORG_ID,
        ));
        if (!is_wp_error($all_result) && !empty($all_result['events'])) {
            $all_events = $all_result['events'];
            set_transient($cache_key, $all_events, 5 * 60);
        } else {
            $all_events = array();
        }
    }

    // Filtrar eventos con mismo título e imagen
    $related_events = array();
    foreach ($all_events as $evt) {
        if ($evt['title'] === $title && $evt['image'] === $image) {
            $related_events[] = $evt;
        }
    }

    $title = esc_html($title);
    $location = isset($event['location']) ? esc_html($event['location']) : '';
    $image = !empty($image) ? str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $image) : '';
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
                <?php if (!empty($related_events)): ?>
                    <div class="entrapolis-event-dates">
                        <strong>Dates disponibles:</strong>
                        <ul class="entrapolis-dates-list">
                            <?php
                            $months_catalan = array(
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

                            foreach ($related_events as $rel_evt):
                                preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $rel_evt['date_readable'], $matches);

                                if ($matches) {
                                    $year = $matches[1];
                                    $month_num = intval($matches[2]);
                                    $day = intval($matches[3]);
                                    $hour = $matches[4];
                                    $minute = $matches[5];

                                    $month_name = isset($months_catalan[$month_num]) ? $months_catalan[$month_num] : $month_num;
                                    $formatted_date = "$day de $month_name de $year a les $hour:$minute";
                                } else {
                                    $formatted_date = $rel_evt['date_readable'];
                                }
                                ?>
                                <li><?php echo esc_html($formatted_date); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
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
                        if ($day['has_events'])
                            $classes[] = 'has-events';
                        if ($day['is_today'])
                            $classes[] = 'is-today';
                        ?>
                        <div class="<?php echo implode(' ', $classes); ?>" data-date="<?php echo esc_attr($day['date']); ?>">
                            <div class="entrapolis-calendar-day-name"><?php echo esc_html($day['day_name']); ?></div>
                            <div class="entrapolis-calendar-day-number"><?php echo esc_html($day['day_num']); ?></div>

                            <?php if ($day['has_events']): ?>
                                <div class="entrapolis-calendar-tooltip">
                                    <?php
                                    $months_catalan = array(
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

                                    foreach ($day['events'] as $event):
                                        $event_detail_url = '';
                                        if (!empty($atts['detail_page'])) {
                                            $event_detail_url = home_url('/' . sanitize_text_field($atts['detail_page']) . '/?entrapolis_event=' . intval($event['id']));
                                        }

                                        preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $event['date_readable'], $matches);
                                        if ($matches) {
                                            $hour = $matches[4];
                                            $minute = $matches[5];
                                            $formatted_date = "$hour:$minute";
                                        } else {
                                            $formatted_date = $event['date_readable'];
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
                                                <span><?php echo esc_html($formatted_date); ?></span>
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

