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
 */
function createRows(items, valuesCallback, classes, rowCallback) {
    'use strict';

    const $body = $('<tbody/>');
    items.forEach(function (item) {
        const values = valuesCallback(item);
        const $row = createRow(values, classes);
        if ($.isFunction(rowCallback)) {
            rowCallback(item, $row);
        }
        $body.append($row);
    });
    $('#result').html($body);
}

/**
 * Fill the table with the generated calculations
 *
 * @param {array}
 *            calculations - the calculations to render.
 */
function renderCalculations(calculations) {
    'use strict';

    const valuesCallback = function (c) {
        return [c.id, c.date, c.state, c.description + "<br>" + c.customer, c.margin, c.total];
    };
    const rowCallback = function (c, $row) {
        const $cell = $row.find('td:first');
        $cell[0].style.setProperty('border-left-color', c.color, 'important');
    };
    const classes = ['text-id text-border', 'text-date', 'text-state', '', 'text-percent', 'text-currency'];
    createRows(calculations, valuesCallback, classes, rowCallback);
}

/**
 * Fill the table with the generated customers
 *
 * @param {array}
 *            customers - the customers to render.
 */
function renderCustomers(customers) {
    'use strict';

    const valuesCallback = function (c) {
        return [c.nameAndCompany, c.address, c.zipCity];
    };
    const classes = ['w-50', 'w-25', 'w-25'];
    createRows(customers, valuesCallback, classes);
}

/**
 * Disable the submit and cancel buttons.
 */
function disableButtons() {
    'use strict';
    const $form = $('#edit-form');
    const $submit = $form.find(':submit');
    const spinner = '<span class="spinner-border spinner-border-sm"></span>';
    $submit.data('text', $submit.text()).toggleDisabled(true).html(spinner);
    $form.find('.btn-cancel').toggleDisabled(true);
}

/**
 * Enable the submit and cancel buttons.
 */
function enableButtons() {
    'use strict';
    const $form = $('#edit-form');
    const $submit = $form.find(':submit');
    $submit.toggleDisabled(false).html($submit.data('text'));
    $form.find('.btn-cancel').toggleDisabled(false);
}

/**
 * Generate values.
 */
function generate() {
    'use strict';

    disableButtons();
    $('#content').slideUp();

    const url = $('#form_entity').val();
    const data = {
        count: $('#form_count').intVal(),
        simulate: $('#form_simulate').isChecked()
    };
    $.getJSON(url, data, function (response) {
        enableButtons();
        const title = $(".card-title").text();
        $('#form_confirm').setChecked(false);
        if (response.result) {
            const index = $('#form_entity').prop('selectedIndex');
            if (index === 0) {
                renderCustomers(response.items);
            } else {
                renderCalculations(response.items);
            }
            $('#content').slideDown();
            Toaster.success(response.message, title, $("#flashbags").data());
        } else {
            Toaster.danger(response.message, title, $("#flashbags").data());
        }
    });
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // init validation
    const options = {
        submitHandler: function () {
            generate();
        }
    };
    $('#edit-form').initValidator(options);
}(jQuery));
