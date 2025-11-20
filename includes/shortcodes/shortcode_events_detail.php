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

    $months_catalan = entrapolis_get_catalan_months();
    $category = isset($event['category']) ? esc_html($event['category']) : '';
    $description = isset($event['description']) && !empty($event['description']) ? esc_html($event['description']) : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
    $location = isset($event['location']) && !empty($event['location']) ? esc_html($event['location']) : 'Teatre Principal';

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
                        <span class="category-label">Categoria:</span>
                        <span class="category-value"><?php echo $category; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($description): ?>
                    <div class="entrapolis-event-description">
                        <p><?php echo nl2br($description); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($location): ?>
                    <div class="entrapolis-event-location">
                        <span class="location-label">Ubicació:</span>
                        <span class="location-value"><?php echo $location; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($related_events)): ?>
                    <div class="entrapolis-event-dates-section">
                        <div class="entrapolis-dates-header">
                            <h3 class="dates-title">Dates disponibles</h3>
                            <div class="entrapolis-event-actions">
                                <?php if ($url_widget): ?>
                                    <a class="entrapolis-event-buy-link" href="<?php echo esc_url($url_widget); ?>"
                                        onclick="var w=900,h=700,l=(screen.width-w)/2,t=(screen.height-h)/2; window.open(this.href, 'CompraEntrades', 'width='+w+',height='+h+',left='+l+',top='+t+',resizable=yes,scrollbars=yes'); return false;">
                                        Comprar entrades
                                    </a>
                                <?php elseif ($url): ?>
                                    <a class="entrapolis-event-buy-link" href="<?php echo esc_url($url); ?>"
                                        onclick="var w=900,h=700,l=(screen.width-w)/2,t=(screen.height-h)/2; window.open(this.href, 'CompraEntrades', 'width='+w+',height='+h+',left='+l+',top='+t+',resizable=yes,scrollbars=yes'); return false;">
                                        Comprar entrades
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

                                    $month_name = isset($months_catalan[$month_num]) ? $months_catalan[$month_num] : $month_num;
                                    $formatted_date = "$day de $month_name de $year a les $hour:$minute";
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

            // Cerrar overlay y ventana
            closeBtn.addEventListener('click', function () {
                if (purchaseWindow && !purchaseWindow.closed) {
                    purchaseWindow.close();
                }
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            });
        })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('entrapolis_event', 'entrapolis_shortcode_event_detail');