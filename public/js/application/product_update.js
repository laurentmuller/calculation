/**! compression tag for ftp-deployment */

/**
 * Gets the visible products.
 *
 * @returns {JQuery} - the visible products.
 */
function getVisibleProducts() {
    'use strict';
    const id = $('#form_category').val();
    return $('#form_products :checkbox[category="' + id + '"]');
}

/**
 * Returns a value indicating if all products checkbox is checked.
 *
 * @returns {boolean} - true if checked.
 */
function isAllProducts() {
    'use strict';
    return $('#form_all_products').isChecked();
}

/**
 * Validate the products selection.
 *
 * @returns {boolean} - true if valid.
 */
function validateProducts() {
    'use strict';
    const validator = $('#edit-form').validate();
    return validator.element("#form_all_products");
}

/**
 * Check if products selection is requiered.
 *
 * @returns {boolean} true if requiered; false if not.
 */
function isProductsRequired() {
    'use strict';
    if (isAllProducts() || getVisibleProducts().filter(':checked').length > 0) {
        return false;
    }
    return true;
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // get widgets
    const $type = $('#form_type');
    const $fixed = $('#form_fixed');
    const $percent = $('#form_percent');
    const $category = $('#form_category');

    // validation
    $('#edit-form').initValidator({
        rules: {
            'form[percent]': {
                notEqualToZero: true
            },
            'form[fixed]': {
                notEqualToZero: true
            },
            'form[all_products]': {
                required: isProductsRequired
            }
        },
        submitHandler: function (form) {
            if (isAllProducts()) {
                $('#form_products :checkbox').setChecked(false);
            } else {
                const id = $category.val();
                $('#form_products :checkbox[category!="' + id + '"]').setChecked(false);
            }
            $(form).showSpinner();
            form.submit();
        }
    });

    // handle events
    $('#form_type_percent, #form_type_fixed').on('click', function () {
        const isPercent = $('#form_type_percent').isChecked();
        $fixed.toggleDisabled(isPercent);
        $percent.toggleDisabled(!isPercent);
        if (isPercent) {
            $fixed.removeValidation();
            $type.val($percent.data('type'));
        } else {
            $percent.removeValidation();
            $type.val($fixed.data('type'));
        }
    });

    $('#form_simulated').on('input', function () {
        if ($(this).isChecked()) {
            $('#form_confirm').toggleDisabled(true).removeValidation();
        } else {
            $('#form_confirm').toggleDisabled(false);
        }
    });

    $category.on('input', function () {
        const id = $(this).val();
        $('#form_products tbody tr[category="' + id + '"]').removeClass('d-none');
        $('#form_products tbody tr[category!="' + id + '"]').addClass('d-none');
        const $products = getVisibleProducts();
        if ($products.find(':checked').length === 0) {
            $products.setChecked(true);
        }
        validateProducts();
    });
    $category.trigger('input');

    $('#form_products tbody tr').on('click', function () {
        const $checkbox = $(this).find(':checkbox');
        if (!isAllProducts() && !$checkbox.is(':hover')) {
            $checkbox.toggleChecked();
            validateProducts();
        }
    });

    $('#form_products :checkbox').on('click', function () {
        validateProducts();
    });

    $('#form_all_products').on('click', function () {
        const disabled = $(this).isChecked();
        $('#form_products tr').toggleClass('text-secondary', disabled);
        $('#btn-all, #btn-none, #btn-reverse, #form_products :checkbox').toggleDisabled(disabled);
        validateProducts();
    });

    $('#btn-all').on('click', function () {
        getVisibleProducts().setChecked(true);
        validateProducts();
    });

    $('#btn-none').on('click', function () {
        getVisibleProducts().setChecked(false);
        validateProducts();
    });

    $('#btn-reverse').on('click', function () {
        getVisibleProducts().toggleChecked();
        validateProducts();
    });

}(jQuery));
