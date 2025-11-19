<?php
if (!defined('ABSPATH')) {
    exit;
}

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