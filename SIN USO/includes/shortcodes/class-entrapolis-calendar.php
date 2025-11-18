<?php
/**
 * Shortcode del calendario de eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Entrapolis_Calendar_Shortcode
{

    private $api;

    public function __construct()
    {
        $this->api = Entrapolis_API::get_instance();
        add_shortcode('entrapolis_calendar', array($this, 'render'));
    }

    public function render($atts)
    {
        $atts = shortcode_atts(array(
            'org' => ENTRAPOLIS_ORG_ID,
            'months' => 3,
        ), $atts, 'entrapolis_calendar');

        $org_id = intval($atts['org']);
        $months_ahead = intval($atts['months']);

        $events = $this->api->get_events($org_id);

        if (is_wp_error($events) || empty($events)) {
            return '';
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

            foreach ($period as $date) {
                $date_str = $date->format('Y-m-d');
                $days[] = array(
                    'date' => $date_str,
                    'day_num' => $date->format('j'),
                    'has_events' => isset($events_by_date[$date_str]),
                    'events' => isset($events_by_date[$date_str]) ? $events_by_date[$date_str] : array(),
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
            <div class="entrapolis-calendar-header">
                <button class="entrapolis-calendar-prev" aria-label="Mes anterior">‹</button>
                <div class="entrapolis-calendar-month-name"></div>
                <button class="entrapolis-calendar-next" aria-label="Mes següent">›</button>
            </div>

            <div class="entrapolis-calendar-container">
                <?php foreach ($months_data as $index => $month): ?>
                    <div class="entrapolis-calendar-month" data-month-index="<?php echo $index; ?>"
                        style="display: <?php echo $index === 0 ? 'flex' : 'none'; ?>;">
                        <?php foreach ($month['days'] as $day): ?>
                            <div class="entrapolis-calendar-day <?php echo $day['has_events'] ? 'has-events' : ''; ?>"
                                data-date="<?php echo esc_attr($day['date']); ?>">
                                <div class="entrapolis-calendar-day-number"><?php echo esc_html($day['day_num']); ?></div>

                                <?php if ($day['has_events']): ?>
                                    <div class="entrapolis-calendar-tooltip">
                                        <?php foreach ($day['events'] as $event): ?>
                                            <div class="entrapolis-calendar-tooltip-event">
                                                <?php if (!empty($event['image'])):
                                                    $image = entrapolis_get_cdn_image($event['image']);
                                                    ?>
                                                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($event['title']); ?>">
                                                <?php endif; ?>
                                                <div class="entrapolis-calendar-tooltip-content">
                                                    <strong><?php echo esc_html($event['title']); ?></strong>
                                                    <span><?php echo esc_html($event['date_readable']); ?></span>
                                                </div>
                                            </div>
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
                    monthName.textContent = monthNames[currentMonth];
                    prevBtn.disabled = currentMonth === 0;
                    nextBtn.disabled = currentMonth === months.length - 1;
                }

                prevBtn.addEventListener('click', () => {
                    if (currentMonth > 0) {
                        currentMonth--;
                        updateCalendar();
                    }
                });

                nextBtn.addEventListener('click', () => {
                    if (currentMonth < months.length - 1) {
                        currentMonth++;
                        updateCalendar();
                    }
                });

                // Posicionar tooltips con position: fixed
                const days = calendar.querySelectorAll('.entrapolis-calendar-day.has-events');
                days.forEach(day => {
                    const tooltip = day.querySelector('.entrapolis-calendar-tooltip');
                    if (!tooltip) return;

                    day.addEventListener('mouseenter', function () {
                        const rect = this.getBoundingClientRect();
                        tooltip.style.left = rect.left + (rect.width / 2) + 'px';
                        tooltip.style.top = (rect.top - 10) + 'px';
                        tooltip.style.transform = 'translate(-50%, -100%)';
                    });
                });

                updateCalendar();
            })();
        </script>
        <?php
        return ob_get_clean();
    }
}

new Entrapolis_Calendar_Shortcode();
