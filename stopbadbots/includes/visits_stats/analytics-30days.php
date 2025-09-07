<?php
/*
Description: Creates a chart of visits over the last 30 days using Chart.js.
Version: 1.0
Author: Bill Minozzi
2/24
*/
if (!defined('ABSPATH')) {
    die('Invalid request.');
}

$table_name = $wpdb->prefix . 'sbb_visitorslog';

// Query for total unique visits
$total_visits_results = $wpdb->get_results("SELECT DATE_FORMAT(date, '%Y-%m-%d') AS visit_date, COUNT(DISTINCT ip) AS total_visits FROM {$wpdb->prefix}sbb_visitorslog  WHERE date >= CURDATE() - INTERVAL 30 DAY GROUP BY DATE(date)");
//$total_visits_results = $wpdb->get_results($wpdb->prepare("SELECT DATE_FORMAT(date, '%Y-%m-%d') AS visit_date, COUNT(DISTINCT ip) AS total_visits FROM %i WHERE date >= CURDATE() - INTERVAL 30 DAY GROUP BY DATE(date)", $table_name));

if ($total_visits_results == NULL) {
    echo '<center>';
    echo esc_attr__('No vists, try again later...', 'stopbadbots');
    echo '</center>';
    return;
}

//var_export($total_visits_results);
// die();



// Query for bot visits
//$bot_visits_results = $wpdb->get_results("SELECT DATE_FORMAT(date, '%Y-%m-%d') AS visit_date, COUNT(DISTINCT ip) AS bot_visits FROM {$wpdb->prefix}sbb_visitorslog  WHERE date >= CURDATE() - INTERVAL 30 DAY AND bot = '1' GROUP BY DATE(date)");

// new $bot_visits_results = $wpdb->get_results($wpdb->prepare("SELECT DATE_FORMAT(date, '%Y-%m-%d') AS visit_date, COUNT(DISTINCT ip) AS bot_visits FROM %i WHERE date >= CURDATE() - INTERVAL 30 DAY AND bot = '1' GROUP BY DATE(date)", $table_name));

$bot_visits_results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT DATE_FORMAT(date, '%%Y-%%m-%%d') AS visit_date, COUNT(DISTINCT ip) AS bot_visits 
            FROM %i 
            WHERE date >= CURDATE() - INTERVAL 30 DAY AND bot = '1' 
            GROUP BY DATE(date)",
        $table_name
    )
);




// Query for human visits
//$human_visits_results = $wpdb->get_results("SELECT DATE_FORMAT(date, '%Y-%m-%d') AS visit_date, COUNT(DISTINCT ip) AS human_visits FROM {$wpdb->prefix}sbb_visitorslog  WHERE date >= CURDATE() - INTERVAL 30 DAY AND human = 'Human' GROUP BY DATE(date)");
//$human_visits_results = $wpdb->get_results($wpdb->prepare("SELECT DATE_FORMAT(date, '%Y-%m-%d') AS visit_date, COUNT(DISTINCT ip) AS human_visits FROM %i WHERE date >= CURDATE() - INTERVAL 30 DAY AND human = 'Human' GROUP BY DATE(date)", $table_name));
/*
    $human_visits_results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DATE_FORMAT(date, '%%Y-%%m-%%d') AS visit_date, COUNT(DISTINCT ip) AS human_visits 
            FROM %i 
            WHERE date >= CURDATE() - INTERVAL 30 DAY AND bot = '1' 
            AND human = 'Human'
            GROUP BY DATE(date)",
            $table_name
        )
    );
    */
$human_visits_results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT DATE_FORMAT(date, '%%Y-%%m-%%d') AS visit_date, COUNT(DISTINCT ip) AS human_visits 
        FROM %i 
        WHERE date >= CURDATE() - INTERVAL 30 DAY AND human = 'Human' 
        GROUP BY DATE(date)",
        $table_name
    )
);





// Query for indeterminate visits
//$indeterminate_visits_results = $wpdb->get_results("SELECT DATE_FORMAT(date, '%Y-%m-%d') AS visit_date, COUNT(*) AS indeterminate_visits FROM {$wpdb->prefix}sbb_visitorslog  WHERE date >= CURDATE() - INTERVAL 30 DAY AND (bot = '?' OR human = '?') GROUP BY DATE(date)");
// Merge results into a single array
$data = [];
foreach ($total_visits_results as $row) {
    $data[$row->visit_date] = [
        'visit_date' => $row->visit_date,
        'total_visits' => $row->total_visits,
        'bot_visits' => 0,
        'human_visits' => 0,
        //  'indeterminate_visits' => 0
    ];
}
foreach ($bot_visits_results as $row) {
    $data[$row->visit_date]['bot_visits'] = $row->bot_visits;
}
foreach ($human_visits_results as $row) {
    $data[$row->visit_date]['human_visits'] = $row->human_visits;
}
//foreach ($indeterminate_visits_results as $row) {
//    $data[$row->visit_date]['indeterminate_visits'] = $row->indeterminate_visits;
//}
// Convert associative array to indexed array
$data = array_values($data);



/*

// === DEBUG BOT VISITS ===
if ($bot_visits_results) {
    $total_bots = 0;
    echo '<h3>Debug: Bot Visits (last 30 days)</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>Date</th><th>Bot Visits</th></tr>';

    foreach ($bot_visits_results as $row) {
        $total_bots += intval($row->bot_visits);
        echo '<tr>';
        echo '<td>' . esc_html($row->visit_date) . '</td>';
        echo '<td>' . esc_html($row->bot_visits) . '</td>';
        echo '</tr>';
    }

    echo '<tr style="font-weight:bold;"><td>Total</td><td>' . $total_bots . '</td></tr>';
    echo '</table><br>';
}



// === DEBUG BOT DETAILS for specific date ===
$debug_date = '2025-09-07'; // dia que você quer detalhar
$bot_details = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT date, ip, ua, referer, url, response, access, human 
         FROM {$wpdb->prefix}sbb_visitorslog 
         WHERE DATE(date) = %s AND bot = '1'",
        $debug_date
    )
);

if ($bot_details) {
    echo '<h3>Detalhes de Bot Visits em ' . esc_html($debug_date) . '</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr>
            <th>Data/Hora</th>
            <th>IP</th>
            <th>User-Agent</th>
            <th>Referer</th>
            <th>URL</th>
            <th>Response</th>
            <th>Access</th>
            <th>Human</th>
          </tr>';

    foreach ($bot_details as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->date) . '</td>';
        echo '<td>' . esc_html($row->ip) . '</td>';
        echo '<td>' . esc_html($row->ua) . '</td>';
        echo '<td>' . esc_html($row->referer) . '</td>';
        echo '<td>' . esc_html($row->url) . '</td>';
        echo '<td>' . esc_html($row->response) . '</td>';
        echo '<td>' . esc_html($row->access) . '</td>';
        echo '<td>' . esc_html($row->human) . '</td>';
        echo '</tr>';
    }

    echo '</table><br>';
} else {
    echo '<p><em>Nenhum detalhe encontrado para ' . esc_html($debug_date) . '.</em></p>';
}
*/



?>
<!-- Checkboxes for showing different types of visits 
<label>
    <input type="checkbox" name="show_total_visits" id="showTotalVisits-M" checked> Total Visits
</label>
<label>
    <input type="checkbox" name="show_human_visits" id="showHumanVisits-M" checked> Human Visits
</label>
<label>
    <input type="checkbox" name="show_bot_visits" id="showBotVisits-M" checked> Bot Visits
</label>
-->


<canvas id="visitors-chart-month" style="width:600px;max-height:300px;"></canvas>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('visitors-chart-month').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo wp_json_encode(array_column($data, 'visit_date')); ?>,
                datasets: [{
                        label: 'Total Visits',
                        data: <?php echo wp_json_encode(array_column($data, 'total_visits')); ?>,
                        fill: {
                            target: 'origin',
                            above: 'rgba(255, 99, 132, 0.2)', // Cor de preenchimento mais clara acima da linha
                        },
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1,
                        hidden: false // Mostrar por padrão
                    },
                    {
                        label: 'Human Visits',
                        data: <?php echo wp_json_encode(array_column($data, 'human_visits')); ?>,
                        fill: {
                            target: 'origin',
                            above: 'rgba(75, 192, 192, 0.2)', // Cor de preenchimento mais clara acima da linha
                        },
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        hidden: false // Mostrar por padrão
                    },
                    {
                        label: 'Bot Visits',
                        data: <?php echo wp_json_encode(array_column($data, 'bot_visits')); ?>,
                        fill: {
                            target: 'origin',
                            above: 'rgba(0, 0, 0, 0.2)', // Cor de preenchimento mais clara acima da linha
                        },
                        borderColor: 'rgb(0, 0, 0)',
                        tension: 0.1,
                        hidden: false // Mostrar por padrão
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        // Add event listeners to the checkboxes
        document.getElementById('showTotalVisits-M').addEventListener('change', function() {
            myChart.data.datasets[0].hidden = !this.checked;
            myChart.update();
        });
        document.getElementById('showHumanVisits-M').addEventListener('change', function() {
            myChart.data.datasets[1].hidden = !this.checked;
            myChart.update();
        });
        document.getElementById('showBotVisits-M').addEventListener('change', function() {
            myChart.data.datasets[2].hidden = !this.checked;
            myChart.update();
        });
    });
</script>
<?php
