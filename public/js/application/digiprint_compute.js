/** ! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Update the given input.
 * 
 * @param string
 *            id - the input identifier.
 * @param number -
 *            value the value to set .
 */
function updateValue(id, value) {
    'use strict';
    const formatter = new Intl.NumberFormat('de-CH', {
        'minimumFractionDigits': 2,
        'maximumFractionDigits': 2
    });
    $('#' + id).val(formatter.format(value));
}
/**
 * Send form to server and update UI.
 * 
 * @param form
 *            form - the submitted form.
 */
function update(form) {
    'use strict';

    // get data
    const $form = $(form);
    const url = $form.data('url');
    const data = {
        'id': $('#digi_print_service_digiprint').intVal(),
        'quantity': $('#digi_print_service_quantity').intVal(),
        'price': $('#digi_print_service_price').isChecked(),
        'blacklit': $('#digi_print_service_blacklit').isChecked(),
        'replicating': $('#digi_print_service_replicating').isChecked(),
    };

    // cancel send
    if ($form.jqXHR) {
        $form.jqXHR.abort();
        $form.jqXHR = null;
    }

    // send
    $form.jqXHR = $.post(url, data, function (response) {
        if (response.result) {
            // update
            updateValue('priceAmount', response.priceAmount);
            updateValue('priceTotal', response.priceTotal);
            updateValue('blacklitAmount', response.blacklitAmount);
            updateValue('blacklitTotal', response.blacklitTotal);
            updateValue('replicatingAmount', response.replicatingAmount);
            updateValue('replicatingTotal', response.replicatingTotal);
            updateValue('overall', response.overall);
        } else {
            const title = $('.card-title').text();
            const options = $('#flashbags').data();
            Toaster.danger(response.message, title, options);
        }
    }).fail(function () {
        const title = $('.card-title').text();
        const options = $('#flashbags').data();
        const message = $form.data('failed');
        Toaster.danger(message, title, options);
    });
}

/**
 * Handles input change by sending form.
 */
function onInputChange() {
    'use strict';

    // update UI
    const $option = $('#digi_print_service_digiprint option:selected');
    $('#digi_print_service_price').attr("disabled", !$option.data('prices'));
    $('#digi_print_service_blacklit').attr("disabled", !$option.data('backlits'));
    $('#digi_print_service_replicating').attr("disabled", !$option.data('replicatings'));

    // submit
    $("#edit-form").submit();
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // format
    $("#digi_print_service_quantity").inputNumberFormat({
        'decimal': 0
    });

    // bind change
    $('#digi_print_service_digiprint, #digi_print_service_quantity').on('input', function () {
        $(this).updateTimer(onInputChange, 250);
    });
    $('#digi_print_service_price, #digi_print_service_blacklit, #digi_print_service_replicating').on('change', function () {
        $(this).updateTimer(onInputChange, 250);
    });

    // validation
    const options = {
        submitHandler: function (form) {
            update(form);
        }
    };
    $('form').initValidator(options);
    onInputChange();
}(jQuery));
