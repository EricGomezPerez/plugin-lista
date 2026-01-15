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
        'lang' => '',
        'limit' => 4,
    ), $atts, 'entrapolis_events');

    $org_id = intval($atts['org']);
    $detail_page_slug = sanitize_text_field($atts['detail_page']);
    $lang = sanitize_text_field($atts['lang']);
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

    // Guardar total antes de limitar
    $total_events = count($grouped_events);
    $has_more = false;

    // Limitar eventos si se especifica
    if ($limit > 0 && $total_events > $limit) {
        $grouped_events = array_slice($grouped_events, 0, $limit);
        $has_more = true;
    }

    // Mapeo de categorías a colores
    $generic_color = get_option('entrapolis_generic_color', '#e31e24');
    $generic_text_color = get_option('entrapolis_generic_text_color', '#ffffff');
    $category_colors = array(
        'Generic' => $generic_color,
    );


    // Meses en diferentes idiomas
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
        ),
        'fr' => array(
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'août',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre'
        )
    );

    $unique_id = 'entrapolis-grid-' . uniqid();

    ob_start();
    ?>
    <div class="entrapolis-events-wrapper" id="<?php echo $unique_id; ?>" data-org="<?php echo $org_id; ?>"
        data-detail-page="<?php echo esc_attr($detail_page_slug); ?>" data-limit="<?php echo $limit; ?>"
        data-offset="<?php echo $limit; ?>">
        <div class="entrapolis-events-container">
            <div class="entrapolis-events-grid entrapolis-events-grid-content">
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
                    $category_color = isset($category_colors[$category]) ? $category_colors[$category] : '#e31e24';


                    // Formatear primera fecha según idioma
                    $first_date = $event['dates'][0];
                    preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $first_date, $matches);

                    $lang_code = in_array($lang, array('ca', 'es', 'en', 'fr')) ? $lang : 'ca';
                    $month_names = $months[$lang_code];

                    if ($matches) {
                        $year = $matches[1];
                        $month_num = intval($matches[2]);
                        $day = intval($matches[3]);
                        $hour = $matches[4];
                        $minute = $matches[5];
                        $month_name = isset($month_names[$month_num]) ? $month_names[$month_num] : $month_num;

                        if ($lang_code === 'ca') {
                            $formatted_date = "$day de $month_name de $year $hour:$minute";
                        } elseif ($lang_code === 'es') {
                            $formatted_date = "$day de $month_name de $year $hour:$minute";
                        } elseif ($lang_code === 'en') {
                            $formatted_date = "$month_name $day, $year $hour:$minute";
                        } elseif ($lang_code === 'fr') {
                            $formatted_date = "$day $month_name $year $hour:$minute";
                        } else {
                            $formatted_date = "$day de $month_name de $year $hour:$minute";
                        }
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
                                    style="background-color:<?php echo $category_color; ?>; color:<?php echo $generic_text_color; ?> !important;">
                                    <h3 class="entrapolis-event-date"
                                        style="color:<?php echo $generic_text_color; ?> !important;">
                                        <?php echo $formatted_date; ?>
                                    </h3>
                                    <h2 class="entrapolis-event-title"
                                        style="color:<?php echo $generic_text_color; ?> !important;"><?php echo $title; ?></h2>
                                    <?php if ($description): ?>
                                        <p class="entrapolis-event-excerpt"
                                            style="color:<?php echo $generic_text_color; ?> !important;"><?php echo $description; ?>
                                        </p>
                                    <?php endif; ?>
                                </figcaption>
                            </figure>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($has_more): ?>
                <div class="entrapolis-load-more-wrapper">
                    <?php
                    // Traducción del texto del botón según idioma
                    $load_more_texts = array(
                        'ca' => 'Carregar més esdeveniments',
                        'es' => 'Cargar más eventos',
                        'en' => 'Load more events',
                        'fr' => 'Charger plus d\'événements'
                    );
                    $loading_texts = array(
                        'ca' => 'Carregant...',
                        'es' => 'Cargando...',
                        'en' => 'Loading...',
                        'fr' => 'Chargement...'
                    );
                    $load_more_text = isset($load_more_texts[$lang_code]) ? $load_more_texts[$lang_code] : $load_more_texts['ca'];
                    $loading_text = isset($loading_texts[$lang_code]) ? $loading_texts[$lang_code] : $loading_texts['ca'];
                    ?>
                    <button class="entrapolis-load-more-btn" data-target="<?php echo $unique_id; ?>">
                        <?php echo esc_html($load_more_text); ?>
                    </button>
                    <span class="entrapolis-loading" style="display:none;"><?php echo esc_html($loading_text); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        (function () {
            const container = document.getElementById('<?php echo $unique_id; ?>');
            if (!container) return;

            const loadMoreBtn = container.querySelector('.entrapolis-load-more-btn');
            const loadingSpan = container.querySelector('.entrapolis-loading');
            const grid = container.querySelector('.entrapolis-events-grid-content');

            if (!loadMoreBtn) return;

            loadMoreBtn.addEventListener('click', function () {
                const orgId = container.dataset.org;
                const detailPage = container.dataset.detailPage;
                const limit = parseInt(container.dataset.limit);
                const offset = parseInt(container.dataset.offset);

                loadMoreBtn.style.display = 'none';
                loadingSpan.style.display = 'inline';

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'entrapolis_load_more_grid',
                        org_id: orgId,
                        detail_page: detailPage,
                        limit: limit,
                        offset: offset
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Response:', data);
                        if (data.success && data.data && data.data.html) {
                            grid.insertAdjacentHTML('beforeend', data.data.html);
                            container.dataset.offset = offset + limit;

                            if (!data.data.has_more) {
                                loadMoreBtn.parentElement.remove();
                            } else {
                                loadMoreBtn.style.display = 'inline-block';
                                loadingSpan.style.display = 'none';
                            }
                        } else {
                            console.error('Invalid response:', data);
                            loadMoreBtn.parentElement.remove();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        loadMoreBtn.style.display = 'inline-block';
                        loadingSpan.style.display = 'none';
                    });
            });
        })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('entrapolis_events', 'entrapolis_shortcode_events');