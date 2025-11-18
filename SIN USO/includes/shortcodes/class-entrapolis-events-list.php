<?php
/**
 * Shortcode del listado de eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Entrapolis_Events_List_Shortcode
{

    private $api;

    public function __construct()
    {
        $this->api = Entrapolis_API::get_instance();
        add_shortcode('entrapolis_events', array($this, 'render'));
    }

    public function render($atts)
    {
        $atts = shortcode_atts(array(
            'org' => ENTRAPOLIS_ORG_ID,
            'detail_page' => '',
        ), $atts, 'entrapolis_events');

        $org_id = intval($atts['org']);
        $detail_page_slug = sanitize_text_field($atts['detail_page']);

        $events = $this->api->get_events($org_id);

        if (is_wp_error($events)) {
            return '<div class="entrapolis-error">Error carregant esdeveniments: ' . esc_html($events->get_error_message()) . '</div>';
        }

        if (empty($events)) {
            return '<div class="entrapolis-empty">No hi ha esdeveniments disponibles.</div>';
        }

        ob_start();
        ?>
        <div class="entrapolis-events-list">
            <?php foreach ($events as $event):
                $id = intval($event['id']);
                $title = esc_html($event['title']);
                $date = esc_html($event['date_readable']);
                $image = entrapolis_get_cdn_image($event['image'] ?? '');
                $url = !empty($event['url']) ? $event['url'] : '';
                $url_widget = !empty($event['url_widget']) ? $event['url_widget'] : '';

                $detail_url = entrapolis_get_detail_url($id, $detail_page_slug);
                ?>
                <div class="entrapolis-event-item">
                    <?php if ($image): ?>
                        <a href="<?php echo esc_url($image); ?>" target="_blank" rel="noopener">
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>"
                                class="entrapolis-event-image">
                        </a>
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
}

new Entrapolis_Events_List_Shortcode();
