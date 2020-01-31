/**! compression tag for ftp-deployment */
/* globals sortable */

/**
 * Handle drag start
 */
function dragStart() {
    'use strict';
    console.log('dragStart');
    $('tr.table-success').removeClass('table-success');
}

/**
 * Handle drag stop
 */
function dragStop(e) {
    'use strict';

    const $item = $(e.detail.item);
    const origin = e.detail.origin;
    const destination = e.detail.destination;
    const $bodies = $("#data-table-edit tbody");

    if (origin.container !== destination.container) {
        const oldIndex = $bodies.index($(origin.container));
        const newIndex = $bodies.index($(destination.container));
        console.log("dragStop", "Container changed", oldIndex, newIndex);

        if ($(origin.container).children().length === 1) {
            $(origin.container).fadeOut('slow', function () {
                $(origin.container).remove();
                $item.timeoutToggle('table-success');
            });
        } else {
            $item.timeoutToggle('table-success');
        }
    } else if (origin.index !== destination.index) {
        console.log("dragStop", "Position changed", origin.index, destination.index);
        $item.timeoutToggle('table-success');
    }
}
/**
 * Initialize sortable
 */
function initSortable(destroy) {
    'use strict';

    const selector = "#data-table-edit tbody";

    // remove
    const $bodies = $(selector);
    if (destroy) {
        $bodies.off('sortstart', dragStart);
        $bodies.off('sortupdate', dragStop);
        sortable(selector, 'destroy');
    }

    // add
    $bodies.on('sortstart', dragStart);
    $bodies.on('sortupdate', dragStop);

    // create
    sortable(selector, {
        items: 'tr:not(.drag-skip)',
        handle: 'td',
        draggingClass: null,
        placeholderClass: 'bg-primary',
        forcePlaceholderSize: false,
        acceptFrom: "tbody"
    });
}

/**
 * Document ready function
 */
$(function () {
    'use strict';

    initSortable(false);

    $("#addBody").on('click', function () {
        const $body = $("#data-table-edit > tbody:first").clone();
        $("#data-table-edit").append($body);
        initSortable(true);
    });
});