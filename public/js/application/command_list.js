/* globals Toaster */

/**
 * @return {jQuery|any}
 */
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
        content: function (element) {
            return $(element.dataset.content);
        }
    });
}

/**
 * @param {string} id
 * @return {boolean}
 */
function isShow(id) {
    'use strict';
    const $element = $('#' + id);
    if ($element.length) {
        return $element.hasClass('show');
    }
    return $('#content').data(id) || false;
}

/**
 * @return {{name: string, help: boolean, options: boolean, arguments: boolean}}
 */
function getParams() {
    'use strict';
    return {
        name: getCommandName(),
        help: isShow('help'),
        options: isShow('options'),
        arguments: isShow('arguments'),
    };
}

function updateExecute() {
    'use strict';
    const name = getCommandName();
    const $execute = $('.btn-execute');
    const href = $execute.data('url').replace('query', name);
    $execute.attr('href', href);
}

/**
 * @param {boolean} value
 * @return {string}
 */
function toString(value) {
    'use strict';
    return Number(value).toString();
}

/**
 * @param {boolean} replace
 */
function updateHistory(replace) {
    'use strict';
    const params = getParams();
    const url = new URL(location);
    const searchParams = url.searchParams;
    searchParams.set('name', params.name);
    searchParams.set('arguments', toString(params.arguments));
    searchParams.set('options', toString(params.options));
    searchParams.set('help', toString(params.help));
    if (replace) {
        window.history.replaceState({'name': params.name}, '', url);
    } else {
        window.history.pushState({'name': params.name}, '', url);
    }
}

/**
 * Load the command data.
 */
function loadContent() {
    'use strict';
    disposePopover();
    const $content = $('#content');
    const $execute = $('.btn-execute');
    const params = getParams();
    const url = $('#command').data('url') + '?' + $.param(params);
    $.get(url, function (response) {
        if (response.result) {
            // update
            $execute.fadeIn();
            $content.replaceWith(response.content).fadeIn()
                .data(params);
            updateHistory(false);
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
        $command.updateTimer(loadContent, 350);
    }).trigger('focus');
    updateExecute();
    createPopover();

    // save collapse state
    const $content = $('#content');
    $content.on('shown.bs.collapse', '.collapse', function () {
        const id = $(this).attr('id');
        $content.data(id, true);
        updateHistory(true);
    }).on('hidden.bs.collapse', '.collapse', function () {
        const id = $(this).attr('id');
        $content.data(id, false);
        updateHistory(true);
    });
});
