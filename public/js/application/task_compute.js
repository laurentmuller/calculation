/* globals Toaster */

/**
 * @typedef {Object} InputData
 * @property {number} id
 * @property {number} quantity
 * @property {number[]} items
 *
 * @typedef {Object} ResponseData
 * @property {boolean} result
 * @property {string} message
 * @property {number} overall
 * @property {Array.<{id: Number, value: number, amount: number}>} items
 */

/**
 * Update the given input.
 *
 * @param {string} id - the input identifier.
 * @param {number} value - the value to set.
 */
function updateValue(id, value) {
    'use strict';
    $('#' + id).text($.formatFloat(value));
}

/**
 * Reset all plain text to 0.
 */
function resetValues() {
    'use strict';
    const value = $.formatFloat(0);
    $('#edit-form .form-control-read-only:not(.skip-reset)').text(value);
}

/**
 * Display an error message.
 *
 * @param {string} [message] - the message to display.
 */
function showError(message) {
    'use strict';
    resetValues();
    const title = $('.card-title').text();
    message = message || $('#edit-form').data('failed');
    Toaster.danger(message, title);
}

/**
 * @param {JQuery} $form
 * @param {InputData} data
 * @return {boolean}
 */
function isSameValues($form, data) {
    'use strict';
    const newValue = JSON.stringify(data);
    const oldValue = $form.data('value');
    if (oldValue && oldValue === newValue) {
        return true;
    }
    $form.data('value', newValue);
    return false;
}

/**
 * Gets data to post.
 * @return {InputData}
 */
function getData() {
    'use strict';
    const id = $('#task').intVal();
    const selector = `#table-task-edit .task-item-row[data-id="${id}"] .item-input:checked`;
    const items = $(selector).map(function () {
        return $(this).intVal();
    }).get();

    return {
        id: id,
        items: items,
        quantity: $('#quantity').floatVal()
    };
}

/**
 * Validate.
 * @param {InputData} data
 * @return {boolean}
 */
function isValid(data) {
    'use strict';
    if (data.items.length === 0) {
        const id = $('#task').intVal();
        $(`#table-task-edit .task-item-row[data-id="${id}"] .item-input:first`).trigger('focus');
        $('.task-items-empty').removeClass('d-none');
        resetValues();
        return false;
    }
    return true;
}

/**
 * Cancel post submit.
 * @param {jQuery} $form
 */
function cancelSubmit($form) {
    'use strict';
    if ($form.jqXHR) {
        $form.jqXHR.abort();
        $form.jqXHR = null;
    }
}

/**
 * Send form to server and update UI.
 * @param {HTMLFormElement} form - the submitted form.
 */
function submitForm(form) {
    'use strict';
    // get data
    const data = getData();

    // same?
    const $form = $(form);
    if (isSameValues($form, data)) {
        return;
    }

    // valid?
    if (!isValid(data)) {
        return;
    }

    // update UI
    $('#quantity-error').remove();
    $('.task-items-empty').addClass('d-none');

    // cancel
    cancelSubmit($form);

    // send
    const url = $form.prop('action');
    $form.jqXHR = $.post(url, data, function (response) {
        /** @param {ResponseData} response */
        if (response.result) {
            // update
            response.items.forEach(function (item) {
                updateValue(`task_value_${item.id}`, item.value);
                updateValue(`task_total_${item.id}`, item.amount);
            });
            updateValue('task_overall', response.overall);
        } else {
            showError(response.message);
        }
    }).fail(function (_jqXHR, textStatus) {
        if (textStatus !== 'abort') {
            showError();
        }
    });
}

/**
 * Handles input change.
 */
function onInputChanged() {
    'use strict';
    $('#edit-form').trigger('submit');
}

/**
 * Handles task change.
 */
function onTaskChanged() {
    'use strict';
    // toggle rows visibility
    const $task = $('#task');
    const id = $task.intVal();
    $(`.task-item-row:not([data-id="${id}"])`).addClass('d-none');
    const $rows = $(`.task-item-row[data-id="${id}"]`).removeClass('d-none');

    // task items?
    const empty = $rows.length === 0;
    $('.row-table').toggleClass('d-none', empty);
    $('.row-empty').toggleClass('d-none', !empty);

    // unit
    const $selection = $task.getSelectedOption();
    $('#unit').html(String($selection.data('unit')) || '&nbsp;');

    // submit
    if (!empty) {
        $('#edit-form').trigger('submit');
    }
}

/**
 * Ready function
 */
$(function () {
    'use strict';
    // attach handlers
    $('#task').on('input', () => {
        $(this).updateTimer(onTaskChanged, 250);
    });
    $('#quantity').on('input', () => {
        $(this).updateTimer(onInputChanged, 250);
    }).inputNumberFormat();
    $('.item-input').on('input', () => {
        $(this).updateTimer(onInputChanged, 250);
    });

    // validation
    const options = {
        showModification: false,
        submitHandler: (form) => submitForm(form),
        rules: {
            quantity: {
                greaterThanValue: 0
            }
        }
    };
    $('#edit-form').initValidator(options);

    // update
    onInputChanged();
});
