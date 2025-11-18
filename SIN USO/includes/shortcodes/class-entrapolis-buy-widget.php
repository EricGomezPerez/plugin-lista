<?php
/**
 * Shortcode del widget de compra
 */

if (!defined('ABSPATH')) {
    exit;
}

class Entrapolis_Buy_Widget_Shortcode
{

    public function __construct()
    {
        add_shortcode('entrapolis_buy_widget', array($this, 'render'));
    }

    public function render($atts)
    {
        $atts = shortcode_atts(array(
            'url' => '',
        ), $atts, 'entrapolis_buy_widget');

        $url = !empty($_GET['url']) ? esc_url_raw($_GET['url']) : esc_url($atts['url']);

        if (empty($url)) {
            return '<div class="entrapolis-error">URL del widget no especificada.</div>';
        }

        ob_start();
        ?>
        <div class="entrapolis-buy-widget">
            <iframe src="<?php echo esc_url($url); ?>" title="Comprar entrades"></iframe>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Entrapolis_Buy_Widget_Shortcode();
