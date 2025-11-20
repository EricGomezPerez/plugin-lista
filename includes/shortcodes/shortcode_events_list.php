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
    ), $atts, 'entrapolis_events_list');

    $org_id = intval($atts['org']);
    $detail_page_slug = sanitize_text_field($atts['detail_page']);
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

                    // Formatear todas las fechas
                    $formatted_dates = array();
                    foreach ($event['dates'] as $date) {
                        preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date, $matches);
                        if ($matches) {
                            $year = $matches[1];
                            $month_num = intval($matches[2]);
                            $day = intval($matches[3]);
                            $hour = $matches[4];
                            $minute = $matches[5];
                            $month_name = isset($months_catalan[$month_num]) ? $months_catalan[$month_num] : $month_num;
                            $formatted_dates[] = "$day de $month_name de $year a les $hour:$minute";
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
                                    Comprar entrades
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
                    Carregar més esdeveniments
                </button>
                <span class="entrapolis-loading" style="display:none;">Carregant...</span>
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
            });            // Cerrar overlay y ventana
            closeBtn.addEventListener('click', function () {
                if (purchaseWindow && !purchaseWindow.closed) {
                    purchaseWindow.close();
                }
                overlay.style.display = 'none';
                document.body.style.overflow = '';
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
