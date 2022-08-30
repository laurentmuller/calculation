/**! compression tag for ftp-deployment */

/**
 * Gets the product's checkboxes for the selected category.
 * @param {number} [id] - the selected category.
 * @returns {JQuery} - the checkboxes.
 */
function getProducts(id) {
    'use strict';
    id = id || $('#form_category').val();
    return $('#form_products :checkbox[data-category="' + id + '"]');
}

/**
 * Returns a value indicating if all product checkbox is checked.
 *
 * @returns {boolean} - true if checked.
 */
function isAllProducts() {
    'use strict';
    return $('#form_allProducts').isChecked();
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
 * Gets the product's checked checkboxes.
 *
 * @return {JQuery} - the checked checkboxes.
 */
function getSelectedProducts() {
    'use strict';
    const selector = isAllProducts() ? '.selectable' : '.selectable:checked';
    return getProducts().filter(selector);
}

/**
 * Gets the selectable product's checkboxes.
 *
 * @return {JQuery} - the selectable checkboxes.
 */
function getSelectableProducts() {
    'use strict';
    return getProducts().filter('.selectable');
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
 * Hide rows with empty price when percent radio is selected.
 */
function hideEmptyPrices() {
    'use strict';
    const disabled = isPercent();
    getProducts().each(function () {
        const $this = $(this);
        const price = $.parseFloat($this.attr('data-price'));
        const selectable = !disabled || price !== 0;
        $this.toggleClass('selectable', selectable).parents('tr').toggleClass('d-none', !selectable);
    });
}

/**
 * Compute the new product price.
 *
 * @param {number} oldPrice - the old price of the product.
 * @param {number} value - the value to update with.
 * @param {boolean} isPercent - true if the value is a percentage, false if is a fixed amount
 * @param {boolean} round - true to round new value up to 0.05
 * @returns {number} the new price.
 */
function computePrice(oldPrice, value, isPercent, round) {
    'use strict';
    const newPrice = isPercent ? oldPrice * (1.0 + value / 100.0) : oldPrice + value;
    return round ? Math.round(newPrice * 20) / 20 : newPrice;
}

/**
 * Update the select all, select none and reverse buttons.
 */
function updateButtons() {
    'use strict';
    const disabled = $('#form_allProducts').isChecked();
    const noSelectable = getSelectableProducts().length === 0;
    $('#btn-all, #btn-none, #btn-reverse').toggleDisabled(disabled || noSelectable);
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
        value = $('#form_percent').floatVal();
    } else {
        value = $('#form_fixed').floatVal();
    }
    getProducts().each(function () {
        const $this = $(this);
        const oldPrice = $.parseFloat($this.attr('data-price'));
        const newPrice = computePrice(oldPrice, value, percent, round);
        const text = $.formatFloat(newPrice);
        $this.closest('tr').find('td:eq(2)').text(text);
    });
    updateButtons();
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
    const $allProducts = $('#form_allProducts');

    const $alert = $('#alert');
    const $overFlow = $('#overflow-table');

    // number inputs
    $fixed.inputNumberFormat();

    // add custom method for products selection
    $.validator.addMethod('checkProducts', function () {
        if (getSelectableProducts().length === 0) {
            $overFlow.hide();
            $alert.show(250);
        } else {
            $alert.hide();
            $overFlow.show(250);
        }
        return getSelectedProducts().length !== 0;
    }, $allProducts.data('error'));

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
                checkProducts: true
            }
        },
        submitHandler: function (form) {
            if (isAllProducts()) {
                $('#form_products :checkbox').setChecked(false);
            } else {
                const id = $category.val();
                $('#form_products :checkbox[data-category!="' + id + '"]').setChecked(false);
            }
            $(form).showSubmit({
                text: $('.card-title').text() + '...'
            });
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
        // update visibility
        const id = $(this).val();
        $('#form_products tbody tr[data-category="' + id + '"]').removeClass('d-none')
            .find(':checkbox').addClass('selectable');
        $('#form_products tbody tr[data-category!="' + id + '"]').addClass('d-none')
            .find(':checkbox').removeClass('selectable');

        // select all if none
        const $products = getProducts(id);
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
    }).on('click', ':checkbox', function () {
        validateProducts();
    });

    $allProducts.on('click', function () {
        const disabled = $(this).isChecked();
        $('#form_products :checkbox').toggleDisabled(disabled);
        $('#form_products tbody tr').toggleClass('text-secondary', disabled);
        updateButtons();
        validateProducts();
    });

    $('#btn-all').on('click', function () {
        getProducts().setChecked(true);
        validateProducts();
    });
    $('#btn-none').on('click', function () {
        getProducts().setChecked(false);
        validateProducts();
    });
    $('#btn-reverse').on('click', function () {
        getProducts().toggleChecked();
        validateProducts();
    });
}(jQuery));
