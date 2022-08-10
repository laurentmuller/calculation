/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Show an error message
 *
 * @param {JQuery} $form - the edit form.
 * @param {string} [message]- the message to display or null to use default.
 */
function showError($form, message) {
    'use strict';
    message = message || $form.data('error');
    const title = $form.find('.card-title').text();
    Toaster.danger(message, title, $('#flashbags').data());
}

/**
 * Update the text of the given element.
 *
 * @param {JQuery} $element - the element to update.
 * @param {String} date - the new date.
 * @returns {JQuery} the element for chaining.
 */
function updateDate($element, date) {
    'use strict';
    const source = $element.data('text');
    const text = source.replace('%date%', date);
    $element.text(text);
    return $element;
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

    $.getJSON(url, data, function (response) {
        if (response.result) {
            const rate = response.rate;
            const amount = $('#amount').floatVal();
            const result = amount * rate;

            const $baseOption = $('#baseCode').getSelectedOption();
            const baseCode = $baseOption.data('name');
            const baseDigits = $baseOption.data('digits');
            const baseText = $.parseFloat(1).toFixed(baseDigits) + ' ' + baseCode;

            const $targetOption = $('#targetCode').getSelectedOption();
            const targetCode = $targetOption.data('name');
            const targetDigits = $targetOption.data('digits');
            const targetText = rate + ' ' + targetCode;

            $('#result').val(result.toFixed(targetDigits));
            $('#rate').text($form.data('rate').replace('%base%', baseText).replace('%target%', targetText));
            updateDate($('#last-update'), response.update);
            updateDate($('#next-update'), response.next);

        } else {
            showError($form, response.message);
        }
    }).fail(function () {
        showError($form);
    });
}

/**
 * Format the currency entry.
 *
 * @param {Object} currency - the currency data.
 * @returns the formatted currency.
 */
function formatCurrency(currency) {
    'use strict';
    const id = currency.id;
    const text = currency.text;
    if (!id) {
        return text;
    }

    const $flag = $('<span/>', {
        'class': 'mr-1 currency-flag currency-flag-' + id.toLowerCase(),
    });
    const $text = $('<span/>', {
        'class': 'text-truncate',
        'text': text
    });
    const $div = $('<div/>', {
        'class': 'd-inline-flex align-items-center w-100'
    });
    return $div.append($flag).append($text);
}

/**
 * Handle swap button click event.
 */
function swapCodes() {
    'use strict';
    const $baseCode = $('#baseCode');
    const $targetCode = $('#targetCode');
    const temp = $baseCode.val();
    if (temp) {
        $baseCode.val($targetCode.val()).trigger('change');
        $targetCode.val(temp).trigger('change');
        compute();
    }
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // initialize select
    $('#baseCode, #targetCode').initSelect2({
        templateSelection: formatCurrency,
        templateResult: formatCurrency
    });

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
    $('#amount').trigger('select').trigger('focus');
    compute();

}(jQuery));
