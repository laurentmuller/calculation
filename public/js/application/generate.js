/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Generate values.
 */
function generate() {
    'use strict';

    const entity = $('#form_entity').val();
    const count = $('#form_count').val();
    const url = entity.replace(/0/g, count);
    const index = $('#form_entity').prop('selectedIndex');

    $('#result').hide();
    $.getJSON(url, function (response) {
        if (response.result) {
            if (index === 0) {
                displayCalculations(response.calculations);
            } else {
                displayCustomers(response.customers);
            }
            const title = $(".card-title").text();
            Toaster.success(response.message, title, $("#flashbags").data());
        }
    });
}

/**
 * Creates a row.
 * 
 * @param {array}
 *            values - the cell values.
 * @param {array}
 *            classes - the optional cell classes. This array mus have the same
 *            number of elements as the array of values.
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
 * Display the generate calculations
 * 
 * @param {array}
 *            calculations - the calculations to display.
 */
function displayCalculations(calculations) {
    'use strict';

    const $body = $('<tbody/>');
    const classes = ['text-id', 'text-date', null, null, 'text-currency'];
    calculations.forEach(function (c) {
        $body.append(createRow([c.id, c.date, c.state, c.description + "<br>" + c.customer, c.total], classes));
    });
    $('#result').html($body).show();
}

/**
 * Display the generate customers
 * 
 * @param {array}
 *            customers - the customers to display.
 */
function displayCustomers(customers) {
    'use strict';

    const $body = $('<tbody/>');
    const classes = ['w-50', 'w-25', 'w-25'];
    customers.forEach(function (c) {
        $body.append(createRow([c.nameAndCompany, c.address, c.zipCity], classes));
    });
    $('#result').html($body).show();
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // hide result on change
    $('#form_entity, #form_count').on('input', function () {
        $('#result').hide();
    });

    // init validation
    const options = {
        submitHandler: function () {
            generate();
        },
    };
    $('#edit-form').initValidator(options);
}(jQuery));
