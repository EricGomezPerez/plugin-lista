<?php
if (!defined('ABSPATH')) {
    exit;
}

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