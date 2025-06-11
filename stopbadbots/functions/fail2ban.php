<?php

/**
 * @ Author: Bill Minozzi -
 * @ Copyright: 2023 www.BillMinozzi.com
 * @ Modified time: 2023-07-17 2024-02-27
 */
if (!defined("ABSPATH")) {
    exit();
} // Exit if accessed directly
/**
 * Registers the shortcode to display the daily Fail2Ban block report.
 * Usage: [sbb_fail2ban_daily_report]
 */
//add_shortcode('sbb_fail2ban_daily_report', 'stopbadbots_render_daily_ban_report');
/**
 * Function that retrieves data and renders the daily Fail2Ban block report.
 *
 * @param array $atts Shortcode attributes (not used in this simple version).
 * @return string HTML of the report table or 'no data' message.
 */
/*
 `id` bigint(20) UNSIGNED NOT NULL,
 `ip` varchar(45) NOT NULL,
 `timestamp` datetime NOT NULL,
 `jail` varchar(100) NOT NULL,
 `reason` text DEFAULT NULL,
 `attempts` int(11) NOT NULL,
 `log_line` text DEFAULT NULL,
 `host` varchar(100) DEFAULT NULL,
 `port` int(11) DEFAULT NULL,
 `protocol` varchar(10) DEFAULT NULL,
 `ban_duration` int(11) NOT NULL
 */
function stopbadbots_render_ban_report()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'stopbadbots_fail2ban_logs';
    $title_color_style = 'style="color: #0073aa;"'; // WordPress blue for titles

?>
    <div id="stopbadbots-logo">
        <img alt="logo" src="<?php echo esc_attr(
                                    STOPBADBOTSIMAGES
                                ); ?>/logo.png" width="250px" />
    </div>
    <?php
    echo "<h2 {$title_color_style}>SBB Fail2ban Monitor</h2>";
    echo '<div class="sbb_fail2ban_monitor_description">';
    echo '<p>' . esc_html__("The use of this page is optional, and it's intended for more advanced users.", 'stopbadbots') . '</p>';
    echo '<p>' . esc_html__("The SBB Fail2Ban Monitor brings your server's powerful Fail2Ban protection into a clear, visual WordPress dashboard – the user-friendly GUI many have been waiting for!", 'stopbadbots') . '</p>';
    echo '<p>' . esc_html__("Currently, you can track key details like the offending IP, when the last attempt occurred, the specific Security Rule (jail) triggered, the number of attempts, and how long the ban lasts", 'stopbadbots') . '</p>';
    echo '<p>' . esc_html__("This is your first step towards richer insights like daily totals, activity graphs, and more detailed ban reasons.", 'stopbadbots') . '</p>';
    echo '<p><small>' .
        esc_html__("Please ensure Fail2Ban is installed and operational on your server, VPS or Cloud VPS for this monitor to function. Our support doesn't include the installation or configuration of Fail2Ban on your server.", 'stopbadbots') .
        esc_html__('All features on this page are available only in our Pro (or premium) version.', 'stopbadbots') .
        '</small></p>';
    echo '<p>';
    echo sprintf(
        esc_html__('Visit our site for more details on %s.', 'stopbadbots'),
        '<a href="https://stopbadbots.com/integrating-antihacker-stopbadbots-with-fail2ban/" target="_blank">' . esc_html__('integrating StopBadBots with Fail2Ban', 'stopbadbots') . '</a>'
    );
    echo '</p>';
    echo '</div>'; // End of .sbb_fail2ban_monitor_description div

    // START OF CHARTS CONTAINER
    echo '<div class="sbb-charts-container">';

    // --- Block Table by Day Section (already present in your code) ---
    $query_30_days = "
    SELECT
        DATE(timestamp) AS ban_date,
        COUNT(*) AS ban_count
    FROM
        `{$table_name}`
    GROUP BY
        ban_date
    ORDER BY
        ban_date ASC
";
    $results_30_days = $wpdb->get_results($query_30_days, ARRAY_A);

    if (!empty($results_30_days)) {
        // START OF FIRST CHART WRAPPER
        echo '<div class="sbb-chart-wrapper">';

        $graph_data_30_days = array();
        $graph_ticks_30_days = array();
        $attack_counts_by_date_map = [];
        for ($i = 29; $i >= 0; $i--) {
            $date_map_key = date('Y-m-d', strtotime("-$i days", current_time('timestamp')));
            $attack_counts_by_date_map[$date_map_key] = 0;
        }
        foreach ($results_30_days as $row) {
            if (isset($attack_counts_by_date_map[$row['ban_date']])) {
                $attack_counts_by_date_map[$row['ban_date']] = (int)$row['ban_count'];
            }
        }
        $idx = 0;
        $days_to_skip_ticks = 2;

        foreach ($attack_counts_by_date_map as $date_str => $count) {
            $graph_data_30_days[] = [$idx, $count];
            if ($idx % $days_to_skip_ticks == 0) {
                $graph_ticks_30_days[] = [$idx, date('d/m', strtotime($date_str))];
            }
            $idx++;
        }

        echo "<h2 {$title_color_style}>" . esc_html__('Blocks Last 30 Days', 'stopbadbots') . '</h2>';

        echo '<script type="text/javascript">';
        echo 'jQuery(function() {';
        echo 'var d2 = [';
        $data_parts = [];
        foreach ($graph_data_30_days as $point) {
            $data_parts[] = '[' . esc_js($point[0]) . ',' . esc_js($point[1]) . ']';
        }
        echo implode(',', $data_parts);
        echo '];';
        echo 'var ticks_30_days_chart = ['; // Changed variable name for clarity
        $tick_parts = [];
        foreach ($graph_ticks_30_days as $tick) {
            $tick_parts[] = '[' . esc_js($tick[0]) . ',"' . esc_js($tick[1]) . '"]';
        }
        echo implode(',', $tick_parts);
        echo '];';
    ?>
        var options_30_days = { // Changed variable name for clarity
        series: {
        lines: { show: true },
        points: { show: true },
        color: "#ff0000"
        },
        grid: {
        hoverable: true,
        clickable: true,
        borderColor: "#CCCCCC",
        color: "#333333",
        backgroundColor: { colors: ["#fff", "#eee"]}
        },
        xaxis:{
        font:{
        size:8,
        style:"italic",
        weight:"normal",
        family:"sans-serif",
        color: "#ff0000",
        variant:"small-caps"
        },
        ticks: ticks_30_days_chart,
        },
        yaxis: {
        font:{
        size:8,
        style:"italic",
        weight:"bold",
        family:"sans-serif",
        color: "#616161",
        variant:"small-caps"
        },
        tickFormatter: function stopbadbots_suffixFormatter(val, axis) {
        return (val.toFixed(0));
        }
        }
        };
        jQuery.plot("#placeholder_30_days_chart", [ d2 ], options_30_days); // Changed ID for clarity
        });
        </script>
    <?php
        echo '<div id="placeholder_30_days_chart" style="min-width:250px; height:165px;"></div>';
        echo '</div>'; // END OF FIRST CHART WRAPPER
    }

    // --- Chart for Blocks in the Last 24 Hours ---
    $twenty_four_hours_ago_server_local = date('Y-m-d H:i:s', current_time('timestamp') - (24 * HOUR_IN_SECONDS));
    $query_24h_chart = $wpdb->prepare("
        SELECT
            DATE_FORMAT(timestamp, %s) AS hour_block,
            COUNT(*) AS ban_count
        FROM
            `{$table_name}`
        WHERE
            timestamp >= %s
        GROUP BY
            hour_block
        ORDER BY
            hour_block ASC
    ", '%Y-%m-%d %H:00:00', $twenty_four_hours_ago_server_local);
    $results_24h_raw = $wpdb->get_results($query_24h_chart, ARRAY_A);

    if (!empty($results_24h_raw)) {
        // START OF SECOND CHART WRAPPER
        echo '<div class="sbb-chart-wrapper">';
        echo "<h2 {$title_color_style}>" . esc_html__('Blocks in the Last 24 Hours', 'stopbadbots') . '</h2>';

        $graph_data_24h = [];
        $graph_ticks_24h = [];
        $hourly_counts_map_local = [];
        $current_server_time_wp = current_time('timestamp');
        for ($h = 0; $h < 24; $h++) {
            $target_hour_ts = $current_server_time_wp - ((23 - $h) * HOUR_IN_SECONDS);
            $hour_key_local = date('Y-m-d H:00:00', $target_hour_ts);
            $hourly_counts_map_local[$hour_key_local] = 0;
        }
        foreach ($results_24h_raw as $row) {
            if (isset($hourly_counts_map_local[$row['hour_block']])) {
                $hourly_counts_map_local[$row['hour_block']] = (int)$row['ban_count'];
            }
        }
        $idx_24h = 0;
        foreach ($hourly_counts_map_local as $hour_str_local => $count) {
            $graph_data_24h[] = [$idx_24h, $count];
            $tick_label = date('H', strtotime($hour_str_local));
            $graph_ticks_24h[] = [$idx_24h, $tick_label];
            $idx_24h++;
        }

        echo '<script type="text/javascript">';
        echo 'jQuery(function() {';
        echo 'var data_hourly_chart = [';
        $js_data_parts_24h = [];
        foreach ($graph_data_24h as $point) {
            $js_data_parts_24h[] = '[' . esc_js($point[0]) . ',' . esc_js($point[1]) . ']';
        }
        echo implode(',', $js_data_parts_24h);
        echo '];';
        echo 'var ticks_hourly_chart = [';
        $js_tick_parts_24h = [];
        foreach ($graph_ticks_24h as $tick) {
            $js_tick_parts_24h[] = '[' . esc_js($tick[0]) . ',"' . esc_js($tick[1]) . '"]';
        }
        echo implode(',', $js_tick_parts_24h);
        echo '];';
    ?>
        var options_hourly = {
        series: {
        lines: { show: true },
        points: { show: true },
        color: "#0073aa"
        },
        grid: {
        hoverable: true,
        clickable: true,
        borderColor: "#CCCCCC",
        color: "#333333",
        backgroundColor: { colors: ["#fff", "#eee"]}
        },
        xaxis:{
        font:{
        size:9,
        style:"normal",
        weight:"normal",
        family:"sans-serif",
        color: "#0073aa",
        variant:"normal"
        },
        ticks: ticks_hourly_chart
        },
        yaxis: {
        font:{
        size:8,
        style:"italic",
        weight:"bold",
        family:"sans-serif",
        color: "#616161",
        variant:"small-caps"
        },
        tickFormatter: function stopbadbots_suffixFormatter(val, axis) {
        return (val.toFixed(0));
        }
        }
        };
        jQuery.plot("#placeholder_hourly_blocks_chart", [ data_hourly_chart ], options_hourly); // Changed ID for clarity
        });
        </script>
<?php
        echo '<div id="placeholder_hourly_blocks_chart" style="min-width:250px; height:165px;"></div>';
        echo '</div>'; // END OF SECOND CHART WRAPPER
    }
    echo '</div>'; // END OF CHARTS CONTAINER

    // --- New Row with Two Columns for Jail Stats ---
    echo '<div style="display: flex; flex-wrap: wrap; margin-top: 30px; width: 100%; clear: both;">';

    // --- Left Column: Jails Stats Last 30 Days ---
    echo '<div style="flex: 1; min-width: 300px; padding-right: 15px; box-sizing: border-box;">';
    echo "<h3 {$title_color_style}>" . esc_html__('Jail Activity (Last 30 Days)', 'stopbadbots') . '</h3>';

    $thirty_days_ago_datetime = date('Y-m-d H:i:s', current_time('timestamp') - (30 * DAY_IN_SECONDS));
    $query_jails_30_days = $wpdb->prepare("
        SELECT
            jail,
            COUNT(*) AS total_bans
        FROM
            `{$table_name}`
        WHERE
            timestamp >= %s
        GROUP BY
            jail
        ORDER BY
            total_bans DESC
    ", $thirty_days_ago_datetime);
    $results_jails_30_days = $wpdb->get_results($query_jails_30_days, ARRAY_A);

    if (!empty($results_jails_30_days)) {
        // Calculate total bans for percentage calculation
        $total_bans_for_period_30_days = 0;
        foreach ($results_jails_30_days as $row) {
            $total_bans_for_period_30_days += (int)$row['total_bans'];
        }

        echo '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ccc;">';
        echo '<table class="wp-list-table widefat fixed striped pages">';
        echo '<thead>';
        echo '<tr>';
        $th_style_jails = 'style="position: sticky; top: 0; background-color: #f9f9f9; z-index: 1; box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);"';
        echo "<th scope=\"col\" {$th_style_jails}>" . esc_html__('Jail', 'stopbadbots') . "</th>";
        echo "<th scope=\"col\" {$th_style_jails}>" . esc_html__('Total Bans', 'stopbadbots') . "</th>";
        echo "<th scope=\"col\" {$th_style_jails}>" . esc_html__('Percentage', 'stopbadbots') . "</th>"; // New Column
        echo '</tr>';
        echo '</thead>';
        echo '<tbody id="the-list-jails-30-days">';
        foreach ($results_jails_30_days as $row) {
            $percentage = ($total_bans_for_period_30_days > 0) ? ((int)$row['total_bans'] / $total_bans_for_period_30_days) * 100 : 0;
            echo '<tr>';
            echo '<td>' . esc_html($row['jail']) . '</td>';
            echo '<td>' . esc_html($row['total_bans']) . '</td>';
            echo '<td>' . number_format($percentage, 2) . '%</td>'; // Display Percentage
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo '<p>' . esc_html__('No jail activity recorded in the last 30 days.', 'stopbadbots') . '</p>';
    }
    echo '</div>'; // End Left column

    // --- Right Column: Jails Stats Today ---
    echo '<div style="flex: 1; min-width: 300px; padding-left: 15px; box-sizing: border-box;">';
    echo "<h3 {$title_color_style}>" . esc_html__('Jail Activity (Today)', 'stopbadbots') . '</h3>';

    $today_date = current_time('Y-m-d');
    $query_jails_today = $wpdb->prepare("
        SELECT
            jail,
            COUNT(*) AS total_bans
        FROM
            `{$table_name}`
        WHERE
            DATE(timestamp) = %s
        GROUP BY
            jail
        ORDER BY
            total_bans DESC
    ", $today_date);
    $results_jails_today = $wpdb->get_results($query_jails_today, ARRAY_A);

    if (!empty($results_jails_today)) {
        // Calculate total bans for percentage calculation
        $total_bans_for_today = 0;
        foreach ($results_jails_today as $row) {
            $total_bans_for_today += (int)$row['total_bans'];
        }

        echo '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ccc;">';
        echo '<table class="wp-list-table widefat fixed striped pages">';
        echo '<thead>';
        echo '<tr>';
        echo "<th scope=\"col\" {$th_style_jails}>" . esc_html__('Jail', 'stopbadbots') . "</th>";
        echo "<th scope=\"col\" {$th_style_jails}>" . esc_html__('Total Bans', 'stopbadbots') . "</th>";
        echo "<th scope=\"col\" {$th_style_jails}>" . esc_html__('Percentage', 'stopbadbots') . "</th>"; // New Column
        echo '</tr>';
        echo '</thead>';
        echo '<tbody id="the-list-jails-today">';
        foreach ($results_jails_today as $row) {
            $percentage_today = ($total_bans_for_today > 0) ? ((int)$row['total_bans'] / $total_bans_for_today) * 100 : 0;
            echo '<tr>';
            echo '<td>' . esc_html($row['jail']) . '</td>';
            echo '<td>' . esc_html($row['total_bans']) . '</td>';
            echo '<td>' . number_format($percentage_today, 2) . '%</td>'; // Display Percentage
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    } else {
        echo '<p>' . esc_html__('No jail activity recorded today.', 'stopbadbots') . '</p>';
    }
    echo '</div>'; // End Right column
    echo '</div>'; // End Flex container 

    // --- Last 100 Fail2Ban Block Records ---
    $query_last_100 = "
    SELECT
        id, ip, timestamp, jail, reason, attempts, host, ban_duration
    FROM
        `{$table_name}`
    ORDER BY
        timestamp DESC
    LIMIT 100
    ";
    $results_last_100 = $wpdb->get_results($query_last_100, ARRAY_A);
    if (!empty($results_last_100)) {
        echo "<h2 {$title_color_style} style=\"margin-top: 30px; clear: both;\">" . esc_html__('Last 100 Fail2Ban Block Records', 'stopbadbots') . '</h2>';
        echo '<div style="max-height: 600px; overflow-y: auto; overflow-x: auto; border: 1px solid #ccc;">';
        echo '<table class="wp-list-table widefat fixed striped pages">';
        echo '<thead>';
        echo '<tr>';
        $th_style = 'style="position: sticky; top: 0; background-color: #f9f9f9; z-index: 1; box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);"';
        echo "<th scope=\"col\" {$th_style}>" . esc_html__('Date and Time', 'stopbadbots') . "</th>";
        echo "<th scope=\"col\" {$th_style}>" . esc_html__('IP', 'stopbadbots') . "</th>";
        echo "<th scope=\"col\" {$th_style}>" . esc_html__('Jail', 'stopbadbots') . "</th>";
        echo "<th scope=\"col\" {$th_style}>" . esc_html__('Attempts', 'stopbadbots') . "</th>";
        echo "<th scope=\"col\" {$th_style}>" . esc_html__('Ban Duration (seconds)', 'stopbadbots') . "</th>";
        echo '</tr>';
        echo '</thead>';
        echo '<tbody id="the-list-last-100">'; // Changed ID for clarity
        foreach ($results_last_100 as $row) {
            $formatted_datetime = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($row['timestamp']));
            echo '<tr>';
            echo '<td>' . esc_html($formatted_datetime) . '</td>';
            echo '<td>' . esc_html($row['ip']) . '</td>';
            echo '<td>' . esc_html($row['jail']) . '</td>';
            echo '<td>' . esc_html($row['attempts']) . '</td>';
            echo '<td>' . esc_html($row['ban_duration']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
}

function stopbadbots_add_menu_fail2ban()
{
    $stopbadbots_table_page = add_submenu_page(
        "stop_bad_bots_plugin",
        "Fail2ban Monitor",
        "Fail2ban Monitor",
        "manage_options",
        "stopbadbots_my-custom-submenu-page-fail2ban",
        "stopbadbots_render_ban_report"
    );
}
if (is_admin() && current_user_can("manage_options")) {
    add_action('admin_menu', 'stopbadbots_add_menu_fail2ban');
}

?>