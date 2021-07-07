/**! compression tag for ftp-deployment */

/* globals URLSearchParams */

/**
 * Gets the query input.
 *
 * @returns {jQuery} the input element or null if none.
 */
function getQueryInput() {
    'use strict';

    const $query = $('.form-query-input');
    return $query.length ? $query: null;
}

/**
 * Update hyperlink references within the search value
 */
function updateLinks() {
    'use strict';

    const $query = getQueryInput();
    if ($query) {
        const text = $query.val().clean() || false;
        $('.card a.rowlink-skip').each(function () {
            const $this = $(this);
            const parts = $this.attr('href').split('?');
            const params = new URLSearchParams(parts.length === 2 ? parts[1] : '');

            // check if update is needed
            if (!text && !params.has("query")) {
                return false;
            }

            // update parameters
            if (text) {
                params.set('query', text);
            } else if (params.has("query")) {
                params.delete("query");
            }

            // update href
            $this.attr('href', parts[0] + params.toQuery());
        });
    }
}

/**
 * Update filter text.
 */
function updateCounter() {
    'use strict';

    if ($('#counter').length) {
        const total = $('.card-flex').length;
        const visible = $('.card-flex:visible').length;
        $('#nomatch').toggleClass('d-none', visible !== 0);
        $('#counter').toggleClass('d-none', visible === 0);

        if (visible > 0) {
            let text = $('#nomatch').data('filter');
            text = text.replace('{0}', visible).replace('{1}', total);
            $('#counter').text(text);
        }
    }
}

/**
 * Upate counter and hyperlinks.
 *
 * @returns
 */
function updateUI() {
    'use strict';

    updateCounter();
    updateLinks();
}

/**
 * Show all items.
 */
function showAll() {
    'use strict';

    const $elements = $('.card-flex.d-none');
    if ($elements.length) {
        $elements.removeClass('d-none');
        updateUI();
    }
}

/**
 * Handles the query text change.
 */
function onQueryChange() {
    'use strict';

    const query = getQueryInput().val().clean();
    if (query.length) {
        const selector = '.data-search:icontains(' + query + ')';
        $('.card-flex').addClass('d-none').has(selector).removeClass('d-none');
        updateUI();
    } else {
        showAll();
    }
}

/**
 * Ready function.
 */
(function ($) {
    'use strict';

    // bind query input
    const $query = getQueryInput();
    if ($query) {
        $query.on('input', function () {
            $query.updateTimer(onQueryChange, 250);
        });
        $('.btn-query-clear').on('click', function () {
            $query.val('').selectFocus();
            showAll();
        });
    }

    // show selection or set focus
    const $selection = $('.card-flex.border-primary:visible');
    if ($selection.length) {
        $selection.scrollInViewport().timeoutToggle('border-primary');
    } else {
        $('.card-flex.border-primary').removeClass('border-primary');
        if ($query) {
            $query.selectFocus();
        }
    }

    // UI
    updateUI();
}(jQuery));
