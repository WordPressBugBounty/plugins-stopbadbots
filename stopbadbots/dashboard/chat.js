jQuery(document).ready(function ($) {


    let chatVersion = "3.00";
    const billChatMessages = $('#stopbadbots-dashboard-chat-messages');
    const billChatForm = $('#stopbadbots-dashboard-chat-form');
    const billChatInput = $('#stopbadbots-dashboard-chat-input');
    const billChaterrorMessage = $('#stopbadbots-dashboard-error-message');
    let billChatLastMessageCount = 0;

    const autoCheckupButtons = $('#stopbadbots-dashboard-auto-checkup, #stopbadbots-dashboard-auto-checkup2');
    if (autoCheckupButtons.length === 0) {
    }

    let billChat_inactivityTimer;
    let billChat_userHasInteracted = false;

    function billChat_triggerPulseAnimation() {
        if (billChat_userHasInteracted) return;

        autoCheckupButtons.addClass('pulse-button');

        setTimeout(function () {
            autoCheckupButtons.removeClass('pulse-button');

            if (!billChat_userHasInteracted) {
                billChat_inactivityTimer = setTimeout(billChat_triggerPulseAnimation, 15000);
            }
        }, 6000);
    }

    function billChat_resetInactivityTimer() {
        if (billChat_userHasInteracted) return;

        clearTimeout(billChat_inactivityTimer);

        autoCheckupButtons.removeClass('pulse-button');

        billChat_inactivityTimer = setTimeout(billChat_triggerPulseAnimation, 8000);
    }

    function billChat_stopAnimationFeature() {
        billChat_userHasInteracted = true;
        clearTimeout(billChat_inactivityTimer);
        autoCheckupButtons.removeClass('pulse-button');
    }

    $(document).on('mousemove keypress click', billChat_resetInactivityTimer);

    billChat_resetInactivityTimer();

    function billChatEscapeHtml(text) {
        return $('<div>').text(text).html();
    }

    $.ajax({
        url: stopbadbots_bill_data.ajax_url,
        type: 'POST',
        data: {
            action: 'stopbadbots_dashboard_reset_messages',
            security: stopbadbots_bill_data.reset_nonce
        },
        success: function () { },
        error: function (xhr, status, error) { console.error(stopbadbots_bill_data.reset_error, error, xhr.responseText); }
    });

    function billChatLoadMessages() {
        $.ajax({
            url: bill_data.ajax_url,
            method: 'POST',
            data: { action: 'stopbadbots_dashboard_load_messages', last_count: billChatLastMessageCount },
            success: function (response, status, xhr) {
                try {
                    if (typeof response === 'string') { response = JSON.parse(response); }
                    if (Array.isArray(response.messages)) {
                        if (response.message_count > billChatLastMessageCount) {
                            billChatLastMessageCount = response.message_count;
                            response.messages.forEach(function (message) {
                                if (message.text && message.sender) {

                                    if (message.sender === 'user') {
                                        billChatMessages.append('<div class="user-message">' + billChatEscapeHtml(message.text) + '</div>');
                                    } else if (message.sender === 'chatgpt') {
                                        let processedText = message.text;
                                        //processedText = processedText.replace(/<br>/g, '<br>');
                                        processedText = processedText.replace(/&lt;br&gt;/g, '<br>');
                                        processedText = processedText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                                        billChatMessages.append('<div class="chatgpt-message">' + processedText + '</div>');

                                    }
                                }
                            });
                            billChatMessages.scrollTop(billChatMessages[0].scrollHeight);
                            $('.stopbadbots-dashboard-spinner999').css('display', 'none');
                            setTimeout(function () { $('#stopbadbots-dashboard-chat-form button').prop('disabled', false); }, 2000);
                        }
                    } else {
                        console.error(stopbadbots_bill_data.invalid_response_format, response);
                        $('.stopbadbots-dashboard-spinner999').css('display', 'none');
                        $('#stopbadbots-dashboard-chat-form button').prop('disabled', false);
                    }
                } catch (err) {
                    console.error(stopbadbots_bill_data.response_processing_error, err, response);
                    $('.stopbadbots-dashboard-spinner999').css('display', 'none');
                    $('#stopbadbots-dashboard-chat-form button').prop('disabled', false);
                }
            },
            error: function (xhr, status, error) {
                console.error(stopbadbots_bill_data.ajax_error, error, xhr.responseText);
                $('.stopbadbots-dashboard-spinner999').css('display', 'none');
                $('#stopbadbots-dashboard-chat-form button').prop('disabled', false);
                setTimeout(() => billChaterrorMessage.fadeOut(), 5000);
            },
        });
    }

    $('#stopbadbots-dashboard-chat-form button').on('click', function (e) {
        e.preventDefault();

        billChat_stopAnimationFeature();

        const clickedButtonId = $(this).attr('id');
        const message = billChatInput.val().trim();

        const assistanceType = $('input[name="assistance_type"]:checked').val();

        const chatType = (clickedButtonId === 'stopbadbots-dashboard-auto-checkup' || clickedButtonId === 'stopbadbots-dashboard-auto-checkup2')
            ? clickedButtonId
            : assistanceType;

        if ((chatType === 'stopbadbots-dashboard-auto-checkup' || chatType === 'stopbadbots-dashboard-auto-checkup2') || (chatType !== 'stopbadbots-dashboard-auto-checkup' && chatType !== 'stopbadbots-dashboard-auto-checkup2' && message !== '')) {
            $('.stopbadbots-dashboard-spinner999').css('display', 'block');
            $('#stopbadbots-dashboard-chat-form button').prop('disabled', true);

            $.ajax({
                url: stopbadbots_bill_data.ajax_url,
                method: 'POST',
                data: {
                    action: 'stopbadbots_dashboard_send_message',
                    message: message,
                    chat_type: chatType,
                    chat_version: chatVersion,
                    security: stopbadbots_bill_data.send_nonce
                },
                timeout: 60000,
                success: function () {
                    setTimeout(function () { billChatInput.val(''); }, 2000);
                    billChatLoadMessages();
                },
                error: function (xhr, status, error) {
                    $('.stopbadbots-dashboard-spinner999').css('display', 'none');
                    $('#stopbadbots-dashboard-chat-form button').prop('disabled', false);
                    setTimeout(() => billChaterrorMessage.fadeOut(), 5000);
                }
            });
        } else {
            billChaterrorMessage.text(stopbadbots_bill_data.empty_message_error).show();
            setTimeout(() => billChaterrorMessage.fadeOut(), 3000);
        }
    });

    setInterval(() => {
        if (billChatMessages.is(':visible')) {
            billChatLoadMessages();
        }
    }, 3000);
});