/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Creates a row.
 * 
 * @param {array}
 *            values - the cell values.
 * @param {array}
 *            classes - the cell classes. This array must have the same length
 *            of the values.
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
 * Fill the table with the generated calculations
 * 
 * @param {array}
 *            calculations - the calculations to render.
 */
function renderCalculations(calculations) {
    'use strict';

    const $body = $('<tbody/>');
    const classes = ['text-id', 'text-date', '', '', 'text-currency'];
    calculations.forEach(function (c) {
        $body.append(createRow([c.id, c.date, c.state, c.description + "<br>" + c.customer, c.total], classes));
    });
    $('#result').html($body);
}

/**
 * Fill the table with the generated customers
 * 
 * @param {array}
 *            customers - the customers to render.
 */
function renderCustomers(customers) {
    'use strict';

    const $body = $('<tbody/>');
    const classes = ['w-50', 'w-25', 'w-25'];
    customers.forEach(function (c) {
        $body.append(createRow([c.nameAndCompany, c.address, c.zipCity], classes));
    });
    $('#result').html($body);
}

/**
 * Disable the submit and cancel buttons.
 */
function disableButtons() {
    'use strict';
    const $form = $('#edit-form');
    const $submit = $form.find(':submit');
    const spinner = '<span class="spinner-border spinner-border-sm"></span>';
    $submit.data('text', $submit.text()).addClass('disabled').html(spinner);
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
    $('#content').slideUp('slow');

    const entity = $('#form_entity').val();
    const count = $('#form_count').intVal();
    const url = entity.replace(/0/g, count);
    $.getJSON(url, function (response) {
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
            $('#content').slideDown('slow');
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
        },
    };
    $('#edit-form').initValidator(options);
}(jQuery));
