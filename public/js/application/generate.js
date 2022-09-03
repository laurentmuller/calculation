/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Creates rows for the given items.
 *
 * @param {array} items - the items to render.
 * @param {object} mapping - a map where keys are the item property and value is the optional cell class.
 * @param {function} [callback] - an optional function to update the row.
 */
function createRows(items, mapping, callback) {
    'use strict';
    const $body = $('<tbody/>');
    const entries = Object.entries(mapping);
    const isCallback = typeof callback === 'function';
    items.forEach(function (item, index) {
        const $row = $('<tr/>');
        for (const [key, className] of entries) {
            $row.append($('<td/>', {
                'html': item[key],
                'class': className || ''
            }));
        }
        if (isCallback) {
            callback(item, $row);
        }
        if (index === 0) {
            // remove top border
            $row.find('td').addClass('border-top-0');
        }
        $body.append($row);
    });
    $('#table-result tbody').replaceWith($body);
}

/**
 * Fill the table with the generated calculations
 *
 * @param {array} items - the calculations to render.
 */
function renderCalculations(items) {
    'use strict';
    const mapping = {
        'id': 'text-id text-border',
        'date': 'text-date',
        'state': 'text-state',
        'customer': 'text-nowrap',
        'description': 'text-nowrap',
        'margin': 'text-percent',
        'total': 'text-currency'
    };
    const callback = function (item, $row) {
        const $cell = $row.find('td:first');
        $cell[0].style.setProperty('border-left-color', item.color, 'important');
    };
    createRows(items, mapping, callback);
}

/**
 * Fill the table with the generated customers
 *
 * @param {array} items - the customers to render.
 */
function renderCustomers(items) {
    'use strict';
    const mapping = {
        'nameAndCompany': 'w-50 text-nowrap',
        'address': 'w-25 text-nowrap',
        'zipCity': 'w-25 text-nowrap'
    };
    createRows(items, mapping);
}

/**
 * Fill the table with the generated products
 *
 * @param {array} items - the products to render.
 */
function renderProducts(items) {
    'use strict';
    const mapping = {
        'description': 'w-50 text-nowrap',
        'group': 'text-group',
        'category': 'text-category',
        'price': 'text-currency',
        'unit': 'text-unit'
    };
    createRows(items, mapping);
}

/**
 * Disable submit and cancel buttons.
 */
function disableButtons() {
    'use strict';
    const $form = $('#edit-form');
    const $submit = $form.find(':submit');
    $submit.data('focused', $submit.is(":focus")).toggleDisabled(true);
    $form.find('.btn-cancel').toggleDisabled(true);
    $('#message-result').slideUp();
}

/**
 * Enable submit and cancel buttons.
 */
function enableButtons() {
    'use strict';
    const $form = $('#edit-form');
    const $submit = $form.find(':submit');
    $submit.toggleDisabled(false);
    if ($submit.data('focused')) {
        $submit.trigger('focus');
    }
    $form.find('.btn-cancel').toggleDisabled(false);
}

/**
 * Notify a message.
 *
 * @param {string} type - the message type.
 * @param {string} message - the message to display.
 */
function notifyMessage(type, message) {
    'use strict';
    const title = $(".card-title").text();
    Toaster.notify(type, message, title, $("#flashbags").data());
}

/**
 * Generate values.
 */
function generate() {
    'use strict';
    disableButtons();
    const url = $('#form_entity').val();
    const data = {
        count: $('#form_count').intVal(),
        simulate: $('#form_simulate').isChecked()
    };
    const $form = $('#edit-form');
    $.getJSON(url, data, function (response) {
        if (!response.result) {
            notifyMessage('danger', response.message || $('#edit-form').data('error'));
            return;
        } else if (!response.count) {
            notifyMessage('warning', $('#edit-form').data('empty'));
            return;
        }
        const key = $('#form_entity').getSelectedOption().data('key');
        switch (key) {
            case 'calculation':
                renderCalculations(response.items);
                break;
            case 'customer':
                renderCustomers(response.items);
                break;
            case 'product':
                renderProducts(response.items);
                break;
            default:
                notifyMessage('warning', $form.data('empty'));
                return;
        }

        $('#simulated').toggleClass('d-none', !response.simulate);
        $('#message').text(response.message);
        $('#message-result').slideDown();
    }).always(function () {
        $('#form_confirm').setChecked(false);
        enableButtons();
    }).fail(function () {
        notifyMessage('danger', $form.data('error'));
    });
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('#modal-result').on('hide.bs.modal', function () {
        $('#overflow').scrollTop(0);
    });
    $('#edit-form').simulate().initValidator({
        submitHandler: function () {
            generate();
        }
    });
}(jQuery));
