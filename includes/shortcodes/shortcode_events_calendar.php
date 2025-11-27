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
        'months' => 24,
        'detail_page' => '',
        'lang' => '',
    ), $atts, 'entrapolis_calendar');

    $org_id = intval($atts['org']);
    $months_ahead = intval($atts['months']);
    $lang = sanitize_text_field($atts['lang']);
    $lang_code = in_array($lang, array('ca', 'es', 'en')) ? $lang : 'ca';

    // Traducciones
    $texts = array(
        'ca' => array(
            'prev' => 'Mes anterior',
            'next' => 'Mes següent',
            'days_header' => array('Dl', 'Dt', 'Dc', 'Dj', 'Dv', 'Ds', 'Dg'),
        ),
        'es' => array(
            'prev' => 'Mes anterior',
            'next' => 'Mes siguiente',
            'days_header' => array('Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do'),
        ),
        'en' => array(
            'prev' => 'Previous month',
            'next' => 'Next month',
            'days_header' => array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'),
        ),
    );
    $t = $texts[$lang_code];

    // Meses en diferentes idiomas
    $months_names = array(
        'ca' => array('gener', 'febrer', 'març', 'abril', 'maig', 'juny', 'juliol', 'agost', 'setembre', 'octubre', 'novembre', 'desembre'),
        'es' => array('enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'),
        'en' => array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'),
    );

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
    // Días de la semana según idioma
    $days_names = $t['days_header'];
    $today = date('Y-m-d');

    for ($i = 0; $i < $months_ahead; $i++) {
        $month_date = clone $current_date;
        $month_date->modify("+{$i} months");

        $month_key = $month_date->format('Y-m');
        $month_num = intval($month_date->format('n')) - 1;
        $year = $month_date->format('Y');
        $month_name = ucfirst($months_names[$lang_code][$month_num]) . ' ' . $year;

        // Primer y último día del mes
        $first_day = new DateTime($month_date->format('Y-m-01'));
        $last_day = new DateTime($month_date->format('Y-m-t'));

        // Vista horizontal (desktop) - todos los días en fila
        $days_horizontal = array();
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($first_day, $interval, $last_day->modify('+1 day'));

        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            $day_of_week = $date->format('w');
            $days_horizontal[] = array(
                'date' => $date_str,
                'day_num' => $date->format('j'),
                'day_name' => $days_names[$day_of_week == 0 ? 6 : $day_of_week - 1],
                'has_events' => isset($events_by_date[$date_str]),
                'events' => isset($events_by_date[$date_str]) ? $events_by_date[$date_str] : array(),
                'is_today' => $date_str === $today,
            );
        }

        // Vista grid (móvil) - semanas
        $first_day = new DateTime($month_date->format('Y-m-01'));
        $last_day = new DateTime($month_date->format('Y-m-t'));
        $first_day_of_week = $first_day->format('N');

        $weeks = array();
        $current_week = array();

        // Rellenar días vacíos antes del primer día del mes
        for ($j = 1; $j < $first_day_of_week; $j++) {
            $current_week[] = array(
                'date' => '',
                'day_num' => '',
                'is_empty' => true,
                'has_events' => false,
                'events' => array(),
                'is_today' => false,
            );
        }

        // Llenar los días del mes
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($first_day, $interval, $last_day->modify('+1 day'));

        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            $day_of_week_num = $date->format('N');

            $current_week[] = array(
                'date' => $date_str,
                'day_num' => $date->format('j'),
                'is_empty' => false,
                'has_events' => isset($events_by_date[$date_str]),
                'events' => isset($events_by_date[$date_str]) ? $events_by_date[$date_str] : array(),
                'is_today' => $date_str === $today,
            );

            if ($day_of_week_num == 7 || $date == $last_day) {
                while (count($current_week) < 7) {
                    $current_week[] = array(
                        'date' => '',
                        'day_num' => '',
                        'is_empty' => true,
                        'has_events' => false,
                        'events' => array(),
                        'is_today' => false,
                    );
                }
                $weeks[] = $current_week;
                $current_week = array();
            }
        }

        $months_data[] = array(
            'key' => $month_key,
            'name' => $month_name,
            'days' => $days_horizontal,
            'weeks' => $weeks,
            'days_header' => $days_names,
        );
    }

    // $months_catalan = entrapolis_get_catalan_months();

    ob_start();
    ?>
    <div class="entrapolis-calendar" data-current-month="0">
        <div class="entrapolis-calendar-container">
            <?php foreach ($months_data as $index => $month): ?>
                <div class="entrapolis-calendar-month" data-month-index="<?php echo $index; ?>"
                    style="display: <?php echo $index === 0 ? 'flex' : 'none'; ?>;">

                    <!-- Header con navegación -->
                    <div class="entrapolis-calendar-header">
                        <button class="entrapolis-calendar-prev" aria-label="<?php echo esc_attr($t['prev']); ?>">‹</button>
                        <div class="entrapolis-calendar-month-name"><?php echo esc_html($month['name']); ?></div>
                        <button class="entrapolis-calendar-next" aria-label="<?php echo esc_attr($t['next']); ?>">›</button>
                    </div>

                    <!-- Contenedor para vistas -->
                    <div class="entrapolis-calendar-views">
                        <!-- Vista horizontal para desktop -->
                        <div class="entrapolis-calendar-horizontal">
                            <?php foreach ($month['days'] as $day):
                                $classes = array('entrapolis-calendar-day');
                                if ($day['has_events'])
                                    $classes[] = 'has-events';
                                if ($day['is_today'])
                                    $classes[] = 'is-today';
                                ?>
                                <div class="<?php echo implode(' ', $classes); ?>"
                                    data-date="<?php echo esc_attr($day['date']); ?>">
                                    <div class="entrapolis-calendar-day-name"><?php echo esc_html($day['day_name']); ?></div>
                                    <div class="entrapolis-calendar-day-number"><?php echo esc_html($day['day_num']); ?></div>

                                    <?php if ($day['has_events']): ?>
                                        <div class="entrapolis-calendar-tooltip">
                                            <?php foreach ($day['events'] as $event):
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
                                                <a href="<?php echo esc_url($event_detail_url); ?>"
                                                    class="entrapolis-calendar-tooltip-event">
                                                    <?php if (!empty($event['image'])):
                                                        $image = str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $event['image']);
                                                        ?>
                                                        <img src="<?php echo esc_url($image); ?>"
                                                            alt="<?php echo esc_attr($event['title']); ?>">
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

                        <!-- Vista grid para móvil -->
                        <div class="entrapolis-calendar-grid">
                            <div class="entrapolis-calendar-weekdays">
                                <?php foreach ($month['days_header'] as $day_name): ?>
                                    <div class="entrapolis-calendar-weekday"><?php echo esc_html($day_name); ?></div>
                                <?php endforeach; ?>
                            </div>

                            <?php foreach ($month['weeks'] as $week): ?>
                                <div class="entrapolis-calendar-week">
                                    <?php foreach ($week as $day):
                                        $classes = array('entrapolis-calendar-day');
                                        if ($day['is_empty'])
                                            $classes[] = 'is-empty';
                                        if ($day['has_events'])
                                            $classes[] = 'has-events';
                                        if ($day['is_today'])
                                            $classes[] = 'is-today';
                                        ?>
                                        <div class="<?php echo implode(' ', $classes); ?>"
                                            data-date="<?php echo esc_attr($day['date']); ?>">
                                            <?php if (!$day['is_empty']): ?>
                                                <div class="entrapolis-calendar-day-number"><?php echo esc_html($day['day_num']); ?></div>

                                                <?php if ($day['has_events']): ?>
                                                    <div class="entrapolis-calendar-tooltip">
                                                        <?php foreach ($day['events'] as $event):
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
                                                            <a href="<?php echo esc_url($event_detail_url); ?>"
                                                                class="entrapolis-calendar-tooltip-event">
                                                                <?php if (!empty($event['image'])):
                                                                    $image = str_replace('https://www.entrapolis.com/', 'https://cdn.perception.es/v7/_ep/', $event['image']);
                                                                    ?>
                                                                    <img src="<?php echo esc_url($image); ?>"
                                                                        alt="<?php echo esc_attr($event['title']); ?>">
                                                                <?php endif; ?>
                                                                <div class="entrapolis-calendar-tooltip-content">
                                                                    <strong><?php echo esc_html($event['title']); ?></strong>
                                                                    <span><?php echo esc_html($formatted_date); ?></span>
                                                                </div>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
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
                }); const prevBtns = calendar.querySelectorAll('.entrapolis-calendar-prev');
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