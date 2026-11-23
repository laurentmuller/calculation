/* globals Toaster, TomSelect */

/**
 * Show an error message
 *
 * @param {jQuery} $form - the edit form.
 * @param {string} [message]- the message to display or null to use default.
 */
function showError($form, message) {
    'use strict';
    message = message || $form.data('error');
    const title = $form.find('.card-title').text();
    Toaster.danger(message, title);
}

/**
 * Update the text of the given element.
 *
 * @param {jQuery} $element - the element to update.
 * @param {String} date - the new date.
 * @returns {jQuery} the element for chaining.
 */
function updateDate($element, date) {
    'use strict';
    const source = $element.data('text');
    const text = source.replace('%date%', date);
    return $element.text(text);
}

/**
 * Format the given value.
 *
 * @param {number} value - the value to format.
 * @param {number} digits - the number of fraction digits.
 * @return {string} the formatted value.
 */
function formatValue(value, digits) {
    'use strict';
    /* eslint new-cap: ["error", { "newIsCap": false }] */
    const formatter = Intl.NumberFormat('de-CH', {
        'minimumFractionDigits': digits,
        'maximumFractionDigits': digits
    });
    return formatter.format(value);
}

/**
 * Compute values and update the UI.
 */
function compute() {
    'use strict';
    const $form = $('#edit-form').validateForm();
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
            $('#result').val(formatValue(result, targetDigits));
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
 * Format a currency.
 * @param {Object} currency
 * @param {function} escape
 */
function formatCurrency(currency, escape) {
    'use strict';
    const text = escape(currency.text);
    const value = currency.value.toLowerCase();

    return `<div class="d-inline-flex align-items-center w-100">
                <span class="me-1 currency-flag currency-flag-${value}"></span>
                <span class="text-truncate">${text}</span>      
            </div>`;
}

/**
 * Handle swap button click event.
 * @param {TomSelect} tsBaseCode
 * @param {TomSelect} tsTargetCode
 */
function swapCodes(tsBaseCode, tsTargetCode) {
    'use strict';
    const baseCode = tsBaseCode.getValue();
    const targetCode = tsTargetCode.getValue();
    tsBaseCode.setValue(targetCode, false);
    tsTargetCode.setValue(baseCode, false);
    compute();
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    // initialize select
    const tsBaseCode = initSingleTomSelect('#baseCode', {
        render: {
            item: (data, escape) => formatCurrency(data, escape),
            option: (data, escape) => formatCurrency(data, escape),
        }
    });
    const tsTargetCode = initSingleTomSelect('#targetCode', {
        render: {
            item: (data, escape) => formatCurrency(data, escape),
            option: (data, escape) => formatCurrency(data, escape),
        }
    });

    // bind events
    $('.btn-swap').on('click', function () {
        swapCodes(tsBaseCode, tsTargetCode);
    });
    $('#baseCode, #targetCode, #amount').on('input', function () {
        $(this).updateTimer(compute, 250);
    });

    // validate
    const options = {
        focus: false,
        showModification: false,
        submitHandler: () => compute(),
        rules: {
            targetCode: {
                notEqualTo: '#baseCode'
            }
        }
    };
    $('#edit-form').initValidator(options);
    $('#amount').trigger('select').trigger('focus');
    compute();
});
