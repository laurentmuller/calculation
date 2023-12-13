/**! compression tag for ftp-deployment */

/**
 * The last error data key.
 * @type {string}
 */
const KEY_LAST_ERROR = 'margin-error';

/**
 * Gets a margin error message.
 *
 * @param {JQuery} $element
 * @return {string}
 */
function getMarginError($element) {
    'use strict';
    const $form = $('#edit-form');
    const id = $element.data(KEY_LAST_ERROR);
    // $element.removeData(KEY_LAST_ERROR);
    const message = String($form.data(id));
    return message || $.validator.messages.remote;
}

/**
 * Sets or remove the margin error message.
 *
 * @param {jQuery} $element the element to update.
 * @param {string} [error] the error message to set or null to remove.
 * @return {boolean} false if error message; true if valid.
 */
function setMarginError($element, error) {
    'use strict';
    if (error) {
        $element.data(KEY_LAST_ERROR, error);
        return false;
    }
    $element.removeData(KEY_LAST_ERROR);
    return true;
}

/**
 * Validate the minimum margin.
 *
 * @param {jQuery} $element the element to validate.
 * @return {boolean} false if error; true if valid.
 */
function validateMinimumMargin($element) {
    'use strict';
    const $row = $element.parents('tr');
    const min = $row.find('input[name$="[minimum]"]').floatVal();
    const max = $row.find('input[name$="[maximum]"]').floatVal();
    if (min > max) {
        return setMarginError($element, 'minimum_smaller_maximum');
    }
    const $previousRow = $row.prev();
    if (!$previousRow.length) {
        return setMarginError($element);
    }
    const prevMax = $previousRow.find('input[name$="[maximum]"]').floatVal();
    if (min < prevMax) {
        return setMarginError($element, 'minimum_overlap');
    }
    if (min !== prevMax) {
        return setMarginError($element, 'minimum_discontinued');
    }
    return setMarginError($element);
}

/**
 * Validate the maximum margin.
 *
 * @param {jQuery} $element the element to validate.
 * @return {boolean} false if error; true if valid.
 */
function validateMaximumMargin($element) {
    'use strict';
    const $row = $element.parents('tr');
    const min = $row.find('input[name$="[minimum]"]').floatVal();
    const max = $row.find('input[name$="[maximum]"]').floatVal();
    if (max <= min) {
        return setMarginError($element, 'maximum_greater_minimum');
    }
    const $nextRow = $row.next();
    if (!$nextRow.length) {
        return setMarginError($element);
    }
    const nextMin = $nextRow.find('input[name$="[minimum]"]').floatVal();
    if (max > nextMin) {
        return setMarginError($element, 'maximum_overlap');
    }
    if (max !== nextMin) {
        return setMarginError($element, 'maximum_discontinued');
    }
    return setMarginError($element);
}

/**
 * Add the margins methods to the validator.
 */
function addMarginsMethods() {
    'use strict';
    $.validator.addMethod('validate-minimum', function (value, element) {
        return this.optional(element) || validateMinimumMargin($(element));
    }, function (value, element) {
        return getMarginError($(element));
    });

    $.validator.addMethod('validate-maximum', function (value, element) {
        return this.optional(element) || validateMaximumMargin($(element));
    }, function (value, element) {
        return getMarginError($(element));
    });
}
