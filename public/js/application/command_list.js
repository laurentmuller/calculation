/* globals Toaster */

/**
 * @typedef QueryParams
 * @type {object}
 * @property {string} name - the command name.
 * @property {boolean} arguments - the expanded arguments state.
 * @property {boolean} options - the expanded options state.
 * @property {boolean} help - the expanded help state.
 */

/**
 * @return {jQuery|any}
 */
function getDataContent() {
    'use strict';
    return $('#content [data-content');
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
 * @return {QueryParams}
 */
function getParams() {
    'use strict';
    return {
        name: $('#command').val(),
        arguments: isShow('arguments'),
        options: isShow('options'),
        help: isShow('help')
    };
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
 * @param {URL} url
 * @param {QueryParams} [params]
 * @return {URL}
 */
function updateParams(url, params) {
    'use strict';
    params = params || getParams();
    const searchParams = url.searchParams;
    for (const key of searchParams.keys()) {
        searchParams.delete(key);
    }
    searchParams.set('name', params.name);
    searchParams.set('arguments', toString(params.arguments));
    searchParams.set('options', toString(params.options));
    searchParams.set('help', toString(params.help));
    return url;
}

function updateExecute() {
    'use strict';
    const $execute = $('#execute');
    const href = $execute.attr('href');
    const url = updateParams(new URL(href, location.origin));
    $execute.attr('href', url.pathname + url.search);
}

/**
 * @param {boolean} replace
 */
function updateHistory(replace) {
    'use strict';
    const params = getParams();
    const url = updateParams(new URL(location), params);
    if (replace) {
        window.history.replaceState({'command': params.name}, '', url);
    } else {
        window.history.pushState({'command': params.name}, '', url);
    }
    updateExecute();
}

/**
 * @param {jQuery|any} $element
 */
function saveCollapse($element) {
    'use strict';
    const id = $element.attr('id');
    const value = $element.hasClass('show');
    $('#content').data(id, value);
    updateHistory(true);
}

function notifyError(message) {
    'use strict';
    const title = $('.card-title').text();
    Toaster.notify(Toaster.NotificationTypes.DANGER, message, title);
    $('#execute').fadeOut();
    $('#content').fadeOut();
}

/**
 * Load the command data.
 */
function loadContent() {
    'use strict';
    disposePopover();
    const url = $('#command').data('url');
    const params = getParams();
    $.getJSON(url, params, function (response) {
        if (!response.result) {
            notifyError(response.message || $('#command').data('error'));
            return;
        }
        $('#execute').fadeIn();
        $('#content').replaceWith(response.content)
            .data(params).fadeIn();
        updateHistory(false);
        updateExecute();
        createPopover();
    }).fail(function () {
        notifyError($('#command').data('error'));
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
    createPopover();

    // handle collapse change
    $('#content').parent().on('shown.bs.collapse hidden.bs.collapse', '.collapse', function () {
        saveCollapse($(this));
    });

    // handle pop state change
    window.addEventListener('popstate', (e) => {
        // console.log(`location: ${document.location}, state: ${JSON.stringify(e.state)}`);
        if (e.state && e.state.command) {
            document.location.reload();
        }
    });
});
