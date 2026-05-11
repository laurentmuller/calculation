(function ($) {
    'use strict';

    /**
     * The checked menu class.
     */
    const CHECKED_CLASS = 'dropdown-item-checked-right';

    /**
     * Save a value to the user's session.
     * @param {string} name the key name.
     * @param {boolean} value the value to save.
     */
    function saveSession(name, value) {
        const url = $('#pivot').data('session');
        const data = {
            name: name,
            value: value
        };
        $.ajaxSetup({
            global: false
        });
        $.post(url, data).always(() => $.ajaxSetup({
            global: true
        }));
    }

    /**
     * Toggle the cell highlight enablement.
     *
     * @param {jQuery<HTMLLinkElement>} $source - The highlight link.
     * @param {jQuery<HTMLTableElement>} $table - The table to update.
     * @param {boolean} save - true to save value to the session.
     */
    function toggleHighlight($source, $table, save) {
        const checked = $source.hasClass(CHECKED_CLASS);
        const highlight = $table.data('cell-highlight');
        if (checked) {
            if (!highlight) {
                $table.cellhighlight({
                    rowSelector: 'tr:not(.skip)',
                    cellSelector: 'td:not(.not-hover), th:not(.not-hover)',
                    highlightHorizontal: 'table-primary',
                    highlightVertical: 'table-primary'
                });
            } else {
                highlight.enable();
            }
        } else {
            if (highlight) {
                highlight.disable();
            }
        }

        // save to session
        if (save) {
            saveSession('pivot.highlight', checked);
        }
    }

    /**
     * Toggle the popover enablement.
     *
     * @param {jQuery<HTMLLinkElement>} $source - The popover link.
     * @param {jQuery<HTMLTableCellElement>} $selector - The popover elements.
     * @param {boolean} save - true to save value to the session.
     */
    function togglePopover($source, $selector, save) {
        const checked = $source.hasClass(CHECKED_CLASS);
        const enabled = $source.data('enabled');
        if (checked) {
            if (enabled) {
                $selector.popover('enable');
            } else {
                $selector.popover({
                    html: true,
                    trigger: 'hover',
                    placement: 'top',
                    customClass: 'popover-primary popover-w-100',
                    fallbackPlacements: ['top', 'bottom', 'right', 'left'],
                    content: function (e) {
                        const content = $(e).data('bs-html');
                        return $(content);
                    }
                });
                $source.data('enabled', true);
            }
        } else {
            if (enabled) {
                $selector.popover('disable');
            }
        }

        // save to session
        if (save) {
            saveSession('pivot.popover', checked);
        }
    }

    /**
     * Ready function
     */
    $(function () {
        // get elements
        const $table = $('#pivot');
        const $popover = $('#popover');
        const $highlight = $('#highlight');
        const $selector = $('#pivot [data-bs-toggle="popover"]');

        // popover
        if ($popover.hasClass(CHECKED_CLASS)) {
            togglePopover($popover, $selector, false);
        }
        $popover.on('click', function () {
            $popover.toggleClass(CHECKED_CLASS);
            togglePopover($popover, $selector, true);
        });

        // highlight
        if ($highlight.hasClass(CHECKED_CLASS)) {
            toggleHighlight($highlight, $table, false);
        }
        $highlight.on('click', function () {
            $highlight.toggleClass(CHECKED_CLASS);
            toggleHighlight($highlight, $table, true);
        });

        // hover
        $selector.on('mouseenter', function () {
            $(this).addClass('text-hover');
        }).on('mouseleave', function () {
            $(this).removeClass('text-hover');
        });
    });
}(jQuery));
