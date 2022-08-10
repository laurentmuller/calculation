/**! compression tag for ftp-deployment */

/* globals Toaster, ClipboardJS */

/**
 * Display a notification.
 *
 * @param {string} type - the notification type.
 * @param {string} message - the notification message.
 */
function notify(type, message) {
    'use strict';
    const title = $('#edit-form').data('title');
    const options = $('#flashbags').data();
    Toaster.notify(type, message, title, options);
}

/**
 * Handle the error response.
 *
 * @param {Object} response - the Ajax call response.
 * @param {string} response.message - the response message.
 * @param {Object} response.exception - the response exception.
 */
function handleError(response) {
    'use strict';
    let message = response.message;
    if (response.exception) {
        const error = $('#edit-form').data('last-error');
        message += error.replace('%code%', response.exception.code).replace('%message%', response.exception.message);
    }
    notify(Toaster.NotificationTypes.DANGER, message);
}

/**
 * Translate.
 *
 * @param {HTMLElement} form - the submitted form.
 */
function translate(form) {
    'use strict';
    const $form = $(form);
    const $buttonSubmit = $form.find(':submit');
    const $buttonCopy = $('.btn-copy');
    const $labelDetected = $('#detected');
    const $textResult = $('#result');

    // wait
    $buttonSubmit.toggleDisabled(true);
    $buttonCopy.toggleDisabled(true);

    // build parameters
    const $text = $('#text');
    $text.val($text.val().trim());
    const data = {
        'from': $('#from').val(),
        'to': $('#to').val(),
        'text': $text.val(),
        'service': $('#service').val()
    };

    // call

    const url = $form.data('ajax');
    $.post(url, data, function (response) {
        // ok?
        if (response.result) {
            const data = response.data;
            $textResult.val(data.target);
            $buttonCopy.toggleDisabled(false);

            // update
            if ($('#from').val()) {
                $labelDetected.text('');
            } else {
                const label = $form.data('detected').replace('%name%', data.from.name);
                $labelDetected.text(label);
            }

            // message
            const from = data.from.name;
            const to = data.to.name;
            const service = $('#service').getSelectedOption().text();
            const message = $form.data('success').replace('%from%', from).replace('%to%', to).replace('%service%', service);
            notify(Toaster.NotificationTypes.PRIMARY, message);

        } else {
            // update
            $textResult.val('');
            $labelDetected.text('');
            $buttonCopy.toggleDisabled(true);

            // message
            handleError(response);
        }
    }).always(function () {
        $buttonSubmit.toggleDisabled(false);
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
function handleExchange() {
    'use strict';
    // exchange from and to language.
    const $from = $('#from');
    const $to = $('#to');
    const from = $from.val();
    if (from) {
        $from.val($to.val()).trigger('change');
        $to.val(from).trigger('change');
    }
}

/**
 * Handle from and to input event.
 */
function handleSelection() {
    'use strict';
    // update exchange button.
    const from = $('#from').getSelectedOption().index();
    const to = $('#to').getSelectedOption().index();
    $('.btn-exchange').toggleDisabled(from === 0 || from - 1 === to);
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
function handleService() {
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
        const $to = $('#to');
        const $from = $('#from');

        // save values
        const oldTo = $to.val();
        const oldFrom = $from.val();

        // clear
        $to.empty();
        $from.find('option').not(':first').remove();

        if (response.result) {
            // add options
            let option;
            $.each(response.languages, function (text, value) {
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
        } else {
            handleError(response);
        }
    });
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    // initialize select
    const $fromTo = $('#from, #to');
    $fromTo.initSelect2();

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
        handleExchange();
    });
    $fromTo.on('input', function () {
        handleSelection();
    });
    $('#text, #result').on('focus', function () {
        $(this).trigger('select');
    });
    $('#service').on('input', function () {
        handleService();
    });

    // validate
    const options = {
        focus: false,
        submitHandler: function (form) {
            translate(form);
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
    $('#text').trigger('focus');
}(jQuery));
