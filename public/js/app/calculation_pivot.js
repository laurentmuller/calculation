/** ! compression tag for ftp-deployment */

/**
 * Toogle the wholly enablement.
 * 
 * @param {JQuery}
 *            $source - The highlight checkbox.
 * 
 * @param {JQuery}
 *            $pivot - The table to update.
 */
function toggleHighlight($source, $pivot) {
    'use strict';

    const data = $pivot.data('wholly');
    const enabled = $source.isChecked();
    if (enabled) {
        if (!data) {
            $pivot.wholly({
                rowSelector: 'tr:not(.skip)',
                cellSelector: 'td:not(.not-hover), th:not(.not-hover)',
                highlightHorizontal: 'table-primary',
                highlightVertical: 'table-primary'
            });
        } else {
            data.enable();
        }
    } else {
        if (data) {
            data.disable();
        }
    }
}

/**
 * Toogle the popover enablement.
 * 
 * @param {JQuery}
 *            $source - The popover checkbox.
 * 
 * @param {JQuery}
 *            $selector - The popover elements.
 */
function togglePopover($source, $selector) {
    'use strict';

    const enabled = $source.isChecked();
    const data = $selector.data('bs.popover');
    if (enabled) {
        if (data) {
            $selector.popover('enable');
        } else {
            $selector.customPopover({
                html: true,
                type: 'primary',
                trigger: 'hover',
                placement: 'top',
                container: 'body',
                title: $('.card-title').html().replace(/<h1.*>.*?<\/h1>/ig, ''),
                content: function () {
                    const data = $(this).data('html');
                    return $(data);
                },
            });
        }
    } else {
        if (data) {
            $selector.popover('disable');
        }
    }
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    // get elements
    const $pivot = $('#pivot');
    const $popover = $('#popover');
    const $highlight = $('#highlight');
    const $selector = $('[data-toggle="popover"]');

    // popover
    if ($popover.isChecked()) {
        togglePopover($popover, $selector);
    }
    $popover.on('input', function () {
        togglePopover($(this), $selector);
    });

    // highlight
    if ($highlight.isChecked()) {
        toggleHighlight($highlight, $pivot);
    }
    $highlight.on('input', function () {
        toggleHighlight($(this), $pivot);
    });

    // selection
    const selection = 'td:not(.not-hover), th:not(.not-hover)';
    $pivot.on('mouseenter', selection, function () {
        $(this).addClass('text-hover');
    }).on('mouseleave', selection, function () {
        $(this).removeClass('text-hover');
    });
});
