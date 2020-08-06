/**! compression tag for ftp-deployment */

/* globals Toaster, ClipboardJS */

/**
 * Display a notification.
 * 
 * @param {string}
 *            type - the notification type.
 * @param {string}
 *            message - the notification message.
 */
function notify(type, message) {
    'use strict';

    const title = $('#edit-form').data('title');
    Toaster.notify(type, message, title, $('#flashbags').data());
}

/**
 * Transalte
 */
function translate() {
    'use strict';

    const $form = $('#edit-form');
    const $buttonSubmit = $form.find(':submit');
    const $buttonCopy = $('.btn-copy');
    const $labelDetected = $('#detected');
    const $textResult = $('#result');

    // wait
    const html = $buttonSubmit.html();
    const spinner = '<span class="spinner-border spinner-border-sm"></span>';
    $buttonSubmit.addClass('disabled').html(spinner);
    $buttonCopy.attr('disabled', 'disabled');

    // build parameters
    $('#text').val($('#text').val().trim());
    
    const data = {
        'from': $('#from').val(),
        'to': $('#to').val(),
        'text': $('#text').val(),
        'service': $('#service').val()
    };

    // call
    const url = $form.data('ajax');
    $.post(url, data, function (response) {
        // restore
        $buttonSubmit.removeClass('disabled').html(html);

        // ok?
        if (response.result) {
            const data = response.data;
            $textResult.val(data.target);
            $buttonCopy.removeAttr('disabled');

            // update
            if ($('#from').val()) {
                $labelDetected.text('');
            } else {
                const label = $form.data('detected').replace('%name%', data.from.name);
                $labelDetected.text(label);
            }

            // message
            const from= data.from.name;
            const to= data.to.name;
            const service = $('#service option:selected').text();
            const message = $form.data('success')
                .replace('%from%', from)
                .replace('%to%', to)
                .replace('%service%', service);
            notify(Toaster.NotificationTypes.PRIMARY, message);

        } else {
            // update
            $textResult.val('');
            $labelDetected.text('');
            $buttonCopy.attr('disabled', 'disabled');

            // message
            let message = response.message;
            if (response.exception) {
                message += '<hr>Code : ' + response.exception.code;
                message += '<br>' + response.exception.message;
            }
            notify(Toaster.NotificationTypes.DANGER, message);
        }
    });
}

/**
 * Handle success copy event.
 */
function onCopySuccess(e) {
    'use strict';

    e.clearSelection();
    const message = $('#edit-form').data('copy-success');
    notify(Toaster.NotificationTypes.SUCCESS, message);
}

/**
 * Handle the error copy event.
 */
function onCopyError(e) {
    'use strict';

    e.clearSelection();
    const message = $('#edit-form').data('copy-error');
    notify(Toaster.NotificationTypes.WARNING, message);
}

/**
 * Handle exchange click event.
 */
function onExchange() {
    'use strict';

    // Exhange from and to language.
    const from = $('#from').val();
    if (from) {
        $('#from').val($('#to').val());
        $('#to').val(from);
    }
}

/**
 * Handle from and to input event.
 */
function onSelection() {
    'use strict';

    // update exchange button.
    const from = $('#from option:selected').index();
    const to = $('#to option:selected').index();
    if (from > 0 && to >= 0 && from - 1 !== to) {
        $('.btn-exchange').removeClass('disabled');
    } else {
        $('.btn-exchange').addClass('disabled');
    }
}

/**
 * Gets the locale (language).
 * 
 * @returns the locale.
 */
function getLocale() {
    'use strict';

    const locale = $('#edit-form').data('locale');
    return locale.split('_')[0];
}

/**
 * Handles the service input event.
 */
function onService() {
    'use strict';

    // get selection
    const $service = $('#service');
    const $option = $service.getSelectedOption();
    if (!$option.length) {
        return;
    }

    // update API documentation URL
    const href = $option.data('api');
    $('#api-url').attr('href', href);

    // update languages
    const url = $service.data('languages');
    const data = {
        'service': $service.val()
    };
    $.post(url, data, function (response) {
        if (response) {
            const $to = $('#to');
            const $from = $('#from');

            // save values
            const oldTo = $to.val();
            const oldFrom = $from.val();

            // clear
            $to.empty();
            $from.find('option').not(':first').remove();

            // add options
            let option;
            $.each(response, function (text, value) {
                option = '<option value="{0}">{1}</option>'.format(value, text);
                $to.append(option);
                $from.append(option);
            });

            // restore values
            if (!$to.val(oldTo).val() && !$to.val(getLocale()).val()) {
                $to.selectFirstOption();
            }
            if (oldFrom && !$from.val(oldFrom).val()) {
                $from.selectFirstOption();
            }
        }
    });
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    // clipboard
    if (ClipboardJS.isSupported()) {
        const clipboard = new ClipboardJS('.btn-copy');
        clipboard.on('success', function (e) {
            onCopySuccess(e);
        });
        clipboard.on('error', function (e) {
            onCopyError(e);
        });
    } else {
        $('.btn-copy').remove();
    }

    // bind events
    $('.btn-exchange').on('click', function () {
        onExchange();
    });
    $('#from, #to').on('input', function () {
        onSelection();
    });
    $('#text, #result').on('focus', function () {
        $(this).select();
    });
    $('#service').on('input', function () {
        onService();
    });

    // validate
    const options = {
        focus: false,
        submitHandler: function () {
            translate();
        },
        rules: {
            text: {
                normalizer: function (value) {
                    return value.trim();
                }
            }
        }
    };

    $('#edit-form').initValidator(options);
    $('#text').focus();
});
