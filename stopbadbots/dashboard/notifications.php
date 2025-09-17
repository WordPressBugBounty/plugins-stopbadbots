<?php

/**
 * @author    William Sergio Minozzi
 * @copyright 2021
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly 
}


add_action('admin_head', 'meu_plugin_admin_bar_status_css');

function meu_plugin_admin_bar_status_css()
{
    echo '
    <style>
        .protection-status-container {
            width: 250px;
            height: 20px;
            background-color: #eee;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #ccc;
        }
        .protection-status-bar {
            height: 100%;
            transition: width 0.4s ease-in-out;
        }
        .protection-status-label {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 14px;
            line-height: 20px;
            font-weight: bold;
            color: #333;
            text-shadow: 0 0 2px #fff;
        }
    </style>';
}


global $stopbadbots_active;
global $stopbadbots_ip_active;
global $stopbadbots_referer_active;
global $stopbadbots_Report_Blocked_Firewall;
global $stopbadbots_notif_level;
global $wpdb;

// Usa o nível de proteção real do seu plugin
$stopbadbots_prot_perc = stopbadbots_find_perc();

if (isset($_GET['notif'])) {
    $notif = sanitize_text_field($_GET['notif']);
    if ($notif == 'level') {
        update_option('stopbadbots_notif_level', time());
        $stopbadbots_notif_level = time();
    }
}
$timeout_level = time() > ($stopbadbots_notif_level + 60 * 60 * 24 * 7);
$site = STOPBADBOTSHOMEURL . "admin.php?page=stop_bad_bots_plugin&tab=notifications&notif=";

// Lógica para mudar a cor da barra com base no nível
$bar_color = '#d63638'; // Vermelho para níveis baixos
if ($stopbadbots_prot_perc > 85) {
    $bar_color = '#4CAF50'; // Verde para níveis altos
} else if ($stopbadbots_prot_perc > 60) {
    $bar_color = '#ffb821'; // Amarelo para níveis médios
}
?>

<div id="stopbadbots-notifications-page">
    <div class="stopbadbots-block-title">
        <?php esc_attr_e("Notifications", "stopbadbots"); ?>
    </div>

    <div style="margin-top: 15px;">
        <p><?php esc_attr_e("Protection Status level:", "stopbadbots"); ?></p>
        <div class="protection-status-container" style="position: relative;">
            <div class="protection-status-bar" style="width: <?php echo esc_attr($stopbadbots_prot_perc); ?>%; background-color: <?php echo esc_attr($bar_color); ?>;"></div>
            <span class="protection-status-label"><?php echo esc_attr($stopbadbots_prot_perc); ?>%</span>
        </div>
    </div>

    <div id="notifications-tab">
        <br>
        <?php
        $empty_notif = true;
        if ($stopbadbots_active != 'yes') {
            $empty_notif = false; ?>
            <b><?php esc_attr_e("Plugin Stop Bad Bots It is not active!", "stopbadbots"); ?></b>
            <br>
            <?php esc_attr_e("Go to Dashboard => Stop Bad Bots => Settings => General Settings (tab) and activate it. ", "stopbadbots"); ?>
            <br>
            <?php esc_attr_e('Mark: "Block all Bots included at Bad Bots Table?" with yes.', "stopbadbots"); ?>
            <br>
            <hr>
        <?php
        }
        if ($stopbadbots_ip_active != 'yes') {
            $empty_notif = false; ?>
            <b> <?php esc_attr_e("Plugin Stop Bad Bots (Block Ips) It is not active!", "stopbadbots"); ?></b>
            <br>
            <?php esc_attr_e("Go to Dashboard => Stop Bad Bots => Settings => General Settings (tab) and activate it.", "stopbadbots"); ?>
            <br>
            <?php esc_attr_e('Mark: "Block all IPs included at Bad IPs Table?" with yes.', "stopbadbots"); ?>
            <hr>
        <?php
        }
        if ($stopbadbots_referer_active != 'yes') {
            $empty_notif = false; ?>
            <b> <?php esc_attr_e("Plugin Stop Bad Bots (Block Bad Refer Table) It is not active!", "stopbadbots"); ?></b>
            <br>
            <?php esc_attr_e("Go to Dashboard => Stop Bad Bots => Settings => General Settings (tab) and activate it.", "stopbadbots"); ?>
            <br>
            <?php esc_attr_e('Mark: "Block all bots included at Bad Referer Table?" with yes.', "stopbadbots"); ?>
            <hr>
        <?php
        }

        // CORRIGIDO: O texto "Improve your protection level" agora exibe a barra de progresso
        if ($timeout_level and $stopbadbots_prot_perc < 80) {
            $empty_notif = false;
        ?>
            <b> <?php esc_attr_e("Improve your protection level.", "stopbadbots"); ?> </b>
            <br>
            <?php esc_attr_e("To increase, go to", "stopbadbots"); ?>
            <br>
            <?php esc_attr_e("Stop Bad Bots => Setting => General Settings", "stopbadbots"); ?>
            <br>
            <?php esc_attr_e("and mark all with yes.", "stopbadbots"); ?>
            <br>
            <hr>
        <?php }
        if ($empty_notif) {
            echo  '<br>';
            echo '<b>' . esc_attr_e("No notifications at this time!", "stopbadbots") . '</b>';
        }
        ?>
    </div>
</div>