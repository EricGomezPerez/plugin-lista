<?php
/**
 * Shortcode per mostrar un esdeveniment en format cartelera (pantalla completa)
 * Usage: [entrapolis_billboard event_id="12345" detail_page="evento"]
 *
 * @package Entrapolis
 */

if (!defined('ABSPATH')) {
    exit;
}

function entrapolis_shortcode_billboard($atts)
{
    $atts = shortcode_atts(array(
        'event_id' => '',
        'detail_page' => '',
    ), $atts, 'entrapolis_billboard');

    $event_id = intval($atts['event_id']);
    $detail_page_slug = sanitize_text_field($atts['detail_page']);

    if (!$event_id) {
        return '<div class="entrapolis-error">Es requereix un ID d\'esdeveniment.</div>';
    }

    // Obtener evento desde la API
    $cache_key = 'entrapolis_event_' . $event_id;
    $event = get_transient($cache_key);

    if ($event === false) {
        $result = entrapolis_api_post('/api/event/', array(
            'events_master_id' => $event_id,
        ));

        if (is_wp_error($result)) {
            return '<div class="entrapolis-error">Error carregant esdeveniment: ' . esc_html($result->get_error_message()) . '</div>';
        }

        if (empty($result['event'])) {
            return '<div class="entrapolis-empty">Esdeveniment no trobat.</div>';
        }

        $event = $result['event'];
        set_transient($cache_key, $event, 5 * 60);
    }

    $title = esc_html($event['title']);
    $image = !empty($event['image']) ? str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $event['image']) : '';
    $date_readable = isset($event['date_readable']) ? $event['date_readable'] : '';

    // Formatear fecha
    $formatted_date = '';
    if ($date_readable) {
        preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_readable, $matches);
        if ($matches) {
            $months_catalan = entrapolis_get_catalan_months();
            $year = $matches[1];
            $month_num = intval($matches[2]);
            $day = intval($matches[3]);
            $hour = $matches[4];
            $minute = $matches[5];
            $month_name = isset($months_catalan[$month_num]) ? $months_catalan[$month_num] : $month_num;
            $formatted_date = "$day de $month_name de $year a les $hour:$minute";
        } else {
            $formatted_date = $date_readable;
        }
    }

    // URL de detalle
    $detail_url = '';
    if ($detail_page_slug) {
        $detail_url = home_url('/' . $detail_page_slug . '/?entrapolis_event=' . $event_id);
    }

    ob_start();
    ?>
    <div class="entrapolis-billboard">
        <?php if ($image): ?>
            <div class="entrapolis-billboard-image" style="background-image: url('<?php echo esc_url($image); ?>');">
                <div class="entrapolis-billboard-overlay"></div>
            </div>
        <?php endif; ?>

        <div class="entrapolis-billboard-content">
            <?php if ($formatted_date): ?>
                <div class="entrapolis-billboard-date"><?php echo esc_html($formatted_date); ?></div>
            <?php endif; ?>

            <h1 class="entrapolis-billboard-title"><?php echo $title; ?></h1>

            <?php if ($detail_url): ?>
                <a href="<?php echo esc_url($detail_url); ?>" class="entrapolis-billboard-btn">
                    Més informació
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('entrapolis_billboard', 'entrapolis_shortcode_billboard');
