<?php
/**
 * Shortcode per llistar esdeveniments en format llista (taula)
 * Usage: [entrapolis_events_list org="ORG_ID" detail_page="detail-page-slug" limit="10"]
 *
 * @package Entrapolis
 */

if (!defined('ABSPATH')) {
    exit;
}

function entrapolis_shortcode_events_list($atts)
{
    $atts = shortcode_atts(array(
        'org' => ENTRAPOLIS_ORG_ID,
        'detail_page' => '',
        'limit' => 10,
        'lang' => '',
    ), $atts, 'entrapolis_events_list');

    $org_id = intval($atts['org']);
    $detail_page_slug = sanitize_text_field($atts['detail_page']);
    $limit = intval($atts['limit']);
    $lang = sanitize_text_field($atts['lang']);
    $lang_code = in_array($lang, array('ca', 'es', 'en', 'fr')) ? $lang : 'ca';

    // Traducciones
    $texts = array(
        'ca' => array(
            'buy' => 'Comprar entrades',
            'load_more' => 'Carregar més esdeveniments',
            'loading' => 'Carregant...',
        ),
        'es' => array(
            'buy' => 'Comprar entradas',
            'load_more' => 'Cargar más eventos',
            'loading' => 'Cargando...',
        ),
        'en' => array(
            'buy' => 'Buy tickets',
            'load_more' => 'Load more events',
            'loading' => 'Loading...',
        ),
        'fr' => array(
            'buy' => 'Acheter des billets',
            'load_more' => 'Charger plus d\'événements',
            'loading' => 'Chargement...',
        ),
    );
    $t = $texts[$lang_code];

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
                'category' => isset($event['category']) ? $event['category'] : 'Generic',
                'dates' => array(),
                'ids' => array(),
                'url_widget' => !empty($event['url_widget']) ? $event['url_widget'] : '',
                'url' => !empty($event['url']) ? $event['url'] : '',
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

    $months_catalan = entrapolis_get_catalan_months();
    $unique_id = 'entrapolis-list-' . uniqid();

    ob_start();
    ?>
    <div class="entrapolis-events-list-wrapper" id="<?php echo $unique_id; ?>" data-org="<?php echo $org_id; ?>"
        data-detail-page="<?php echo esc_attr($detail_page_slug); ?>" data-limit="<?php echo $limit; ?>"
        data-offset="<?php echo $limit; ?>">
        <table class="entrapolis-events-table">
            <tbody class="entrapolis-events-tbody">
                <?php foreach ($grouped_events as $event):
                    $id = intval($event['ids'][0]);
                    $title = esc_html($event['title']);
                    $image = !empty($event['image']) ? str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $event['image']) : '';

                    $buy_url = !empty($event['url_widget']) ? $event['url_widget'] : (!empty($event['url']) ? $event['url'] : '');

                    $detail_url = '';
                    if ($detail_page_slug) {
                        $detail_url = home_url('/' . $detail_page_slug . '/?entrapolis_event=' . $id);
                    }

                    // Formatear todas las fechas según idioma
                    $formatted_dates = array();
                    foreach ($event['dates'] as $date) {
                        preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date, $matches);
                        if ($matches) {
                            $year = $matches[1];
                            $month_num = intval($matches[2]);
                            $day = intval($matches[3]);
                            $hour = $matches[4];
                            $minute = $matches[5];
                            $month_name = isset($month_names[$month_num]) ? $month_names[$month_num] : $month_num;
                            if ($lang_code === 'ca') {
                                $formatted_dates[] = "$day de $month_name de $year a les $hour:$minute";
                            } elseif ($lang_code === 'es') {
                                $formatted_dates[] = "$day de $month_name de $year a las $hour:$minute";
                            } elseif ($lang_code === 'en') {
                                $formatted_dates[] = "$month_name $day, $year at $hour:$minute";
                            } elseif ($lang_code === 'fr') {
                                $formatted_dates[] = "$day $month_name $year à $hour:$minute";
                            } else {
                                $formatted_dates[] = "$day de $month_name de $year a les $hour:$minute";
                            }
                        } else {
                            $formatted_dates[] = $date;
                        }
                    }
                    ?>
                    <tr class="entrapolis-event-row" data-detail-url="<?php echo esc_url($detail_url); ?>"
                        style="cursor: pointer;">
                        <td class="col-image">
                            <?php if ($image): ?>
                                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>"
                                    class="entrapolis-list-image">
                            <?php endif; ?>
                        </td>
                        <td class="col-title">
                            <strong><?php echo $title; ?></strong>
                        </td>
                        <td class="col-dates">
                            <?php if (count($formatted_dates) === 1): ?>
                                <span class="single-date"><?php echo esc_html($formatted_dates[0]); ?></span>
                            <?php else: ?>
                                <ul class="dates-list">
                                    <?php foreach ($formatted_dates as $date): ?>
                                        <li><?php echo esc_html($date); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </td>
                        <td class="col-action">
                            <?php if ($buy_url): ?>
                                <a href="javascript:void(0);" class="entrapolis-btn-buy"
                                    data-buy-url="<?php echo esc_url($buy_url); ?>">
                                    <?php echo esc_html($t['buy']); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($has_more): ?>
            <div class="entrapolis-load-more-wrapper">
                <button class="entrapolis-load-more-btn" data-target="<?php echo $unique_id; ?>">
                    <?php echo esc_html($t['load_more']); ?>
                </button>
                <span class="entrapolis-loading" style="display:none;"><?php echo esc_html($t['loading']); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <script>
        (function () {
            const container = document.getElementById('<?php echo $unique_id; ?>');
            if (!container) return;

            const loadMoreBtn = container.querySelector('.entrapolis-load-more-btn');
            const loadingSpan = container.querySelector('.entrapolis-loading');
            const tbody = container.querySelector('.entrapolis-events-tbody');

            // Crear overlay y añadirlo al body
            let overlay = document.getElementById('entrapolis-purchase-overlay-list');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'entrapolis-purchase-overlay-list';
                overlay.className = 'entrapolis-purchase-overlay';
                overlay.style.display = 'none';
                overlay.innerHTML = '<button class="entrapolis-overlay-close">&times;</button>';
                document.body.appendChild(overlay);
            }

            const closeBtn = overlay.querySelector('.entrapolis-overlay-close');
            let purchaseWindow = null;

            // Click en fila para ir al detalle
            tbody.addEventListener('click', function (e) {
                const row = e.target.closest('.entrapolis-event-row');
                if (row && row.dataset.detailUrl && !e.target.closest('.entrapolis-btn-buy')) {
                    window.location.href = row.dataset.detailUrl;
                }
            });

            // Click en botón de compra para abrir ventana popup
            tbody.addEventListener('click', function (e) {
                const buyBtn = e.target.closest('.entrapolis-btn-buy');
                if (buyBtn) {
                    e.preventDefault();
                    e.stopPropagation();

                    const url = buyBtn.dataset.buyUrl;
                    console.log('Buy URL:', url);

                    if (url) {
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
                    }
                }
            });            // Click en overlay (fondo oscuro) para devolver el foco a la ventana de compra
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
                        action: 'entrapolis_load_more_list',
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
                            tbody.insertAdjacentHTML('beforeend', data.data.html);
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
add_shortcode('entrapolis_events_list', 'entrapolis_shortcode_events_list');
