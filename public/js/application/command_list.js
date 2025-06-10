/* globals Toaster */

function getDataContent() {
    'use strict';
    return $('#content [data-content');
}

/**
 * @return {string}
 */
function getCommandName() {
    'use strict';
    return $('#command').val();
}

function disposePopover() {
    'use strict';
    getDataContent().popover('dispose');
}

function createPopover() {
    'use strict';
    getDataContent().popover({
        html: true,
        trigger: 'hover',
        placement: 'top',
        customClass: 'popover-w-100',
        content: function (e) {
            const $content = $(e).data('content');
            return $($content);
        }
    });
}

function updateExecute() {
    'use strict';
    const name = getCommandName();
    const $execute = $('.btn-execute');
    const href = $execute.data('url').replace('query', name);
    $execute.attr('href', href);
}

/**
 * Load the command data.
 */
function loadContent() {
    'use strict';
    disposePopover();
    const $content = $('#content');
    const $execute = $('.btn-execute');
    const name = getCommandName();
    const url = $('#command').data('url').replace('query', name);
    $.get(url, function (response) {
        if (response.result) {
            // update
            $execute.fadeIn();
            $content.replaceWith(response.content).fadeIn();
            const url = new URL(location);
            url.searchParams.set('name', name);
            window.history.pushState({'name': name}, '', url);
            updateExecute();
            createPopover();
            return;
        }
        // show error
        $content.fadeOut();
        $execute.fadeOut();
        const title = $('.card-title').text();
        Toaster.notify(Toaster.NotificationTypes.DANGER, response.message, title);
    });
}

/**
 * Ready function
 */
$(function () {
    'use strict';
    const $command = $('#command');
    $command.on('input', function () {
        $command.createTimer(loadContent, 350);
    }).trigger('focus');
    updateExecute();
    createPopover();
});
