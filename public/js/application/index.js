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
        $('#calculations tbody').customTooltip({
            selector: '.has-tooltip',
            type: 'danger overall-datatable'
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

    } else {
        // show selection
        const $selection = $('.card-last.border-primary');
        if ($selection.length) {
            $selection.scrollInViewport().timeoutToggle('border-primary');
        }

        // enable tooltips
        $('body').customTooltip({
            selector: '.has-tooltip',
            type: 'danger overall-card'
        });
    }
}(jQuery));
