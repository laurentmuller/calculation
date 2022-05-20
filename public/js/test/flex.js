/**! compression tag for ftp-deployment */

/**
 * Gets the number format.
 */
function getNumberFormat(decimal) {
    'use strict';
    return new Intl.NumberFormat('de-CH', {
        'minimumFractionDigits': decimal,
        'maximumFractionDigits': decimal
    });
}

/**
 * Get the group separator.
 */
function getGroup() {
    'use strict';
    const formatter = getNumberFormat(2);
    const parts = formatter.formatToParts(1000);
    const item = parts.find(item => item.type === 'group'); // eslint-disable-line
    return item ? item.value : '';
}


/**
 * Get the decimal separator.
 */
function getDecimal() {
    'use strict';
    const formatter = getNumberFormat(2);
    const parts = formatter.formatToParts(1000);
    const item = parts.find(item => item.type === 'decimal'); // eslint-disable-line
    return item ? item.value : '.';
}


/**
 * Parse input value to a float.
 */
function parse($input) {
    'use strict';
    const group = getGroup();
    const decimal = getDecimal();
    const value = $input.val().replace(group, '').replace(decimal, '.');
    return $.parseFloat(value);
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    const options = {
        onCreateInput: function (e, $input) {
            const index = $input.parents('td').index();
            switch (index) {
            case 0: // id
                $input.val(parse($input)).inputNumberFormat({
                    'decimal': 0
                });
                break;
            case 3: // calculations
                $input.val(parse($input)).inputNumberFormat();
                break;
            }
        },

        onSave: function (e, $input) {
            // text?
            let text = $input.val().trim();
            if (text.length === 0) {
                e.preventDefault();
                return;
            }

            // format
            const numberFormat = $input.data("inputNumberFormat");
            if (numberFormat) {
                numberFormat.update();
                const decimal = numberFormat.options.decimal;
                const formatter = getNumberFormat(decimal);
                text = formatter.format(parseFloat(text));
            }
            $input.val(text);

            // ok
            $input.parents('td').timeoutToggle('table-success');
        },

        onRemoveInput: function (e, $input) {
            const numberFormat = $input.data("inputNumberFormat");
            if (numberFormat) {
                numberFormat.destroy();
            }
        },
    };

    $('#editable').tableEditor(options);

}(jQuery));
