/**! compression tag for ftp-deployment */

/* globals Toaster */

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
    $('#edit-form .form-control-plaintext:not(.skip-reset)').text(value);
}

/**
 * Display an error message.
 *
 * @param {string} message - the message to display.
 */
function showError(message) {
    'use strict';
    resetValues();
    const title = $('.card-title').text();
    Toaster.danger(message, title, $('#flashes').data());
}

/**
 * Send form to server and update UI.
 *
 * @param form form - the submitted form.
 */
function update(form) {
    'use strict';
    // get items
    const $itemsEmpty = $('.task-items-empty');
    const items = $('#table-edit .item-row:not(.d-none) :checkbox:checked').map(function () {
        return $(this).intVal();
    }).get();
    if (items.length === 0) {
        $('#table-edit .item-row:not(.d-none) :checkbox:first').trigger('focus');
        $itemsEmpty.removeClass('d-none');
        resetValues();
        return;
    }

    // update UI
    $('#quantity-error').remove();
    $itemsEmpty.addClass('d-none');

    // get data
    const $form = $(form);
    const url = $form.prop('action');
    const data = {
        'id': $('#task').intVal(), 'quantity': $('#quantity').floatVal(), 'items': items
    };

    // cancel send
    if ($form.jqXHR) {
        $form.jqXHR.abort();
        $form.jqXHR = null;
    }

    // send
    $form.jqXHR = $.post(url, data, function (response) {
        /**
         * @param {Object} response
         * @param {boolean} response.result
         * @param {string} response.message
         * @param {number} response.overall
         */
        if (response.result) {
            // update
            response.results.forEach(function (item) {
                updateValue('value_' + item.id, item.value);
                updateValue('amount_' + item.id, item.amount);
            });
            updateValue('overall', response.overall);
        } else {
            showError(response.message || $form.data('failed'));
        }
    }).fail(function (_jqXHR, textStatus) {
        if (textStatus !== 'abort') {
            showError($form.data('failed'));
        }
    });
}

/**
 * Handles input change.
 */
function onInputChanged() {
    'use strict';
    // submit
    $("#edit-form").trigger('submit');
}

/**
 * Handles task change.
 */
function onTaskChanged() {
    'use strict';
    // toggle rows visibility
    const $task = $('#task');
    const id = $task.intVal();
    const selector = '[data-id="' + id + '"]';
    $('.item-row:not(' + selector + ')').addClass('d-none');
    const $rows = $('.item-row' + selector).removeClass('d-none');

    // task items?
    const empty = $rows.length === 0;
    $('.row-table').toggleClass('d-none', empty);
    $('.row-empty').toggleClass('d-none', !empty);

    // unit
    const $selection = $task.getSelectedOption();
    $('#unit').html($selection.data('unit') || '&nbsp;');

    // submit
    if (!empty) {
        $("#edit-form").trigger('submit');
    }
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    // attach handlers
    $('#task').on('input', function () {
        $(this).updateTimer(onTaskChanged, 250);
    });
    $('#quantity').on('input', function () {
        $(this).updateTimer(onInputChanged, 250);
    }).inputNumberFormat();
    $('.item-input').on('change', function () {
        $(this).updateTimer(onInputChanged, 250);
    });

    // validation
    const options = {
        showModification: false, submitHandler: function (form) {
            update(form);
        }, rules: {
            quantity: {
                greaterThanValue: 0
            }
        }
    };
    $("#edit-form").initValidator(options);

    // update
    onInputChanged();
}(jQuery));
