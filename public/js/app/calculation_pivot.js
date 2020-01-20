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

    const data = $pivot.data("wholly");
    const enabled = $source.isChecked();
    if (enabled) {
        if (!data) {
            $pivot.wholly({
                rowSelector: 'tr:not(.skip)',
                selection: 'td:not(".not-hover"), th:not(".not-hover")',
                highlightHorizontal: 'table-primary',
                highlightVertical: 'table-primary'
            });
        }
    } else {
        if (data) {
            data.destroy();
        }
    }
}

/**
 * Toogle the popover enablement.
 * 
 * @param {JQuery}
 *            $selector - The popover elements.
 */
function toggleTooltip($source, $selector) {
    'use strict';

    const enabled = $source.isChecked();
    if (enabled) {
        $selector.popover('enable');
    } else {
        $selector.popover('disable');
    }
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    // table
    const $pivot = $('#pivot');

    // bind events
    const selection = 'td:not(".not-hover"), th:not(".not-hover")';
    $pivot.on('mouseenter', selection, function () {
        $(this).addClass('text-hover');
    }).on('mouseleave', selection, function () {
        $(this).removeClass('text-hover');
    });

    // popover
    const $selector = $('[data-toggle="popover"]');
    $selector.customPopover({
        html: true,
        type: 'primary',
        trigger: 'hover',
        placement: 'top',
        container: 'body',
        title: $('.card-title').html().replace(/<h1.*>.*?<\/h1>/ig, ''),
        content: function () {
            const data = $(this).data("html");
            return $(data);
        },
    });
    $('#tooltip').on('input', function () {
        toggleTooltip($(this), $selector);
    });

    // highlight
    $('#highlight').on('input', function () {
        toggleHighlight($(this), $pivot);
    });
});
