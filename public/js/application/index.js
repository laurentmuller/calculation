/**! compression tag for ftp-deployment */

/* globals MenuBuilder  */

/**
 * -------------- jQuery extensions --------------
 */
$.fn.extend({

    /**
     * Creates the context menu items.
     *
     * @returns {Object} the context menu items.
     */
    getContextMenuItems: function () {
        'use strict';
        const $elements = $(this).parents('tr:first').find('.dropdown-menu').children();
        return (new MenuBuilder()).fill($elements).getItems();
    }
});

function onRestrictChange($restrict) {
    'use strict';
    const newValue = $restrict.isChecked();
    const oldValue = $restrict.data('value');
    if (newValue !== oldValue) {
        const url = $restrict.data('url') + '?restrict=' + (newValue ? '1' : '0');
        window.location.assign(url);
    }
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    // remove selection
    const $table = $('#calculations');
    if ($table.length) {
        // show selection
        const $row = $('#calculations tr.table-primary');
        if ($row.length) {
            $row.scrollInViewport().timeoutToggle('table-primary');
        }

        // enable tooltips
        $table.tooltip({
            selector: '.has-tooltip',
            customClass: 'tooltip-danger overall-datatable'
        });

        // initialize context menu
        const show = function () {
            $table.find('tr.table-primary').removeClass('table-primary');
            $('.dropdown-menu.show').removeClass('show');
            $(this).parent().addClass('table-primary');
        };
        const hide = function () {
            $(this).parent().removeClass('table-primary');
        };
        const selector = '#calculations tbody tr td:not(.d-print-none)';
        $table.initContextMenu(selector, show, hide);
    }

    // enable tooltips for calculations by state or by month
    $('.card-body-tootlip').tooltip({
        selector: '.has-tooltip',
        customClass: 'tooltip-danger'
    });

    // user restrict
    const $restrict = $('#restrict');
    if ($restrict.length) {
        $restrict.on('input', function () {
            $restrict.updateTimer(onRestrictChange, 450, $restrict);
        });
    }

}(jQuery));
