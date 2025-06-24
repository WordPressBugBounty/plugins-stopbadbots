<?php

/**
 * StopBadBots - AJAX Installer File
 *
 * This file handles the initial setup process for the StopBadBots plugin,
 * guiding the user through a multi-step AJAX-powered installation wizard.
 * Refactored for simplicity and maintainability.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

//?debug_reset_installer=true
if (function_exists('ini_set')) {
    @ini_set('memory_limit', '256M');
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
    @ini_set('max_execution_time', 300);
}
error_reporting(0);
if (defined('WP_DEBUG') && WP_DEBUG && !defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}
remove_all_actions('admin_notices');
remove_all_actions('all_admin_notices');
remove_all_actions('network_admin_notices');
add_filter('wp_get_admin_notice_messages', '__return_empty_array', PHP_INT_MAX);

/**
 * Returns the default configuration array for the plugin.
 * Centralizing this makes it easy to manage default settings.
 *
 * @return array
 */
function stopbadbots_inst_get_default_config()
{
    return [
        // --- Ativação de Módulos e Funções Principais ---
        'stop_bad_bots_active'               => 'yes',
        'stop_bad_bots_blank_ua'             => 'yes',
        'stop_bad_bots_ip_active'            => 'yes',
        'stop_bad_bots_network'              => 'yes',
        'stop_bad_bots_referer_active'       => 'yes',

        // --- Configurações de Bloqueio Específicas ---
        'stopbadbots_block_china'            => 'no',
        'stopbadbots_block_enumeration'      => 'yes',
        'stopbadbots_block_false_google'     => 'no',
        'stopbadbots_block_pingbackrequest'  => 'yes',
        'stopbadbots_block_spam_comments'    => 'no',
        'stopbadbots_block_spam_contacts'    => 'no',
        'stopbadbots_block_spam_login'       => 'no',

        // --- Listas de Controle (Whitelist) e Ferramentas HTTP ---
        'stopbadbots_http_tools'             => '4D_HTTP_Client android-async-http axios andyhttp Aplix akka-http attohttpc curl CakePHP Cowblog DAP/NetHTTP Dispatch fasthttp FireEyeHttpScan Go-http-client Go1.1packagehttp Go 1.1 package http Go http package Go-http-client Gree_HTTP_Loader grequests GuzzleHttp hyp_http_request HTTPConnect http generic Httparty HTTPing http-ping http.rb/ HTTPREAD Java-http-client Jodd HTTP raynette_httprequest java/ kurl Laminas_Http_Client libsoup lua-resty-http mozillacompatible nghttp2 mio_httpc Miro-HttpClient php/ phpscraper PHX HTTP PHX HTTP Client python-requests Python-urllib python-httpx restful rpm-bot RxnetHttp scalaj-http SP-Http-Client Stilo OMHTTP tiehttp Valve/Steam Wget WP-URLDetails Zend_Http_Client ZendHttpClient ',
        'stopbadbots_string_whitelist'       => 'AOL Baidu Bingbot msn DuckDuck facebook GTmetrix google Lighthouse msn paypal Stripe SiteUptime Teoma Yahoo slurp seznam Twitterbot webgazer Yandex ',
        'stopbadbots_update_http_tools'      => 'no',
        'stopbadbots_ip_whitelist'           => '', // Default is empty, user's IP will be added.

        // --- Configurações de Relatórios e Debug ---
        'stopbadbots_Blocked_Firewall'       => 'no',
        'stopbadbots_checkversion'           => '',
        'stopbadbots_my_email_to'            => '',
        'stopbadbots_my_radio_report_all_visits' => 'no',
        'stopbadbots_keep_log'               => '7',

        // --- Outras Configurações Internas ---
        'stopbadbots_engine_option'          => 'conservative',
        'stopbadbots_firewall'               => 'no',
    ];
}

/**
 * Registers the hidden installer admin page.
 */
function stopbadbots_inst_add_admin_page()
{
    if (get_option('stopbadbots_setup_complete', false)) {
        return;
    }
    add_submenu_page(
        'tools.php',
        'Stopbadbots Installer',
        'Stopbadbots Installer',
        'manage_options',
        'stopbadbots-installer',
        'stopbadbots_inst_render_installer'
    );
}
add_action('admin_menu', 'stopbadbots_inst_add_admin_page');

/**
 * Enqueues CSS and JS for the installer page.
 */
function stopbadbots_inst_enqueue_scripts($hook)
{
    if ($hook !== 'tools_page_stopbadbots-installer') {
        return;
    }
    wp_enqueue_style('stopbadbots-inst-styles', STOPBADBOTSURL . 'includes/install/install.css', ['dashicons'], STOPBADBOTSVERSION);
    wp_enqueue_script('stopbadbots-inst-script', STOPBADBOTSURL . 'includes/install/install.js', ['jquery'], STOPBADBOTSVERSION, true);

    wp_localize_script(
        'stopbadbots-inst-script',
        'stopbadbots_installer_ajax',
        [
            'ajax_url'     => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('stopbadbots-installer-ajax-nonce'),
            'initial_step' => isset($_GET['step']) ? intval($_GET['step']) : 1,
        ]
    );
}
add_action('admin_enqueue_scripts', 'stopbadbots_inst_enqueue_scripts');

/**
 * The main AJAX handler for the installer.
 */
function stopbadbots_ajax_installer_handler()
{
    // 1. Security checks
    check_ajax_referer('stopbadbots-installer-ajax-nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }

    // 2. Sanitize incoming data
    $step_to_load = isset($_POST['step_to_load']) ? intval($_POST['step_to_load']) : 1;
    $direction    = isset($_POST['direction']) ? sanitize_key($_POST['direction']) : 'next';

    // 3. Process data if moving forward
    if ($direction === 'next') {
        $step_to_process = $step_to_load - 1;

        switch ($step_to_process) {
            case 2:
                // Save experience level from Step 2
                $experience_level = isset($_POST['stopbadbots_inst_experience_level']) ? sanitize_key($_POST['stopbadbots_inst_experience_level']) : 'one-click';
                update_option('stopbadbots_inst_experience_level', $experience_level);
                break;

            case 3:
                // === PROCESS STEP 3: SAVE ALL CONFIGURATIONS ===

                // 1. Get the master array of default settings
                $default_config = stopbadbots_inst_get_default_config();

                // 2. Get the user's choices from the form and sanitize them
                $user_choices = [
                    'stopbadbots_my_email_to'            => isset($_POST['stopbadbots_my_email_to']) ? sanitize_email($_POST['stopbadbots_my_email_to']) : '',
                    'stop_bad_bots_blank_ua'             => isset($_POST['stop_bad_bots_blank_ua']) && in_array($_POST['stop_bad_bots_blank_ua'], ['yes', 'no']) ? $_POST['stop_bad_bots_blank_ua'] : 'yes',
                    'stopbadbots_my_radio_report_all_visits' => isset($_POST['stopbadbots_my_radio_report_all_visits']) && in_array($_POST['stopbadbots_my_radio_report_all_visits'], ['yes', 'no']) ? $_POST['stopbadbots_my_radio_report_all_visits'] : 'no',
                    'stopbadbots_keep_log'               => isset($_POST['stopbadbots_keep_log']) && in_array($_POST['stopbadbots_keep_log'], ['1', '3', '7', '14', '21', '30', '90', '180', '360']) ? $_POST['stopbadbots_keep_log'] : '7',
                ];

                // 3. Merge defaults with user choices. User choices will overwrite defaults.
                $final_config = array_merge($default_config, $user_choices);

                // 4. Add the current user's IP to the whitelist
                $current_ip = stopbadbots_get_installer_ip();
                if ($current_ip) {
                    $final_config['stopbadbots_ip_whitelist'] = trim($current_ip);
                }

                // 5. Save all options to the database in a single loop
                foreach ($final_config as $key => $value) {
                    update_option($key, $value);
                }
                break;

            case 4:
                // === FINALIZE INSTALLATION ===
                update_option('stopbadbots_setup_complete', true);

                // Redirect to the main plugin page after completion
                $redirect_url = admin_url('admin.php?page=stop_bad_bots_plugin');
                wp_send_json_success(['redirect' => esc_url_raw($redirect_url)]);
                break;
        }
    }

    // 4. Render and return the HTML for the requested step
    ob_start();
    stopbadbots_inst_render_step_html($step_to_load);
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_stopbadbots_installer_step', 'stopbadbots_ajax_installer_handler');

/**
 * Renders the main installer shell.
 */
function stopbadbots_inst_render_installer()
{
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
?>
    <div class="stopbadbots-inst-wrap">
        <header class="stopbadbots-inst-header">
            <img id="stopbadbots-inst-logo" alt="StopBadBots Logo" src="<?php echo esc_url(STOPBADBOTSURL . '/assets/images/logo.png'); ?>" width="250px" />
            <img id="stopbadbots-inst-step-indicator" alt="Step Indicator" src="<?php echo esc_url(STOPBADBOTSURL . '/assets/images/header-install-step-1.png'); ?>" />
        </header>
        <main id="stopbadbots-inst-content-container" class="stopbadbots-inst-content">
            <div class="stopbadbots-inst-loader">
                <span class="spinner is-active"></span>
                <p><?php esc_html_e('Loading', 'stopbadbots'); ?>...</p>
            </div>
        </main>
    </div>
    <?php
}

/**
 * Renders the HTML for a specific step.
 *
 * @param int $step The step number to render.
 */
function stopbadbots_inst_render_step_html($step = 1)
{
    if ($step < 1 || $step > 4) $step = 1;

    switch ($step):
        case 1:
    ?>
            <h1>1. <?php esc_html_e('Welcome', 'stopbadbots'); ?></h1>
            <p><?php esc_html_e('Thank you for choosing StopBadBots. This quick setup wizard will apply our recommended security settings to protect your site immediately.', 'stopbadbots'); ?></p>
            <p><?php esc_html_e('Please follow the steps to complete the installation. You can always change these settings later.', 'stopbadbots'); ?></p>
            <form id="stopbadbots-installer-form" data-step="1">
                <div class="stopbadbots-inst-buttons">
                    <button type="submit" class="stopbadbots-inst-button stopbadbots-inst-next"><?php esc_html_e('Start Setup', 'stopbadbots'); ?></button>
                </div>
            </form>
        <?php
            break;

        case 2:
            $experience_level = get_option('stopbadbots_inst_experience_level', 'one-click');
        ?>
            <h1>2. <?php esc_html_e('Your Experience Level', 'stopbadbots'); ?></h1>
            <p><?php esc_html_e('This choice helps us understand our users. The default "One-Click Setup" will apply all recommended settings for you.', 'stopbadbots'); ?></p>
            <form id="stopbadbots-installer-form" data-step="2">
                <label>
                    <input type="radio" name="stopbadbots_inst_experience_level" value="one-click" <?php checked($experience_level, 'one-click'); ?> />
                    <?php esc_html_e("One-Click Setup (Recommended)", 'stopbadbots'); ?>
                </label>
                <p class="stopbadbots-inst-description"><?php esc_html_e("We'll automatically apply the best-practice settings for you.", 'stopbadbots'); ?></p>

                <label>
                    <input type="radio" name="stopbadbots_inst_experience_level" value="manual" <?php checked($experience_level, 'manual'); ?> />
                    <?php esc_html_e("Manual Setup (Advanced)", 'stopbadbots'); ?>
                </label>
                <p class="stopbadbots-inst-description"><?php esc_html_e("You plan to configure all settings manually after installation.", 'stopbadbots'); ?></p>

                <div class="stopbadbots-inst-buttons">
                    <button type="button" class="stopbadbots-inst-button stopbadbots-inst-back" data-step="1"><?php esc_html_e('Back', 'stopbadbots'); ?></button>
                    <button type="submit" class="stopbadbots-inst-button stopbadbots-inst-next"><?php esc_html_e('Next', 'stopbadbots'); ?></button>
                </div>
            </form>
        <?php
            break;

        case 3:
            // Get current saved values to pre-fill the form
            $my_email_to      = get_option('stopbadbots_my_email_to', get_option('admin_email'));
            $blank_ua         = get_option('stop_bad_bots_blank_ua', 'yes');
            $report_visits    = get_option('stopbadbots_my_radio_report_all_visits', 'no');
            $keep_log         = get_option('stopbadbots_keep_log', '7');
        ?>
            <h1>3. <?php esc_html_e('Basic Settings', 'stopbadbots'); ?></h1>
            <p><?php esc_html_e('Please confirm these basic settings. All other security rules will be applied automatically for you.', 'stopbadbots'); ?></p>

            <form id="stopbadbots-installer-form" data-step="3">
                <div class="stopbadbots-inst-field">
                    <label for="stopbadbots_my_email_to"><?php esc_html_e('Email for security notifications', 'stopbadbots'); ?></label>
                    <input type="email" id="stopbadbots_my_email_to" name="stopbadbots_my_email_to" value="<?php echo esc_attr($my_email_to); ?>" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>" />
                    <p class="stopbadbots-inst-description"><?php esc_html_e('Leave blank to use your default WordPress admin email.', 'stopbadbots'); ?></p>
                </div>

                <div class="stopbadbots-inst-field">
                    <h3><?php esc_html_e('Block visitors with a blank User-Agent?', 'stopbadbots'); ?></h3>
                    <p class="stopbadbots-inst-description"><?php esc_html_e('This is a common characteristic of poorly written bots and scrapers. Recommended: Yes.', 'stopbadbots'); ?></p>
                    <label><input type="radio" name="stop_bad_bots_blank_ua" value="yes" <?php checked($blank_ua, 'yes'); ?> /> <?php esc_html_e('Yes', 'stopbadbots'); ?></label>
                    <label><input type="radio" name="stop_bad_bots_blank_ua" value="no" <?php checked($blank_ua, 'no'); ?> /> <?php esc_html_e('No', 'stopbadbots'); ?></label>
                </div>

                <div class="stopbadbots-inst-field">
                    <h3><?php esc_html_e('Receive email alerts for each blocked bot?', 'stopbadbots'); ?></h3>
                    <p class="stopbadbots-inst-description"><?php esc_html_e('This can be noisy if your site is under attack. You can view all blocks in the logs. Recommended: No.', 'stopbadbots'); ?></p>
                    <label><input type="radio" name="stopbadbots_my_radio_report_all_visits" value="yes" <?php checked($report_visits, 'yes'); ?> /> <?php esc_html_e('Yes', 'stopbadbots'); ?></label>
                    <label><input type="radio" name="stopbadbots_my_radio_report_all_visits" value="no" <?php checked($report_visits, 'no'); ?> /> <?php esc_html_e('No', 'stopbadbots'); ?></label>
                </div>

                <div class="stopbadbots-inst-field">
                    <h3><?php esc_html_e('How long to keep visitor logs?', 'stopbadbots'); ?></h3>
                    <p class="stopbadbots-inst-description"><?php esc_html_e('Storing data for shorter durations conserves database space; typically, seven days is adequate. Nevertheless, please note that this will directly affect your visit and analytics reports.', 'stopbadbots'); ?></p>
                    <select id="stopbadbots_keep_log" name="stopbadbots_keep_log">
                        <option value="1" <?php selected($keep_log, '1'); ?>><?php esc_html_e('1 day', 'stopbadbots'); ?></option>
                        <option value="3" <?php selected($keep_log, '3'); ?>><?php esc_html_e('3 days', 'stopbadbots'); ?></option>
                        <option value="7" <?php selected($keep_log, '7'); ?>><?php esc_html_e('7 days', 'stopbadbots'); ?></option>
                        <option value="14" <?php selected($keep_log, '14'); ?>><?php esc_html_e('14 days', 'stopbadbots'); ?></option>
                        <option value="30" <?php selected($keep_log, '30'); ?>><?php esc_html_e('30 days', 'stopbadbots'); ?></option>
                        <option value="90" <?php selected($keep_log, '90'); ?>><?php esc_html_e('90 days', 'stopbadbots'); ?></option>
                        <option value="180" <?php selected($keep_log, '180'); ?>><?php esc_html_e('180 days', 'stopbadbots'); ?></option>
                        <option value="360" <?php selected($keep_log, '360'); ?>><?php esc_html_e('360 days', 'stopbadbots'); ?></option>
                    </select>
                </div>


                <div class="stopbadbots-inst-buttons">
                    <button type="button" class="stopbadbots-inst-button stopbadbots-inst-back" data-step="2"><?php esc_html_e('Back', 'stopbadbots'); ?></button>
                    <button type="submit" class="stopbadbots-inst-button stopbadbots-inst-next"><?php esc_html_e('Next', 'stopbadbots'); ?></button>
                </div>
            </form>
        <?php
            break;

        case 4:
        ?>
            <form id="stopbadbots-installer-form" data-step="4">
                <h1>4. <?php esc_html_e('All Done!', 'stopbadbots'); ?></h1>
                <p><?php esc_html_e('StopBadBots has been successfully configured with our recommended settings! Your site is now protected.', 'stopbadbots'); ?></p>
                <div class="stopbadbots-inst-buttons">
                    <button type="button" class="stopbadbots-inst-button stopbadbots-inst-back" data-step="3"><?php esc_html_e('Back', 'stopbadbots'); ?></button>
                    <button type="submit" class="stopbadbots-inst-button stopbadbots-inst-next"><?php esc_html_e('Go to Dashboard', 'stopbadbots'); ?></button>
                </div>
            </form>

<?php
            break;
    endswitch;
}

/**
 * Safely gets the current user's IP address.
 *
 * @return string The sanitized IP address, or an empty string if invalid.
 */
function stopbadbots_get_installer_ip()
{
    $raw_ip = '';
    // Use a robust IP detection method if available, otherwise fall back to REMOTE_ADDR
    if (function_exists('sbb_findip')) { // Assuming sbb_findip is your function
        $raw_ip = sbb_findip();
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $raw_ip = $_SERVER['REMOTE_ADDR'];
    }
    // Sanitize and validate the IP address
    return filter_var(trim($raw_ip), FILTER_VALIDATE_IP) ?: '';
}
