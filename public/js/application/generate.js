/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Creates a row for the given values.
 *
 * @param {array}
 *            values - the cell values.
 * @param {array}
 *            classes - the cell classes. This array must have the same length
 *            as the values.
 * @returns {jQuery} the created row.
 */
function createRow(values, classes) {
    'use strict';
    const $row = $('<tr/>');
    values.forEach(function (value, index) {
        $row.append($('<td/>', {
            'html': value,
            'class': classes[index]
        }));
    });
    return $row;
}

/**
 * Creates rows for the given items.
 *
 * @param {array}
 *            items - the items to render.
 * @param {function}
 *            valuesCallback - the function to get the cell values.
 * @param {array}
 *            classes - the cell classes.
 * @param {function}
 *            rowCallback - the optional function to update the row.
 * @param {string}
 *            header - the table headers to display.
 */
function createRows(items, valuesCallback, classes, rowCallback, headers) {
    'use strict';

    // build
    const $body = $('<tbody/>', {
        'id': 'result'
    });
    items.forEach(function (item) {
        const values = valuesCallback(item);
        const $row = createRow(values, classes);
        if ($.isFunction(rowCallback)) {
            rowCallback(item, $row);
        }
        $body.append($row);
    });
    $('#result').replaceWith($body);

    // update displayed headers
    $('#result').parents('table').find('thead').each(function () {
        const $this = $(this);
        $this.toggleClass('d-none', !$this.hasClass(headers));
    });

}

/**
 * Fill the table with the generated calculations
 *
 * @param {array}
 *            items - the calculations to render.
 */
function renderCalculations(items) {
    'use strict';

    const valuesCallback = function (item) {
        return [item.id, item.date, item.state, item.customer, item.description, item.margin, item.total];
    };
    const rowCallback = function (item, $row) {
        const $cell = $row.find('td:first');
        $cell[0].style.setProperty('border-left-color', item.color, 'important');
    };
    const classes = ['text-id text-border', 'text-date', 'text-state', '', '', 'text-percent', 'text-currency'];
    createRows(items, valuesCallback, classes, rowCallback, 'calculation');
}

/**
 * Fill the table with the generated customers
 *
 * @param {array}
 *            items - the customers to render.
 */
function renderCustomers(items) {
    'use strict';

    const valuesCallback = function (item) {
        return [item.nameAndCompany, item.address, item.zipCity];
    };
    const classes = ['w-50', 'w-25', 'w-25'];
    createRows(items, valuesCallback, classes, null, 'customer');
}

/**
 * Fill the table with the generated products
 *
 * @param {array}
 *            items - the products to render.
 */
function renderProducts(items) {
    'use strict';

    const valuesCallback = function (item) {
        return [item.description, item.group, item.category, item.price, item.unit];
    };
    const classes = ['w-50', 'text-group', 'text-category', 'text-currency', 'text-unit'];
    createRows(items, valuesCallback, classes, null, 'product');
}

/**
 * Disable the submit and cancel buttons.
 */
function disableButtons() {
    'use strict';
    const $form = $('#edit-form');
    const $submit = $form.find(':submit');
    $submit.data('focused', $submit.is(":focus")).toggleDisabled(true);
    $form.find('.btn-cancel').toggleDisabled(true);
    $('#message-result').addClass('d-none');
}

/**
 * Enable the submit and cancel buttons.
 */
function enableButtons() {
    'use strict';
    const $form = $('#edit-form');
    const $submit = $form.find(':submit');
    $submit.toggleDisabled(false);
    if ($submit.data('focused')) {
        $submit.focus();
    }
    $form.find('.btn-cancel').toggleDisabled(false);
}

/**
 * Notify a message.
 *
 * @param {string}
 *            type - the message type.
 * @param {string}
 *            message - the message to display.
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
        case 'customer.name':
            renderCustomers(response.items);
            break;
        case 'calculation.name':
            renderCalculations(response.items);
            break;
        case 'product.name':
            renderProducts(response.items);
            break;
        default:
            notifyMessage('warning', $('#edit-form').data('empty'));
            return;
        }

        $('#simulated').toggleClass('d-none', !response.simulate);
        $('#message').text(response.message);
        $('#message-result').removeClass('d-none');

    }).always(function () {
        $('#form_confirm').setChecked(false);
        enableButtons();

    }).fail(function () {
        notifyMessage('danger', $('#edit-form').data('error'));
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

    $('#form_simulate').on('input', function () {
        if ($(this).isChecked()) {
            $('#form_confirm').toggleDisabled(true).removeValidation();
        } else {
            $('#form_confirm').toggleDisabled(false);
        }
    });

    const options = {
        submitHandler: function () {
            generate();
        }
    };
    $('#edit-form').initValidator(options);
}(jQuery));
