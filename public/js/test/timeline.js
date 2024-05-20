/**! compression tag for ftp-deployment */

/**
 * Update the user-interface.
 * @param {Object} response
 */
function updateUI(response) {
    'use strict';
    const $date = $('#date');
    const min = $date.attr('min');
    const max = $date.attr('max');
    $date.val(response.date);

    const isMinValue = response.from <= min;
    $('.btn-timeline-first').toggleDisabled(isMinValue);
    $('.btn-timeline-previous').toggleDisabled(isMinValue)
        .data('date', response.previous);

    const isMaxValue = response.to >= max;
    $('.btn-timeline-next').toggleDisabled(isMaxValue)
        .data('date', response.next);
    $('.btn-timeline-last').toggleDisabled(isMaxValue);
}

/**
 * Load the content for the given URL and parameters.
 * @param {URL} url
 * @param {Object.<string, string>} parameters
 */
function updateContent(url, parameters) {
    'use strict';
    for (const [key, value] of Object.entries(parameters)) {
        url.searchParams.append(key, value);
    }
    $.getJSON(url.toString(), function (response) {
        if (response.result && response.content) {
            $('#content').html(response.content);
            updateUI(response);
        }
    });
}

/**
 * Load the content for the selected date and interval.
 */
function updateInput() {
    'use strict';
    const url = new URL($('#content').data('url'));
    updateContent(url, {
        date: $('#date').val(),
        interval: $('#interval').val()
    });
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $('#date').on('input', function (e) {
        const $date = $(this);
        const value = $date.val();
        const min = $date.attr('min');
        if (value < min) {
            e.preventDefault();
            $date.val(min);
            return;
        }
        const max = $date.attr('max');
        if (value > max) {
            e.preventDefault();
            $date.val(max);
            return;
        }
        $date.updateTimer(function () {
            updateInput();
        }, 750);
    });

    $('#interval').on('input', function () {
        $(this).updateTimer(function () {
            updateInput();
        }, 750);
    });

    $('.btn-timeline-first, .btn-timeline-last, .btn-timeline-today').on('click', function () {
        const url = new URL($(this).data('url'));
        updateContent(url, {
            interval: $('#interval').val()
        });
    });

    $('.btn-timeline-previous, .btn-timeline-next').on('click', function () {
        const date = $(this).data('date');
        if (date) {
            const url = new URL($(this).data('url'));
            updateContent(url, {
                date: date,
                interval: $('#interval').val()
            });
        }
    });

}(jQuery));
