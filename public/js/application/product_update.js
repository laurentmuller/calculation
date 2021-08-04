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
 * Retuns if the error message for products is displayed.
 *
 * @returns {boolean} - true if displayed.
 */
function isProductsError() {
    'use strict';
    return $('#products-error').is(":visible");
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
 * @param {boolean}
 *            focus - true to set focus to the first product if not valid.
 * @returns {boolean} - true if valid.
 */
function validateProducts(focus) {
    'use strict';
    if (isAllProducts()) {
        $('#products-error').hide();
        return true;
    }
    const $products = getVisibleProducts();
    if ($products.filter(':checked').length > 0) {
        $('#products-error').hide();
        return true;
    }
    if (focus) {
        $products.first().focus();
    }
    $('#products-error').show();
    return false;
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    const $type = $('#form_type');
    const $fixed = $('#form_fixed');
    const $percent = $('#form_percent');
    const $category = $('#form_category');

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

        // toggle visibility
        $('#form_products tbody tr[category="' + id + '"]').removeClass('d-none');
        $('#form_products tbody tr[category!="' + id + '"]').addClass('d-none');

        // check all if none
        const $products = getVisibleProducts();
        if ($products.find(':checked').length === 0) {
            $products.setChecked(true);
        }

        if (isProductsError()) {
            validateProducts(false);
        }
    });
    $category.trigger('input');

    $('#form_products tbody tr').on('click', function () {
        const $checkbox = $(this).find(':checkbox');
        if (!isAllProducts() && !$checkbox.is(':hover')) {
            $checkbox.toggleChecked();
            if (isProductsError()) {
                validateProducts(false);
            }
        }
    });

    $('#form_products :checkbox').on('click', function () {
        if (isProductsError() && $(this).isChecked()) {
            validateProducts(false);
        }
    });

    $('#form_all_products').on('click', function () {
        const disabled = $(this).isChecked();
        $('#form_products').toggleClass('text-secondary', disabled);
        $('.btn-all, .btn-none, .btn-reverse, #form_products :checkbox').toggleDisabled(disabled);
        if (isProductsError()) {
            validateProducts(false);
        }
    });
    $('.btn-all').on('click', function () {
        getVisibleProducts().setChecked(true);
        if (isProductsError()) {
            validateProducts(false);
        }
    });
    $('.btn-none').on('click', function () {
        getVisibleProducts().setChecked(false);
    });
    $('.btn-reverse').on('click', function () {
        getVisibleProducts().toggleChecked();
        if (isProductsError()) {
            validateProducts(false);
        }
    });

    // validation
    $('#edit-form').initValidator({
        rules: {
            'form[percent]': {
                notEqualToZero: true
            },
            'form[fixed]': {
                notEqualToZero: true
            }
        },
        submitHandler: function (form) {
            if (!validateProducts(true)) {
                return;
            }
            if (isAllProducts()) {
                $('#form_products :checkbox').setChecked(false);
            } else {
                const id = $category.val();
                $('#form_products :checkbox[category!="' + id + '"]').setChecked(false);
            }
            form.submit();
        }
    });
}(jQuery));
