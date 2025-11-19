<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Shortcode per mostrar el calendari d'esdeveniments
 * Usage: [entrapolis_calendar org="ORG_ID" months="3"]
 */
function entrapolis_shortcode_calendar($atts)
{
    $atts = shortcode_atts(array(
        'org' => ENTRAPOLIS_ORG_ID,
        'months' => 3,
        'detail_page' => '',
    ), $atts, 'entrapolis_calendar');

    $org_id = intval($atts['org']);
    $months_ahead = intval($atts['months']);

    $cache_key = 'entrapolis_events_' . $org_id;
    $events = get_transient($cache_key);

    if ($events === false) {
        $result = entrapolis_api_post('/api/events/', array(
            'application_id' => $org_id,
        ));

        if (is_wp_error($result) || empty($result['events'])) {
            return '';
        }

        $events = $result['events'];
        set_transient($cache_key, $events, 5 * 60);
    }

    // Agrupar eventos por fecha
    $events_by_date = array();
    foreach ($events as $event) {
        $date_str = $event['date_readable'];
        preg_match('/(\d{4}-\d{2}-\d{2})/', $date_str, $matches);
        if (!empty($matches[1])) {
            $date = $matches[1];
            if (!isset($events_by_date[$date])) {
                $events_by_date[$date] = array();
            }
            $events_by_date[$date][] = $event;
        }
    }

    // Generar meses
    $months_data = array();
    $current_date = new DateTime();

    for ($i = 0; $i < $months_ahead; $i++) {
        $month_date = clone $current_date;
        $month_date->modify("+{$i} months");

        $month_key = $month_date->format('Y-m');
        $month_name = strftime('%B %Y', $month_date->getTimestamp());

        // Días del mes
        $first_day = new DateTime($month_date->format('Y-m-01'));
        $last_day = new DateTime($month_date->format('Y-m-t'));

        $days = array();
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($first_day, $interval, $last_day->modify('+1 day'));

        $days_catalan = array('Dg', 'Dl', 'Dt', 'Dc', 'Dj', 'Dv', 'Ds');
        $today = date('Y-m-d');

        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            $day_of_week = $date->format('w'); // 0 (domingo) a 6 (sábado)
            $days[] = array(
                'date' => $date_str,
                'day_num' => $date->format('j'),
                'day_name' => $days_catalan[$day_of_week],
                'has_events' => isset($events_by_date[$date_str]),
                'events' => isset($events_by_date[$date_str]) ? $events_by_date[$date_str] : array(),
                'is_today' => $date_str === $today,
            );
        }

        $months_data[] = array(
            'key' => $month_key,
            'name' => ucfirst($month_name),
            'days' => $days,
        );
    }

    ob_start();
    ?>
    <div class="entrapolis-calendar" data-current-month="0">
        <div class="entrapolis-calendar-container">
            <?php foreach ($months_data as $index => $month): ?>
                <div class="entrapolis-calendar-month" data-month-index="<?php echo $index; ?>"
                    style="display: <?php echo $index === 0 ? 'flex' : 'none'; ?>;">
                    <div class="entrapolis-calendar-header">
                        <button class="entrapolis-calendar-prev" aria-label="Mes anterior">‹</button>
                        <div class="entrapolis-calendar-month-name"><?php echo esc_html($month['name']); ?></div>
                        <button class="entrapolis-calendar-next" aria-label="Mes següent">›</button>
                    </div>
                    <?php foreach ($month['days'] as $day):
                        $classes = array('entrapolis-calendar-day');
                        if ($day['has_events'])
                            $classes[] = 'has-events';
                        if ($day['is_today'])
                            $classes[] = 'is-today';
                        ?>
                        <div class="<?php echo implode(' ', $classes); ?>" data-date="<?php echo esc_attr($day['date']); ?>">
                            <div class="entrapolis-calendar-day-name"><?php echo esc_html($day['day_name']); ?></div>
                            <div class="entrapolis-calendar-day-number"><?php echo esc_html($day['day_num']); ?></div>

                            <?php if ($day['has_events']): ?>
                                <div class="entrapolis-calendar-tooltip">
                                    <?php
                                    $months_catalan = array(
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
                                    );

                                    foreach ($day['events'] as $event):
                                        $event_detail_url = '';
                                        if (!empty($atts['detail_page'])) {
                                            $event_detail_url = home_url('/' . sanitize_text_field($atts['detail_page']) . '/?entrapolis_event=' . intval($event['id']));
                                        }

                                        preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $event['date_readable'], $matches);
                                        if ($matches) {
                                            $hour = $matches[4];
                                            $minute = $matches[5];
                                            $formatted_date = "$hour:$minute";
                                        } else {
                                            $formatted_date = $event['date_readable'];
                                        }
                                        ?>
                                        <a href="<?php echo esc_url($event_detail_url); ?>" class="entrapolis-calendar-tooltip-event">
                                            <?php if (!empty($event['image'])):
                                                $image = str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $event['image']);
                                                ?>
                                                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($event['title']); ?>">
                                            <?php endif; ?>
                                            <div class="entrapolis-calendar-tooltip-content">
                                                <strong><?php echo esc_html($event['title']); ?></strong>
                                                <span><?php echo esc_html($formatted_date); ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        (function () {
            const calendar = document.querySelector('.entrapolis-calendar');
            if (!calendar) return;

            const prevBtn = calendar.querySelector('.entrapolis-calendar-prev');
            const nextBtn = calendar.querySelector('.entrapolis-calendar-next');
            const monthName = calendar.querySelector('.entrapolis-calendar-month-name');
            const months = calendar.querySelectorAll('.entrapolis-calendar-month');

            const monthNames = <?php echo json_encode(array_column($months_data, 'name')); ?>;
            let currentMonth = 0;

            function updateCalendar() {
                months.forEach((month, index) => {
                    month.style.display = index === currentMonth ? 'flex' : 'none';
                });

                const prevBtns = calendar.querySelectorAll('.entrapolis-calendar-prev');
                const nextBtns = calendar.querySelectorAll('.entrapolis-calendar-next');

                prevBtns.forEach(btn => btn.disabled = currentMonth === 0);
                nextBtns.forEach(btn => btn.disabled = currentMonth === months.length - 1);
            }

            calendar.addEventListener('click', (e) => {
                if (e.target.classList.contains('entrapolis-calendar-prev')) {
                    if (currentMonth > 0) {
                        currentMonth--;
                        updateCalendar();
                    }
                } else if (e.target.classList.contains('entrapolis-calendar-next')) {
                    if (currentMonth < months.length - 1) {
                        currentMonth++;
                        updateCalendar();
                    }
                }
            });

            updateCalendar();
        })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('entrapolis_calendar', 'entrapolis_shortcode_calendar');