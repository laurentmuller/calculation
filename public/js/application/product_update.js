/**! compression tag for ftp-deployment */

/**
 * Gets the product's checkboxes for the selected category.
 *
 * @returns {jQuery} - the checkboxes.
 */
function getVisibleProducts() {
    'use strict';
    const id = $('#form_category').val();
    return $('#form_products :checkbox[category="' + id + '"]');
}

/**
 * Returns a value indicating if all product checkboxes are checked.
 *
 * @returns {boolean} - true if checked.
 */
function isAllProducts() {
    'use strict';
    return $('#form_allProducts').isChecked();
}

/**
 * Validate the product's selection.
 *
 * @returns {boolean} - true if valid.
 */
function validateProducts() {
    'use strict';
    const validator = $('#edit-form').validate();
    return validator.element("#form_allProducts");
}

/**
 * Check if products selection is required.
 *
 * @returns {boolean} true if required; false if not.
 */
function isProductsRequired() {
    'use strict';
    return !(isAllProducts() || getVisibleProducts().filter(':checked:visible').length > 0);
}

/**
 * Returns a value indicating if the percent checkbox is checked.
 *
 * @return {boolean} true if checked, false otherwise.
 */
function isPercent() {
    'use strict';
    return $('#form_type_percent').isChecked();
}

/**
 * Hide rows with empty price when percent is selected.
 */
function hideEmptyPrices() {
    'use strict';
    const disabled = isPercent();
    getVisibleProducts().each(function () {
        const $this = $(this);
        const price = $.parseFloat($this.attr('price'));
        $this.parents('tr').toggleClass('d-none', disabled && price === 0);
    });
}

/**
 * Compute the new product price.
 *
 * @param {number} oldPrice - the old price of the product.
 * @param {number} value - the value to update with.
 * @param {boolean} isPercent - true if the value is a percentage, false if is a fixed amount
 * @param {boolean} round - true to round new value up to 0.05
 * @returns {number} the new price or Number.NaN if not applicable.
 */
function computePrice(oldPrice, value, isPercent, round) {
    'use strict';
    const newPrice = isPercent ? oldPrice * (1.0 + value / 100.0) : oldPrice + value;
    return round ? Math.round(newPrice * 20) / 20 : newPrice;
}

/**
 * Update the product's prices.
 */
function updatePrices() {
    'use strict';
    let value;
    const percent = isPercent();
    const round = $('#form_round').isChecked();
    if (percent) {
        value = $.parseFloat($('#form_percent').val());
    } else {
        value = $.parseFloat($('#form_fixed').val());
    }
    const result = !Number.isNaN(value);

    getVisibleProducts().each(function () {
        let text = '-.--';
        const $this = $(this);
        if (result) {
            const oldPrice = $.parseFloat($this.attr('price'));
            const newPrice = computePrice(oldPrice, value, percent, round);
            if (!Number.isNaN(newPrice)) {
                text = $.formatFloat(newPrice);
            }
        }
        $this.closest('tr').find('td:eq(2)').text(text);
    });
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
    $('#edit-form').simulate().initValidator({
        rules: {
            'form[percent]': {
                notEqualToZero: true
            },
            'form[fixed]': {
                notEqualToZero: true
            },
            'form[allProducts]': {
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
        const percent = isPercent();
        $fixed.toggleDisabled(percent);
        $percent.toggleDisabled(!percent);
        if (percent) {
            $fixed.removeValidation();
            $type.val($percent.data('type'));
        } else {
            $percent.removeValidation();
            $type.val($fixed.data('type'));
        }
        hideEmptyPrices();
        updatePrices();
        validateProducts();
    });

    $('#form_percent, #form_fixed, #form_round').on('input', updatePrices);

    $category.on('input', function () {
        const id = $(this).val();
        $('#form_products tbody tr[category="' + id + '"]').removeClass('d-none');
        $('#form_products tbody tr[category!="' + id + '"]').addClass('d-none');
        const $products = getVisibleProducts();
        if ($products.find(':checked').length === 0) {
            $products.setChecked(true);
        }
        hideEmptyPrices();
        validateProducts();
        updatePrices();
    });
    $category.trigger('input');

    $('#form_products tbody').on('mousedown', 'td', function (e) {
        const $target = $(e.target);
        if (e.which === 1 && !$target.is(':checkbox') && !$target.is('label') && !isAllProducts()) {
            $(this).closest('tr').find(':checkbox').toggleChecked().trigger('focus');
            validateProducts();
        }
    });

    $('#form_products :checkbox').on('click', function () {
        validateProducts();
    });

    $('#form_allProducts').on('click', function () {
        const disabled = $(this).isChecked();
        $('#form_products tbody tr').toggleClass('text-secondary', disabled);
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
