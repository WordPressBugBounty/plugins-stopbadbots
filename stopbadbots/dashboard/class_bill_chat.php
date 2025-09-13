<?php

namespace stopbadbots_BillChat_Dashboard;

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

if (function_exists('is_multisite') && is_multisite()) {
    return;
}

class ChatPluginDashboard
{
    public function __construct()
    {
        // Hooks para AJAX
        add_action('wp_ajax_stopbadbots_dashboard_send_message', [$this, 'stopbadbots_dashboard_send_message']);
        add_action('wp_ajax_stopbadbots_dashboard_reset_messages', [$this, 'stopbadbots_dashboard_reset_messages']);
        add_action('wp_ajax_stopbadbots_dashboard_load_messages', [$this, 'stopbadbots_dashboard_load_messages']);
        // Registrar os scripts
        add_action('admin_init', [$this, 'stopbadbots_dashboard_plugin_scripts']);
        add_action('admin_init', [$this, 'stopbadbots_dashboard_enqueue_chat_scripts']);
    }

    public function stopbadbots_dashboard_plugin_scripts()
    {
        wp_enqueue_style(
            'stopbadbots-dashboard-chat-style',
            plugin_dir_url(__FILE__) . 'chat.css'
        );
    }

    public function stopbadbots_dashboard_enqueue_chat_scripts()
    {
        // AQUI: Usamos a data de modificação do arquivo como versão.
        // Isso garante que o navegador sempre carregue a versão mais recente após uma alteração no arquivo.
        $version = filemtime(plugin_dir_path(__FILE__) . 'chat.js');
        wp_enqueue_script(
            'stopbadbots-dashboard-chat-script',
            plugin_dir_url(__FILE__) . 'chat.js',
            array('jquery'),
            $version,
            true
        );
        wp_localize_script('stopbadbots-dashboard-chat-script', 'stopbadbots_bill_data', array(

            'ajax_url'                 => admin_url('admin-ajax.php'),
            'reset_nonce'              => wp_create_nonce('stopbadbots_dashboard_reset_messages'),
            'send_nonce'               => wp_create_nonce('stopbadbots_dashboard_send_message'),
            'reset_success'            => esc_attr__('Chat messages reset successfully.', 'stopbadbots'),
            'reset_error'              => esc_attr__('Error resetting chat messages.', 'stopbadbots'),
            'invalid_message'          => esc_attr__('Invalid message received:', 'stopbadbots'),
            'invalid_response_format'  => esc_attr__('Invalid response format:', 'stopbadbots'),
            'response_processing_error' => esc_attr__('Error processing server response:', 'stopbadbots'),
            'not_json'                 => esc_attr__('Response is not valid JSON.', 'stopbadbots'),
            'ajax_error'               => esc_attr__('AJAX request failed:', 'stopbadbots'),
            'send_error'               => esc_attr__('Error sending the message. Please try again later.', 'stopbadbots'),
            'empty_message_error'      => esc_attr__('Please enter a message!', 'stopbadbots'),
        ));
    }


    /**
     * Função para carregar as mensagens do chat.
     */
    public function stopbadbots_dashboard_load_messages()
    {
        if (ob_get_length()) {
            ob_clean();
        }
        $messages = get_option('stopbadbots_chat_messages', []);
        $last_count = isset($_POST['last_count']) ? intval($_POST['last_count']) : 0;
        // Verifica se há novas mensagens
        $new_messages = [];
        if (count($messages) > $last_count) {
            $new_messages = array_slice($messages, $last_count);
        }
        // Retorna as mensagens no formato JSON
        wp_send_json([
            'message_count' => count($messages),
            'messages' => array_map(function ($message) {
                return [
                    'text' => esc_html($message['text']),
                    'sender' => esc_html($message['sender'])
                ];
            }, $new_messages)
        ]);
        wp_die();
    }
    public function bill_read_file($file, $lines)
    {
        clearstatcache(true, $file);
        if (!file_exists($file) || !is_readable($file)) {
            return [];
        }
        $text = [];
        $handle = fopen($file, "r");
        if (!$handle) {
            return [];
        }
        $bufferSize = 8192;
        $currentChunk = '';
        $linecounter = 0;
        fseek($handle, 0, SEEK_END);
        $filesize = ftell($handle);
        if ($filesize < $bufferSize) {
            $bufferSize = $filesize;
        }
        if ($bufferSize < 1) {
            fclose($handle);
            return [];
        }
        $pos = $filesize - $bufferSize;
        while ($pos >= 0 && $linecounter < $lines) {
            if ($pos < 0) {
                $pos = 0;
            }
            fseek($handle, $pos);
            $chunk = fread($handle, $bufferSize);
            if ($chunk === false && file_exists($file)) {
                usleep(500000);
                $chunk = fread($handle, $bufferSize);
            }
            $currentChunk = $chunk . $currentChunk;
            $linesInChunk = explode("\n", $currentChunk);
            $currentChunk = array_shift($linesInChunk);
            foreach (array_reverse($linesInChunk) as $line) {
                $text[] = $line;
                $linecounter++;
                if ($linecounter >= $lines) {
                    break 2;
                }
            }
            $pos -= $bufferSize;
        }
        if (!empty($currentChunk)) {
            $text[] = $currentChunk;
        }
        fclose($handle);
        return $text;
    }
    /**
     * Função para chamar a API do ChatGPT.
     */
    public function bill_chat_call_chatgpt_api($data, $chatType, $chatVersion)
    {
        $bill_chat_erros = '';
        try {
            function filter_log_content($content)
            {
                if (is_array($content)) {
                    $filteredArray = array_filter($content);
                    return empty($filteredArray) ? '' : $content;
                } elseif (is_object($content)) {
                    return '';
                } else {
                    return $content;
                }
            }
            $bill_folders = \stopbadbots_BillChat_Dashboard\ChatPluginDashboard::get_path_logs();

            $log_type = "PHP Error Log";
            $bill_chat_erros = "Log ($log_type) not found or not readable.";
            foreach ($bill_folders as $bill_folder) {
                if (!file_exists($bill_folder) && !is_readable($bill_folder)) {
                    continue;
                }
                $returned_bill_chat_erros = $this->bill_read_file($bill_folder, 40);
                $returned_bill_chat_erros = filter_log_content($returned_bill_chat_erros);
                if (!empty($returned_bill_chat_erros)) {
                    $bill_chat_erros = $returned_bill_chat_erros;
                    break;
                }
            }
        } catch (Exception $e) {
            $bill_chat_erros = "An error occurred to read error logs: " . $e->getMessage();
        }
        $plugin_path = plugin_basename(__FILE__);
        $language = get_locale();
        $plugin_slug = explode('/', $plugin_path)[0];
        $domain = parse_url(home_url(), PHP_URL_HOST);
        if (empty($bill_chat_erros)) {
            $bill_chat_erros = 'No errors found!';
        }
        $stopbadbots_checkup = \stopbadbots_sysinfo_get();
        $data2 = [
            'param1' => $data,
            'param2' => $stopbadbots_checkup,
            'param3' => $bill_chat_erros,
            'param4' => $language,
            'param5' => $plugin_slug,
            'param6' => $domain,
            'param7' => $chatType,
            'param8' => $chatVersion,
        ];

        $response = wp_remote_post('https://BillMinozzi.com/chat/api/api.php', [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data2),
        ]);

        if (is_wp_error($response)) {
            $error_message = sanitize_text_field($response->get_error_message());
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
        }

        if (isset($data['success']) && $data['success'] === true) {
            $message = $data['message'];
        } else {
            $message = esc_attr__("Error contacting the Artificial Intelligence (API). Please try again later.", 'stopbadbots');
        }
        return $message;
    }
    /**
     * Função para enviar a mensagem do usuário e obter a resposta do ChatGPT.
     */
    public static function get_path_logs()
    {
        $bill_folders = [];
        $error_log_path = ini_get("error_log");
        if (!empty($error_log_path)) {
            $error_log_path = trim($error_log_path);
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $error_log_path = trailingslashit(WP_CONTENT_DIR) . 'debug.log';
            } else {
                $error_log_path = trailingslashit(ABSPATH) . 'error_log';
            }
        }

        $bill_folders[] = $error_log_path;
        $bill_folders[] =  realpath(ABSPATH . "error_log");
        $bill_folders[] = WP_CONTENT_DIR . "/debug.log";
        $bill_folders[] = plugin_dir_path(__FILE__) . "error_log";
        $bill_folders[] = plugin_dir_path(__FILE__) . "php_errorlog";
        $bill_folders[] = get_theme_root() . "/error_log";
        $bill_folders[] = get_theme_root() . "/php_errorlog";
        $bill_admin_path = str_replace(get_bloginfo("url") . "/", ABSPATH, get_admin_url());
        $bill_folders[] = $bill_admin_path . "/error_log";
        $bill_folders[] = $bill_admin_path . "/php_errorlog";
        try {
            $bill_plugins = array_slice(scandir(plugin_dir_path(__FILE__)), 2);
            foreach ($bill_plugins as $bill_plugin) {
                $plugin_path = plugin_dir_path(__FILE__) . $bill_plugin;
                if (is_dir($plugin_path)) {
                    $bill_folders[] = $plugin_path . "/error_log";
                    $bill_folders[] = $plugin_path . "/php_errorlog";
                }
            }
        } catch (Exception $e) {
            error_log("Error scanning plugins directory: " . $e->getMessage());
        }
        try {
            $bill_themes = array_slice(scandir(get_theme_root()), 2);
            foreach ($bill_themes as $bill_theme) {
                if (is_dir(get_theme_root() . "/" . $bill_theme)) {
                    $bill_folders[] = get_theme_root() . "/" . $bill_theme . "/error_log";
                    $bill_folders[] = get_theme_root() . "/" . $bill_theme . "/php_errorlog";
                }
            }
        } catch (Exception $e) {
            error_log("Error scanning theme directory: " . $e->getMessage());
        }
        return $bill_folders;
    }

    public function stopbadbots_dashboard_send_message()
    {
        // Verifique o nonce de segurança
        check_ajax_referer('stopbadbots_dashboard_send_message', 'security');

        // Captura e sanitiza a mensagem
        $message = sanitize_text_field($_POST['message']);

        // Verifica e sanitiza o chat_type, atribuindo 'default' caso não exista
        $chatType = isset($_POST['chat_type']) ? sanitize_text_field($_POST['chat_type']) : 'default';

        // Usando um array para remover múltiplos prefixos de forma segura e eficiente
        $prefixes_to_remove = ['stopbadbots-', 'dashboard-'];
        $chatType = str_replace($prefixes_to_remove, '', $chatType);

        if (empty($message)) {
            if ($chatType == 'auto-checkup') {
                $message = esc_attr("Auto Checkup for Erros button clicked...", 'stopbadbots');
            } elseif ($chatType == 'auto-checkup2') {
                $message = esc_attr("Auto Checkup Server button clicked...", 'stopbadbots');
            }
        }

        $chatVersion = isset($_POST['chat_version']) ? sanitize_text_field($_POST['chat_version']) : '1.00';

        $response_data = $this->bill_chat_call_chatgpt_api($message, $chatType, $chatVersion);

        if (!empty($response_data)) {
            $output = $response_data;
            $resposta_formatada = $output;
        } else {
            $output = esc_attr__("Error to get response from AI source!", 'stopbadbots');
        }

        $messages = get_option('stopbadbots_chat_messages', []);

        if (!is_array($messages)) {
            $messages = [];
        }

        $messages[] = [
            'text' => $message,
            'sender' => 'user'
        ];

        $messages[] = [
            'text' => $resposta_formatada,
            'sender' => 'chatgpt'
        ];

        update_option('stopbadbots_chat_messages', $messages);

        wp_die();
    }
    /**
     * Função para resetar as mensagens.
     */

    public function stopbadbots_dashboard_reset_messages()
    {
        // 1. Verificar o Nonce para proteção contra CSRF
        check_ajax_referer('stopbadbots_dashboard_reset_messages', 'security');

        // 2. Verificar se o utilizador tem permissão
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to perform this action.');
            wp_die();
        }

        // Apagar as mensagens
        update_option('stopbadbots_chat_messages', []);

        // Enviar uma resposta de sucesso
        wp_send_json_success('Chat messages have been reset.');

        wp_die();
    }
}
new ChatPluginDashboard();
