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
    Toaster.notify(type, message, title);
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
 * Handle from and to input event.
 */
function handleSelection() {
    'use strict';
    // update exchange button.
    const from = $('#from').getSelectedOption().index();
    const to = $('#to').getSelectedOption().index();
    const state = from === 0 || from - 1 === to;
    $('.btn-exchange').toggleDisabled(state);
}

/**
 * Translate.
 *
 * @param {HTMLElement} form - the submitted form.
 * @param {boolean} notification - true to notify.
 */
function translate(form, notification) {
    'use strict';
    // in progress ?
    const $form = $(form);
    if ($form.data('changing')) {
        $form.removeData('changing');
        return;
    }
    const $from = $('#from');
    const $buttonSubmit = $form.find(':submit');
    const $buttonCopy = $('.btn-copy');
    const $labelDetected = $('#detected');
    const $textResult = $('#result');

    // wait
    $buttonCopy.toggleDisabled(true);
    $buttonSubmit.toggleDisabled(true);

    // build parameters
    const data = {
        'from': $from.val(),
        'to': $('#to').val(),
        'text': $('#text').val().trim(),
        'service': $('#service').val()
    };

    // abort
    if (form.jqXHR) {
        form.jqXHR.abort();
        form.jqXHR = null;
    }

    // call
    $.ajaxSetup({global: false});
    const url = $form.data('ajax');
    form.jqXHR = $.post(url, data, function (response) {
        // ok?
        if (response.result) {
            const data = response.data;
            $textResult.val(data.target);
            $buttonCopy.toggleDisabled(false);

            // update
            if ($from.val()) {
                $labelDetected.text('');
            } else {
                const label = $form.data('detected').replace('%name%', data.from.name);
                $labelDetected.text(label);
                if (data.from.tag && $from.val() !== data.from.tag) {
                    $form.data('changing', true);
                    $from.val(data.from.tag).trigger('change');
                    handleSelection();
                }
            }

            // message
            const from = data.from.name;
            const to = data.to.name;
            const service = $('#service').getSelectedOption().text();
            const message = $form.data('success').replace('%from%', from).replace('%to%', to).replace('%service%', service);
            if (notification) {
                notify(Toaster.NotificationTypes.PRIMARY, message);
            }
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
        $.ajaxSetup({global: true});
        form.jqXHR = null;
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
 * Handle text change event.
 */
function handleTextChange() {
    'use strict';
    const $from = $('#from');
    const $to = $('#to');
    const $text = $('#text');

    const oldFrom = $from.data('old-value');
    const oldTo = $to.data('old-value');
    const oldText = $text.data('old-value');

    const newFrom = $from.val();
    const newTo = $to.val().trim();
    const newText = $text.val().trim();
    if (newText.length && (newFrom !== oldFrom || newTo !== oldTo || newText !== oldText)) {
        $from.data('old-value', newFrom);
        $to.data('old-value', newTo);
        $text.data('old-value', newText);
        translate($('#edit-form')[0], false);
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
    $.get(url, data, function (response) {
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

            // translate
            $('#text').data('old-value', null);
            handleTextChange();
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
    const $text = $('#text');
    const $from = $('#from');
    const $fromTo = $('#from, #to');

    // initialize select
    $fromTo.initSelect2();

    // clipboard
    if (ClipboardJS.isSupported('copy')) {
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
    }).on('change', function () {
        $(this).createTimer(handleTextChange, 500);
    });
    $('#text, #result').on('focus', function () {
        $(this).trigger('select');
    });
    $('#service').on('input', function () {
        handleService();
    });
    $text.on('keydown', function () {
        $(this).createTimer(handleTextChange, 500);
    });

    // validate
    const options = {
        focus: false,
        showModification: false,
        submitHandler: function (form) {
            translate(form, true);
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
    $text.trigger('focus');
}(jQuery));
