/**! compression tag for ftp-deployment */

/**
 * Gets the selection filter.
 * 
 * @param {string}
 *            prefix - the selector prefix.
 * @param {string}
 *            separator - the filter separator.
 * @param {function}
 *            filter - the callback filter
 * @returns {string} the filter.
 */
function getSelection(prefix, separator, filter) {
    'use strict';

    // run over cards
    const result = [];
    $(".card").each(function () {
        const $this = $(this);
        const key = $this.data("key");
        const type = $this.data("type");
        const border = $this.data("border");
        if (key && border && type && filter($this, key, type, border)) {
            result.push(prefix + key);
        }
        return true;
    });

    return result.join(separator);
}

/**
 * Gets the hidden card filter.
 * 
 * @returns {string} the filter.
 */
function getHiddenCards() {
    'use strict';

    // calback
    const $callback = function ($this, key, type, border) {
        return !$this.hasClass(border);
    };

    // get selection
    return getSelection("tr.", ",", $callback);
}

/**
 * Gets the hidden levels filter.
 * 
 * @returns {string} the filter.
 */
function getHiddenLevels() {
    'use strict';

    // calback
    const $callback = function ($this, key, type, border) {
        return type === 'level' && !$this.hasClass(border);
    };

    // get selection
    return getSelection('', '|', $callback);
}

/**
 * Gets the hidden channels filter.
 * 
 * @returns {string} the filter.
 */
function getHiddenChannels() {
    'use strict';

    // calback
    const $callback = function ($this, key, type, border) {
        return type === 'channel' && !$this.hasClass(border);
    };

    // get selection
    return getSelection('', '|', $callback);
}

/**
 * Updates the rows visibility.
 */
function updateRows() {
    'use strict';

    // show all
    $("#logs tbody tr").show();

    // hide selection
    const hidden = getHiddenCards();
    if (hidden) {
        const selector = "#logs tbody " + hidden;
        $(selector).hide();
    }
}

/**
 * Updates the card count.
 */
function updateCounters() {
    'use strict';

    $(".card").each(function () {
        const $this = $(this);
        const key = $this.data("key");
        const border = $this.data("border");
        if (!key || !border) {
            return true;
        }

        let count = 0;
        if ($this.hasClass(border)) {
            count = $('#logs tbody tr.' + key + ':visible').length;
        }
        const total = $("#logs tbody tr." + key).length;
        if (count === total) {
            $this.find("h4").removeClass('text-muted').html(count);
        } else if (count !== 0) {
            $this.find("h4").removeClass('text-muted').html(count + "/" + total);
        } else {
            $this.find("h4").addClass('text-muted').html(count + "/" + total);
        }
    });

    // overall counter
    const $total = $("#overall_total");
    const overall = $("#logs tbody tr").length;
    const visible = $("#logs tbody tr:visible").length;
    let text = $total.data("text");
    if (visible === overall) {
        text = text.replace("%count%", overall);
    } else {
        text = text.replace("%count%", visible + "/" + overall);
    }
    $total.text(text);
}

/**
 * Updates the export PDF button.
 */
function updateButton() {
    'use strict';

    // update button state
    const $button = $(".btn-pdf");
    const count = $('#logs tbody tr:visible').length;
    $button.toggleDisabled(count === 0);

    // update button link
    const $params = $("#parameters");
    const href = $params.data("href");
    const limit = $params.data("limit");

    let target = href + "?limit=" + limit;
    const levels = getHiddenLevels('', '|');
    if (levels) {
        target += "&levels=" + levels;
    }
    const channels = getHiddenChannels('', '|');
    if (channels) {
        target += "&channels=" + channels;
    }
    $button.attr('href', target);
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $("#cards").on("click", ".card", function () {
        const $this = $(this);
        const key = $this.data("key");
        const border = $this.data("border");
        if (key && border) {
            $this.toggleClass(border);
            updateRows();
            updateButton();
            updateCounters();
        }
    });
}(jQuery));
