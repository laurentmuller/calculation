/* globals MenuBuilder */

(function ($) {
    'use strict';

    // jQuery extensions
    $.fn.extend({

        /**
         * Gets the context menu items.
         *
         * @return {Object<string, Object>}
         */
        getContextMenuItems: function () {
            const $row = $(this).getParentRow();
            const $elements = $row.find('.dropdown-menu').children();
            return new MenuBuilder({
                elements: $elements
            }).getItems();
        }

    });

    /**
     * Ready function
     */
    $(function () {
        // context menu
        const show = function () {
            $.hideDropDownMenus();
            $(this).getParentRow().addClass('table-primary');
        };
        const hide = function () {
            $(this).getParentRow().removeClass('table-primary');
        };
        $('body').initContextMenu('.row-package td:not(.cell-dropdown)', show, hide);
    });
}(jQuery));
