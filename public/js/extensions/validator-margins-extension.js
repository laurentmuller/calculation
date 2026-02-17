/* exported addMarginsMethods */

/**
 * The last error data key.
 *
 * @type {string}
 */
const KEY_MARGIN_ERROR = 'margin-error';

/**
 * The validating margins state.
 *
 * @type {string}
 */
const KEY_MARGIN_VALIDATE = 'margin-validating';

/**
 * Returns if the validation of margins is in progress.
 *
 * @param {jQuery<HTMLInputElement>} $element
 * @return {boolean} true if in progress.
 */
function isMarginValidate($element) {
    'use strict';
    return $element.parents('table').data(KEY_MARGIN_VALIDATE);
}

/**
 * Sets a value indicating validation of margins is in progress.
 *
 * @param {jQuery<HTMLInputElement>} $element
 * @param {boolean} value - true if in progress.
 */
function setMarginValidate($element, value) {
    'use strict';
    $element.parents('table').data(KEY_MARGIN_VALIDATE, value);
}

/**
 * Gets the minimum input selector.
 *
 * @return {string}
 */
function getMinimumSelector() {
    'use strict';
    return 'input[name$="[minimum]"]';
}

/**
 * Gets the maximum input selector.
 *
 * @return {string}
 */
function getMaximumSelector() {
    'use strict';
    return 'input[name$="[maximum]"]';
}

/**
 * Gets minimum value input.
 *
 * @param {HTMLTableRowElement} row
 * @return number
 */
function getMinimumInput(row) {
    'use strict';
    return $(row).find(getMinimumSelector()).floatVal();
}

/**
 * Gets maximum value input.
 *
 * @param {HTMLTableRowElement} row
 * @return number
 */
function getMaximumInput(row) {
    'use strict';
    return $(row).find(getMaximumSelector()).floatVal();
}

/**
 * Gets the rows for the given element
 * @param {jQuery<HTMLInputElement>} $element the element to get rows from.
 * @return {jQuery} the rows.
 */
function getBodyRows($element) {
    'use strict';
    return $element.closest('tbody').children('tr');
}

/**
 * Gets the sorted rows by first the minimum and if equal, then by the maximum values.
 *
 * @param {jQuery<HTMLInputElement>} $element the element to get rows from.
 * @return {jQuery} the sorted rows.
 */
function getSortedMargins($element) {
    'use strict';
    const $rows = getBodyRows($element);
    if ($rows.length < 2) {
        return $rows;
    }

    return $rows.sortElements(function (rowA, rowB) {
        const result = getMinimumInput(rowA) - getMinimumInput(rowB);
        if (result !== 0) {
            return result;
        }
        return getMaximumInput(rowA) - getMaximumInput(rowB);
    });
}

/**
 * Gets a value indicating if the margins are sorted.
 *
 * @param {jQuery<HTMLInputElement>} $element the element to get rows from.
 * @return {boolean} true if sorted; false otherwise.
 */
function isSortedMargins($element) {
    'use strict';
    const $rows = getBodyRows($element);
    if ($rows.length < 2) {
        return true;
    }
    let sorted = true;
    let lastMinimum = 0;
    let lastMaximum = 0;
    $rows.each(function (index, row) {
        const minimum = getMinimumInput(row);
        const maximum = getMaximumInput(row);
        if (minimum < lastMinimum || maximum < lastMaximum) {
            sorted = false;
            return false;
        }
        lastMinimum = minimum;
        lastMaximum = maximum;
    });
    return sorted;
}

/**
 * Validate all other margins that contain errors.
 *
 * @param {jQuery<HTMLInputElement>} $element
 */
function validateOtherMargins($element) {
    'use strict';
    if (isMarginValidate($element)) {
        return;
    }

    setMarginValidate($element, true);
    const element = $element[0];
    const $validator = $element.parents('form').getValidator();
    const selector = `${getMinimumSelector()}, ${getMaximumSelector()}`;
    const $cells = $element.parents('table').find(selector);
    $cells.each(function () {
        const $cell = $(this);
        if (!$cell.is(element) && $cell.data(KEY_MARGIN_ERROR)) {
            $validator.element($cell);
            return false;
        }
    });
    setMarginValidate($element, false);
}

/**
 * Gets a margin error message.
 *
 * @param {jQuery<HTMLInputElement>} $element
 * @return {string}
 */
function getMarginError($element) {
    'use strict';
    const $form = $element.parents('form');
    const id = $element.data(KEY_MARGIN_ERROR);
    const message = String($form.data(id));
    return message || $.validator.messages.remote;
}

/**
 * Sets or remove the margin error message.
 *
 * @param {jQuery<HTMLInputElement>} $element the element to update.
 * @param {string} [error] the error message to set or null to remove.
 * @return {boolean} false if error message; true if valid.
 */
function setMarginError($element, error) {
    'use strict';
    if (error) {
        $element.data(KEY_MARGIN_ERROR, error);
        if (!isMarginValidate($element)) {
            setTimeout(() => validateOtherMargins($element), 150);
        }
        return false;
    }
    $element.removeData(KEY_MARGIN_ERROR);
    if (!isMarginValidate($element)) {
        setTimeout(() => validateOtherMargins($element), 150);
    }
    return true;
}

/**
 * Validate the minimum margin.
 *
 * @param {jQuery<HTMLInputElement>} $element the element to validate.
 * @return {boolean} false if error; true if valid.
 */
function validateMinimumMargin($element) {
    'use strict';
    const $row = $element.parents('tr');
    const minimum = getMinimumInput($row[0]);
    const maximum = getMaximumInput($row[0]);
    if (minimum > maximum) {
        return setMarginError($element, 'minimum_smaller_maximum');
    }
    // get previous row
    const $rows = getSortedMargins($element);
    const index = $rows.index($row);
    if (index === 0) {
        return setMarginError($element);
    }
    const previousRow = $rows[index - 1];
    const prevMaximum = getMaximumInput(previousRow);
    if (minimum < prevMaximum) {
        return setMarginError($element, 'minimum_overlap');
    }
    if (minimum !== prevMaximum) {
        return setMarginError($element, 'minimum_discontinued');
    }
    return setMarginError($element);
}

/**
 * Validate the maximum margin.
 *
 * @param {jQuery<HTMLInputElement>} $element the element to validate.
 * @return {boolean} false if error; true if valid.
 */
function validateMaximumMargin($element) {
    'use strict';
    const $row = $element.parents('tr');
    const minimum = getMinimumInput($row[0]);
    const maximum = getMaximumInput($row[0]);
    if (maximum <= minimum) {
        return setMarginError($element, 'maximum_greater_minimum');
    }
    // get the next row
    const $rows = getSortedMargins($element);
    const index = $rows.index($row);
    if (index === $rows.length - 1) {
        return setMarginError($element);
    }
    const nextRow = $rows[index + 1];
    const nextMinimum = getMinimumInput(nextRow);
    if (maximum > nextMinimum) {
        return setMarginError($element, 'maximum_overlap');
    }
    if (maximum !== nextMinimum) {
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
