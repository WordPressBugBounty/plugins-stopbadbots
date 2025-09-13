<?php
if (!defined('ABSPATH')) {
    die('Invalid request.');
}

update_option('stopbadbots_chat_messages', []);

if (file_exists(plugin_dir_path(__FILE__) . 'chat.js')) {
} else {
    die(plugin_dir_url(__FILE__) . 'chat.js');
}
?>
<div class="stopbadbots-block-title">
    <?php echo esc_attr__("Chat 24 X 7", "stopbadbots"); ?>
</div>

<div style="font-size: 16px; padding: 10px 20px;">
    <?php
    echo esc_html__('The tool assists with plugin installation, server issues, and product setup. You can run quick scans using the "Auto Checkup" buttons. The database is being expanded to cover more questions, and you can ask in your own language.', "stopbadbots");
    ?>
</div>

<div id="stopbadbots-dashboard-chat-box" class="stopbadbots-dashboard-chat-support-version stopbadbots-dashboard-new-chat-support">
    <div id="stopbadbots-dashboard-chat-header">
        <h2><?php echo esc_attr__("Artificial Intelligence Support Chat for Issues and Solutions", "stopbadbots"); ?></h2>
    </div>
    <div id="stopbadbots-dashboard-gif-container">
        <div class="stopbadbots-dashboard-spinner999"></div>
    </div>

    <div id="stopbadbots-dashboard-chat-messages" style="border-bottom: 1px solid #cccccc; padding: 10px;"></div>

    <div id="stopbadbots-dashboard-error-message" style="display:none;"></div>

    <div id="stopbadbots-dashboard-assistance-type" style="margin: 10px 0; background: #f9f9f9;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <h3 style="margin: 0; white-space: nowrap;"><?php echo esc_attr__('Select assistance type:', 'stopbadbots'); ?></h3>
            <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="radio" name="assistance_type" value="error_diagnostic" checked style="margin-right: 5px;">
                    <?php echo esc_attr__('Error & Server Issues', 'stopbadbots'); ?>
                </label>
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="radio" name="assistance_type" value="plugin_usage" style="margin-right: 5px;">
                    <?php echo esc_attr__('Plugin Usage & Configuration', 'stopbadbots'); ?>
                </label>
            </div>
        </div>
    </div>

    <form id="stopbadbots-dashboard-chat-form">
        <div id="stopbadbots-dashboard-input-group">
            <input type="text" id="stopbadbots-dashboard-chat-input" placeholder="<?php echo esc_attr__('Describe your issue, or use the buttons below to check for errors or server settings...', "stopbadbots"); ?>" />
            <button type="submit"><?php echo esc_attr__('Send', "stopbadbots"); ?></button>
        </div>
        <div id="stopbadbots-dashboard-action-instruction" style="text-align: center; margin-top: 10px;">
            <span><big><?php echo esc_attr__("Enter a message and click 'Send', or just click 'Auto Checkup' to analyze error log or server info configuration.", "stopbadbots"); ?></big></span>
        </div>
        <div class="stopbadbots-dashboard-auto-checkup-container" style="text-align: center; margin-top: 10px;">
            <button type="button" id="stopbadbots-dashboard-auto-checkup" class="stopbadbots-dashboard-new-chat-button">
                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'robot2.png'); ?>" alt="" width="35" height="30">
                <?php echo esc_attr__('Auto Checkup for Errors', "stopbadbots"); ?>
            </button>
            &nbsp;&nbsp;&nbsp;
            <button type="button" id="stopbadbots-dashboard-auto-checkup2" class="stopbadbots-dashboard-new-chat-button">
                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'robot2.png'); ?>" alt="" width="35" height="30">
                <?php echo esc_attr__('Auto Checkup Server ', "stopbadbots"); ?>
            </button>
        </div>
    </form>
</div>