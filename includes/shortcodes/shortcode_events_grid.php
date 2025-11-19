<?php
if (!defined('ABSPATH')) {
    exit;
}
/*
 * Shortcode per llistar esdeveniments
 * Usage: [entrapolis_events org="ORG_ID" detail_page="detail-page-slug" limit="4"]
 * En format grid
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