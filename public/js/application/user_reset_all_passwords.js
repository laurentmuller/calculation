/**
 * Gets all checkboxes.
 * @return {jQuery}
 */
function getAllCheckboxes() {
    'use strict';
    return $('#edit-form .form-check-input');
}

/**
 * Updates the UI.
 */
function updateUI() {
    'use strict';
    let checked = $('#edit-form .form-check-input:checked').length;
    let unchecked = $('#edit-form .form-check-input:not(:checked)').length;
    $('#all').toggleDisabled(unchecked === 0);
    $('#none').toggleDisabled(checked === 0);
}

/**
 * Validates the form.
 */
function validate() {
    'use strict';
    $('#edit-form').validateForm();
    updateUI();
}

/**
 * Ready function
 */
$(function () {
    'use strict';
    const $form = $('#edit-form');
    $form.initValidator({
        focus: false,
        showModification: false,
        rules: {
            'form[users][]': {
                // eslint-disable-next-line camelcase
                require_from_group: [1, '.form-check-input']
            }
        },
        messages: {
            'form[users][]': $form.data('error')
        }
    });
    $('#all').on('click', function () {
        getAllCheckboxes().setChecked(true);
        validate();
    });
    $('#none').on('click', function () {
        getAllCheckboxes().setChecked(false);
        validate();
    });
    $('#toggle').on('click', function () {
        getAllCheckboxes().toggleChecked();
        validate();
    });
    getAllCheckboxes().on('change', function () {
        validate();
    });
    updateUI();
});
