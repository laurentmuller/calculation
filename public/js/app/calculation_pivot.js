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
    $('[data-toggle="popover"]').customPopover({
        html: true,
        type: 'primary',
        trigger: 'hover',
        placement: 'top',
        container: 'body',
        title: $('.card-title').html().replace(/<h1.*>.*?<\/h1>/ig, ''),
        content: function () {
            const html = $(this).data("html");
            return $(html);
        },
    });

    // wholly highlight
    $('#highlight').on('input', function () {
        toggleHighlight($(this), $pivot);
    });
});
