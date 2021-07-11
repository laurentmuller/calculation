/**! compression tag for ftp-deployment */

/**
 * Gets the CSS transform for the given value.
 *
 * @param {number}
 *            value - the current value used to transform.
 * @param {number}
 *            maximum - the maximum allowed value.
 * @returns {string} the CSS transform to apply.
 */
function getTimeTransform(value, maximum) {
    'use strict';
    const rotate = value * 360 / maximum;
    return 'translate(-50%, -100%) rotate(' + rotate + 'deg)';
}

/**
 * Update UI.
 */
function updateTime() {
    'use strict';
    const date = new Date();
    const hours = date.getHours() % 12;
    const minutes = date.getMinutes();
    const seconds = date.getSeconds();

    $('.clock-container .hour').css('transform', getTimeTransform(hours, 11));
    $('.clock-container .minute').css('transform', getTimeTransform(minutes, 59));
    $('.clock-container .second').css('transform', getTimeTransform(seconds, 59));
    $('.clock-container .date').html(date.toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }));
    $('.clock-container .time').html(date.toLocaleTimeString(undefined, {
        timeStyle: 'short'
    }));
}

/**
 * Update the clock theme.
 *
 * @param {boolean}
 *            dark - true for dark theme, false for default (light).
 */
function updateTheme(dark) {
    'use strict';
    const color = dark ? 'light' : 'dark';
    $('.clock-container .hour, .clock-container .minute').toggleClass('bg-light bg-dark');
    $('.clock-container .time, .clock-container .date').toggleClass('text-light', dark);
    $('.clock-container').toggleClass('bg-dark', dark).toggleClass('border', !dark);
    $('.clock-container .center-point').css('--center-color', 'var(--' + color + ')');

    // save
    const url = $('.clock-container').data('url');
    if (url) {
        $.post(url, {
            clock_dark: dark
        });
    }
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    updateTime();
    setInterval(updateTime, 1000);
    $('#dark').on('click', function () {
        updateTheme($(this).isChecked());
    });
}(jQuery));
