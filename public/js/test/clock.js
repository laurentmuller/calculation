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
 *
 * @param {DateTimeFormat}
 *            dateFormat - the format used for the date.
 * @param {DateTimeFormat}
 *            timeFormat - the format used for the time.
 */
function updateTime(dateFormat, timeFormat) {
    'use strict';
    const date = new Date();
    const hours = date.getHours() % 12;
    const minutes = date.getMinutes();
    const seconds = date.getSeconds();
    $('.clock-container .hour').css('transform', getTimeTransform(hours, 11));
    $('.clock-container .minute').css('transform', getTimeTransform(minutes, 59));
    $('.clock-container .second').css('transform', getTimeTransform(seconds, 59));
    $('.clock-container .date').html(dateFormat.format(date));
    $('.clock-container .time').html(timeFormat.format(date));
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
            dark: dark
        });
    }
}

/**
 * Initialize the clock.
 */
function initTime() {
    'use strict';
    const lang = navigator.language;
    const dateFormat = new Intl.DateTimeFormat(lang, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    const timeFormat = new Intl.DateTimeFormat(lang, {
        timeStyle: 'short'
    });
    updateTime(dateFormat, timeFormat);
    setInterval(updateTime, 1000, dateFormat, timeFormat);
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    initTime();
    const $button = $('#dark');
    if ($button.length) {
        $('#dark').on('click', function () {
            updateTheme($(this).isChecked());
        });
    }
}(jQuery));
