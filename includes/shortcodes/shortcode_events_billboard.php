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
        'lang' => '',
    ), $atts, 'entrapolis_billboard');

    $event_id = intval($atts['event_id']);
    $detail_page_slug = sanitize_text_field($atts['detail_page']);
    $lang = sanitize_text_field($atts['lang']);
    $lang_code = in_array($lang, array('ca', 'es', 'en')) ? $lang : 'ca';

    // Traducciones
    $texts = array(
        'ca' => array(
            'more_info' => 'Més informació',
        ),
        'es' => array(
            'more_info' => 'Más información',
        ),
        'en' => array(
            'more_info' => 'More information',
        ),
    );
    $t = $texts[$lang_code];

    if (!$event_id) {
        $error_msgs = array(
            'ca' => 'Es requereix un ID d\'esdeveniment.',
            'es' => 'Se requiere un ID de evento.',
            'en' => 'Event ID is required.',
        );
        return '<div class="entrapolis-error">' . esc_html($error_msgs[$lang_code]) . '</div>';
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

    // Formatear fecha según idioma
    $formatted_date = '';
    if ($date_readable) {
        preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_readable, $matches);
        $months = array(
            'ca' => array(
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
            ),
            'es' => array(
                1 => 'enero',
                2 => 'febrero',
                3 => 'marzo',
                4 => 'abril',
                5 => 'mayo',
                6 => 'junio',
                7 => 'julio',
                8 => 'agosto',
                9 => 'septiembre',
                10 => 'octubre',
                11 => 'noviembre',
                12 => 'diciembre'
            ),
            'en' => array(
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December'
            )
        );
        $month_names = $months[$lang_code];
        if ($matches) {
            $year = $matches[1];
            $month_num = intval($matches[2]);
            $day = intval($matches[3]);
            $hour = $matches[4];
            $minute = $matches[5];
            $month_name = isset($month_names[$month_num]) ? $month_names[$month_num] : $month_num;
            if ($lang_code === 'ca') {
                $formatted_date = "$day de $month_name de $year a les $hour:$minute";
            } elseif ($lang_code === 'es') {
                $formatted_date = "$day de $month_name de $year a las $hour:$minute";
            } elseif ($lang_code === 'en') {
                $formatted_date = "$month_name $day, $year at $hour:$minute";
            } else {
                $formatted_date = "$day de $month_name de $year a les $hour:$minute";
            }
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
                    <?php echo esc_html($t['more_info']); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('entrapolis_billboard', 'entrapolis_shortcode_billboard');
