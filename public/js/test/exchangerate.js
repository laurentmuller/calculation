/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Show an error message
 *
 * @param {JQuery}
 *            $form - the edit form.
 * @param {string}
 *            message- the message to display or null to use default.
 */
function showError($form, message) {
    'use strict';
    message = message || $form.data('error');
    const title = $form.find('.card-title').text();
    Toaster.danger(message, title, $('#flashbags').data());
}

/**
 * Compute values and update UI.
 */
function compute() {
    'use strict';
    const $form = $('#edit-form');
    if (!$form.valid()) {
        return;
    }

    const url = $form.attr('action');
    const data = {
        'baseCode': $('#baseCode').val(),
        'targetCode': $('#targetCode').val()
    };

    $('*').css('cursor', 'wait');
    $.getJSON(url, data, function (response) {
        if (response.result) {
            const rate = response.rate;
            const amount = $('#amount').floatVal();
            const result = amount * rate;

            const $baseOption = $('#baseCode').getSelectedOption();
            const $baseCode = $baseOption.data('name');
            const $baseDigits = $baseOption.data('digits');
            const $baseText = parseFloat(1).toFixed($baseDigits) + ' ' + $baseCode;

            const $targetOption = $('#targetCode').getSelectedOption();
            const $targetCode = $targetOption.data('name');
            const $targetDigits = $targetOption.data('digits');
            const $targetText = rate + ' ' + $targetCode;

            $('#rate').text($baseText + ' = ' + $targetText);
            $('#result').val(result.toFixed($targetDigits));
        } else {
            showError($form, response.message);
        }
    }).fail(function () {
        showError($form);
    }).always(function () {
        $('*').css('cursor', '');
    });
}

/**
 * Handle swap button click event.
 */
function swapCodes() {
    'use strict';
    const temp = $('#baseCode').val();
    $('#baseCode').val($('#targetCode').val()).trigger('change');
    $('#targetCode').val(temp).trigger('change');
    compute();
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize select
    $('#baseCode, #targetCode').initSelect2();

    // bind events
    $('.btn-swap').on('click', function () {
        swapCodes();
    });
    $('#baseCode, #targetCode, #amount').on('input', function () {
        $(this).updateTimer(compute, 250);
    });

    // validate
    const options = {
        focus: false,
        submitHandler: function () {
            compute();
        }
    };
    $('#edit-form').initValidator(options);
    $('#amount').select().focus();
    compute();

}(jQuery));
