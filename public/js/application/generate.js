/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Creates a row.
 * 
 * @param {array}
 *            values - the cell values.
 * @param {array}
 *            classes - the optional cell classes. This array must have the same
 *            length of elements as the array of values.
 * @returns {jQuery} the created row.
 */
function createRow(values, classes) {
    'use strict';

    const $row = $('<tr/>');
    values.forEach(function (value, index) {
        const $cell = $('<td/>').html(value);
        if (classes[index]) {
            $cell.addClass(classes[index]);
        }
        $row.append($cell);
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
    const classes = ['text-id', 'text-date', null, null, 'text-currency'];
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
 * Generate values.
 */
function generate() {
    'use strict';

    const entity = $('#form_entity').val();
    const count = $('#form_count').intVal();
    const url = entity.replace(/0/g, count);

    $('#content').slideUp('slow');
    $.getJSON(url, function (response) {
        const title = $(".card-title").text();
        $('#form_confirm').setChecked(false);
        if (response.result) {
            const index = $('#form_entity').prop('selectedIndex');
            if (index === 0) {
                renderCustomers(response.customers);
            } else {
                renderCalculations(response.calculations);
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

    // hide result on change
    $('#form_entity, #form_count').on('input', function () {        
        $('#content').slideUp();
    });

    // init validation
    const options = {
        submitHandler: function () {
            generate();
        },
    };
    $('#edit-form').initValidator(options);
}(jQuery));
