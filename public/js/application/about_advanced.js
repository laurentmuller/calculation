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
            $(this).getParentRow().addClass('table-primary');
        };
        const hide = function () {
            $(this).getParentRow().removeClass('table-primary');
        };
        const $container = $('#aboutAccordion');
        $container.initContextMenu('.row-package td:not(.rowlink-skip)', show, hide);

        // row link
        $container.rowlink({
            target: '.link-license, .link-homepage, .link_source, .link-package'
        });
    });
}(jQuery));
