<?php
/**
 * @author William Sergio Minossi
 * @copyright 2012-30-07
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/*
CREATE TABLE `wp_sbb_stats` (
  `id` mediumint(9) NOT NULL,
  `date` varchar(4) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `qnick` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `qip` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `qtotal` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL
);
*/

global $wpdb;
$table_name = $wpdb->prefix . "sbb_stats";

// busca dados (sem %i no prepare)
$results9 = $wpdb->get_results("SELECT date, qtotal FROM `$table_name`");

// converte em array simples, garantindo zeros à esquerda
$results8 = [];
foreach ($results9 as $row) {
    $results8[] = [
        'date'   => str_pad($row->date, 4, '0', STR_PAD_LEFT),
        'qtotal' => (int)$row->qtotal,
    ];
}
unset($results9);

// últimos 15 dias
$d = 15;
$array30d = [];
$array30  = [];

for ($x = 0; $x < $d; $x++) {
    $tm = strtotime("-$x days");
    $md = date("md", $tm); // formato MMDD

    $array30d[$x] = $md;
    $key = array_search($md, array_column($results8, 'date'));

    if ($key !== false) {
        $array30[$x] = $results8[$key]['qtotal'];
    } else {
        $array30[$x] = 0;
    }
}

// coloca em ordem cronológica
$array30  = array_reverse($array30);
$array30d = array_reverse($array30d);

/*
// debug
echo '<pre>';
print_r($array30d);
print_r($array30);
echo '</pre>';
*/
?>
