<?php
/**
 * Shortcode del detalle de evento
 */

if (!defined('ABSPATH')) {
    exit;
}

class Entrapolis_Event_Detail_Shortcode
{

    private $api;

    public function __construct()
    {
        $this->api = Entrapolis_API::get_instance();
        add_shortcode('entrapolis_event', array($this, 'render'));
    }

    public function render($atts)
    {
        $atts = shortcode_atts(array(
            'id' => '',
        ), $atts, 'entrapolis_event');

        $event_id = !empty($_GET['entrapolis_event']) ? intval($_GET['entrapolis_event']) : intval($atts['id']);

        if (!$event_id) {
            return '<div class="entrapolis-error">ID d\'esdeveniment no especificat.</div>';
        }

        $result = $this->api->get_event($event_id);

        if (is_wp_error($result)) {
            return '<div class="entrapolis-error">Error carregant l\'esdeveniment.</div>';
        }

        $event = $result['event'];
        $title = esc_html($event['title']);
        $date = esc_html($event['date_readable']);
        $location = isset($event['location']) ? esc_html($event['location']) : '';
        $image = entrapolis_get_cdn_image($event['image'] ?? '');
        $url_widget = !empty($event['url_widget']) ? $event['url_widget'] : '';
        $url = !empty($event['url']) ? $event['url'] : '';

        ob_start();
        ?>
        <div class="entrapolis-event-detail">
            <div class="entrapolis-event-detail-container">
                <?php if ($image): ?>
                    <div class="entrapolis-event-image">
                        <a href="<?php echo esc_url($image); ?>" target="_blank" rel="noopener">
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>">
                        </a>
                    </div>
                <?php endif; ?>

                <div class="entrapolis-event-info">
                    <h2 class="entrapolis-event-title"><?php echo $title; ?></h2>
                    <?php if ($date): ?>
                        <div class="entrapolis-event-date">📅 <?php echo $date; ?></div>
                    <?php endif; ?>
                    <?php if ($location): ?>
                        <div class="entrapolis-event-location">📍 <?php echo $location; ?></div>
                    <?php endif; ?>

                    <div class="entrapolis-event-actions">
                        <?php if ($url_widget): ?>
                            <a class="entrapolis-event-buy-link" href="<?php echo esc_url($url_widget); ?>" target="_blank"
                                rel="noopener">
                                Comprar entrades
                            </a>
                        <?php elseif ($url): ?>
                            <a class="entrapolis-event-buy-link" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
                                Més informació
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Entrapolis_Event_Detail_Shortcode();
