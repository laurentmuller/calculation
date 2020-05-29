/**! compression tag for ftp-deployment */

/* globals MenuBuilder  */

/**
 * -------------- JQuery extensions --------------
 */
$.fn.extend({

    /**
     * Gets the parent row.
     * 
     * @returns {JQuery} - The parent row.
     */
    getParentRow: function () {
        'use strict';

        return $(this).parents('tr:first');
    },

    /**
     * Creates the context menu items.
     * 
     * @returns {Object} the context menu items.
     */
    getContextMenuItems: function () {
        'use strict';

        const builder = new MenuBuilder();
        $(this).getParentRow().find('.dropdown-menu').children().each(function () {
            const $this = $(this);
            if ($this.hasClass('dropdown-divider')) {
                builder.addSeparator();
            } else if ($this.isSelectable()) { // .dropdown-item
                builder.addItem($this);
            }
        });

        return builder.getItems();
    }
});

/**
 * Ready function
 */
$(function () {
    'use strict';

    // selection
    const $selection = $(".card-last.border-primary");
    if ($selection.length) {
        $selection.scrollInViewport().timeoutToggle('border-primary');
    }

    // context menu
    const $table = $('#calculations');
    if ($table.length) {
        const show = function () {
            $('.dropdown-menu.show').removeClass('show');
            $(this).parent().addClass('table-primary');
        };
        const hide = function () {
            $(this).parent().removeClass('table-primary');
        };
        const selector = '#calculations tbody tr td:not(.d-print-none)';
        $table.initContextMenu(selector, show, hide);

    }

    // tooltip
    if ($table.length) {
        $('body').customTooltip({
            selector: '.has-tooltip',
            type: 'danger overall-datatable'
        });
    } else {
        $('body').customTooltip({
            selector: '.has-tooltip',
            type: 'danger overall-card'
        });
    }
});