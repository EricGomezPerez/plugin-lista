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
        'lang' => '',
    ), $atts, 'entrapolis_event');

    $event_id = intval($atts['id']);
    $lang = sanitize_text_field($atts['lang']);

    if (!$event_id && isset($_GET['entrapolis_event'])) {
        $event_id = intval($_GET['entrapolis_event']);
    }

    $lang_code = in_array($lang, array('ca', 'es', 'en', 'fr')) ? $lang : 'ca';

    // Traducciones
    $texts = array(
        'ca' => array(
            'no_event' => 'Cap esdeveniment especificat.',
            'error_loading' => 'Error carregant l\'esdeveniment',
            'not_found' => 'Esdeveniment no trobat.',
            'category' => 'Categoria:',
            'location' => 'Ubicació:',
            'dates_title' => 'Dates disponibles',
            'buy' => 'Comprar entrades',
            'default_location' => 'Teatre Principal',
        ),
        'es' => array(
            'no_event' => 'Ningún evento especificado.',
            'error_loading' => 'Error cargando el evento',
            'not_found' => 'Evento no encontrado.',
            'category' => 'Categoría:',
            'location' => 'Ubicación:',
            'dates_title' => 'Fechas disponibles',
            'buy' => 'Comprar entradas',
            'default_location' => 'Teatro Principal',
        ),
        'en' => array(
            'no_event' => 'No event specified.',
            'error_loading' => 'Error loading event',
            'not_found' => 'Event not found.',
            'category' => 'Category:',
            'location' => 'Location:',
            'dates_title' => 'Available dates',
            'buy' => 'Buy tickets',
            'default_location' => 'Main Theatre',
        ),
        'fr' => array(
            'no_event' => 'Aucun événement spécifié.',
            'error_loading' => 'Erreur de chargement de l\'événement',
            'not_found' => 'Événement non trouvé.',
            'category' => 'Catégorie:',
            'location' => 'Emplacement:',
            'dates_title' => 'Dates disponibles',
            'buy' => 'Acheter des billets',
            'default_location' => 'Théâtre Principal',
        ),
    );
    $t = $texts[$lang_code];

    if (!$event_id) {
        return '<div class="entrapolis-error">' . esc_html($t['no_event']) . '</div>';
    }

    $result = entrapolis_api_post('/api/event/', array(
        'events_master_id' => $event_id,
    ));

    if (is_wp_error($result)) {
        return '<div class="entrapolis-error">' . esc_html($t['error_loading']) . ': ' . esc_html($result->get_error_message()) . '</div>';
    }

    if (empty($result['event'])) {
        return '<div class="entrapolis-error">' . esc_html($t['not_found']) . '</div>';
    }

    $event = $result['event'];
    $title = $event['title'];
    $image = $event['image'];

    // Obtener detalles del evento con descripción multiidioma
    $details_result = entrapolis_api_post('/api/event-details/', array(
        'events_master_id' => $event_id,
        'idioma_slug' => $lang_code,
    ));

    $description = '';
    $location = $t['default_location'];

    if (!is_wp_error($details_result) && !empty($details_result['event'])) {
        $event_details = $details_result['event'];

        // Determinar qué descripción usar según description_multi_idioma
        if (!empty($event_details['description_multi_idioma']) && $event_details['description_multi_idioma'] == 1) {
            $description = !empty($event_details['description_lang']) ? $event_details['description_lang'] : '';
        } else {
            $description = !empty($event_details['description']) ? $event_details['description'] : '';
        }

        // Construir ubicación con place y town
        $place_parts = array();
        if (!empty($event_details['place'])) {
            $place_parts[] = esc_html($event_details['place']);
        }
        if (!empty($event_details['town'])) {
            $place_parts[] = esc_html($event_details['town']);
        }
        if (!empty($place_parts)) {
            $location = implode(', ', $place_parts);
        }
    }

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
    $image = !empty($image) ? str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $image) : '';
    $url_widget = !empty($event['url_widget']) ? $event['url_widget'] : '';
    $url = !empty($event['url']) ? $event['url'] : '';

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
    $month_names = $months[$lang_code];

    $category = isset($event['category']) ? esc_html($event['category']) : '';

    ob_start();
    ?>
    <div class="entrapolis-event-detail">
        <div class="entrapolis-event-detail-container">
            <!-- Imagen grande a la izquierda -->
            <?php if ($image): ?>
                <div class="entrapolis-event-image-column">
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>"
                        class="entrapolis-event-main-image">
                </div>
            <?php endif; ?>

            <!-- Información a la derecha -->
            <div class="entrapolis-event-content-column">
                <h1 class="entrapolis-event-title"><?php echo $title; ?></h1>

                <?php if ($category): ?>
                    <div class="entrapolis-event-category">
                        <span class="category-label"><?php echo esc_html($t['category']); ?></span>
                        <span class="category-value"><?php echo $category; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($description): ?>
                    <div class="entrapolis-event-description">
                        <?php echo wp_kses_post($description); ?>
                    </div>
                <?php endif; ?>

                <?php if ($location): ?>
                    <div class="entrapolis-event-location">
                        <span class="location-label"><?php echo esc_html($t['location']); ?></span>
                        <span class="location-value"><?php echo $location; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($related_events)): ?>
                    <div class="entrapolis-event-dates-section">
                        <div class="entrapolis-dates-header">
                            <h3 class="dates-title"><?php echo esc_html($t['dates_title']); ?></h3>
                            <div class="entrapolis-event-actions">
                                <?php if ($url_widget): ?>
                                    <a class="entrapolis-event-buy-link" href="<?php echo esc_url($url_widget); ?>"
                                        onclick="var w=900,h=700,l=(screen.width-w)/2,t=(screen.height-h)/2; window.open(this.href, 'CompraEntrades', 'width='+w+',height='+h+',left='+l+',top='+t+',resizable=yes,scrollbars=yes'); return false;">
                                        <?php echo esc_html($t['buy']); ?>
                                    </a>
                                <?php elseif ($url): ?>
                                    <a class="entrapolis-event-buy-link" href="<?php echo esc_url($url); ?>"
                                        onclick="var w=900,h=700,l=(screen.width-w)/2,t=(screen.height-h)/2; window.open(this.href, 'CompraEntrades', 'width='+w+',height='+h+',left='+l+',top='+t+',resizable=yes,scrollbars=yes'); return false;">
                                        <?php echo esc_html($t['buy']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <ul class="entrapolis-dates-list">
                            <?php foreach ($related_events as $rel_evt):
                                preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $rel_evt['date_readable'], $matches);

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
                                    } elseif ($lang_code === 'fr') {
                                        $formatted_date = "$day $month_name $year à $hour:$minute";
                                    } else {
                                        $formatted_date = "$day de $month_name de $year a les $hour:$minute";
                                    }
                                } else {
                                    $formatted_date = $rel_evt['date_readable'];
                                }
                                ?>
                                <li class="date-item"><?php echo esc_html($formatted_date); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        (function () {
            // Crear overlay y añadirlo al body
            let overlay = document.getElementById('entrapolis-purchase-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'entrapolis-purchase-overlay';
                overlay.className = 'entrapolis-purchase-overlay';
                overlay.style.display = 'none';
                overlay.innerHTML = '<button class="entrapolis-overlay-close">&times;</button>';
                document.body.appendChild(overlay);
            }

            const closeBtn = overlay.querySelector('.entrapolis-overlay-close');
            let purchaseWindow = null;

            // Interceptar clicks en botones de compra
            document.querySelectorAll('.entrapolis-event-buy-link').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const url = this.href;
                    const w = 900, h = 700;
                    const l = (screen.width - w) / 2;
                    const t = (screen.height - h) / 2;

                    purchaseWindow = window.open(url, 'CompraEntrades', 'width=' + w + ',height=' + h + ',left=' + l + ',top=' + t + ',resizable=yes,scrollbars=yes');

                    if (purchaseWindow) {
                        overlay.style.display = 'block';
                        document.body.style.overflow = 'hidden';
                        
                        // Dar foco inmediato a la ventana de compra
                        purchaseWindow.focus();

                        // Verificar si la ventana se cierra
                        const checkWindow = setInterval(function () {
                            if (purchaseWindow.closed) {
                                clearInterval(checkWindow);
                                overlay.style.display = 'none';
                                document.body.style.overflow = '';
                            }
                        }, 500);
                    }
                });
            });

            // Click en overlay (fondo oscuro) para devolver el foco a la ventana de compra
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay && purchaseWindow && !purchaseWindow.closed) {
                    purchaseWindow.focus();
                }
            });

            // Cerrar overlay y ventana solo con el botón X
            closeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (purchaseWindow && !purchaseWindow.closed) {
                    purchaseWindow.close();
                }
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            });

            // Mantener el foco en la ventana de compra cuando la ventana principal recupera el foco
            window.addEventListener('focus', function () {
                if (overlay.style.display === 'block' && purchaseWindow && !purchaseWindow.closed) {
                    setTimeout(function() {
                        purchaseWindow.focus();
                    }, 100);
                }
            });
        })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('entrapolis_event', 'entrapolis_shortcode_event_detail');