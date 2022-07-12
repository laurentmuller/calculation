/**! compression tag for ftp-deployment */

/**
 * Apply the selected theme (if any).
 */
function applyTheme() {
    'use strict';

    // get values
    const $form = $('#edit-form');
    const $theme = $('#theme');

    // theme
    const newTheme = $theme.val();
    const oldTheme = $form.data('theme') || '';
    if (newTheme && newTheme !== oldTheme) {
        // get theme option
        const $option = $theme.getSelectedOption();
        const title = $option.text();
        const description = $option.data('description');
        const href = $form.data('asset') + $option.data('css');

        // update and save
        $('#example_name').text(title);
        $('#example_description').text(description);
        $('head link[title][rel="stylesheet"]').attr('href', href);
        $form.data('theme', newTheme);
    }

    // background
    const newBackground = $('#background').val();
    const oldBackground = $form.data('background') || '';
    if (newBackground && newBackground !== oldBackground) {
        // apply and save
        $('.navbar-color').toggleClass(newBackground + ' ' + oldBackground);
        $form.data('background', newBackground);
    }
}

/**
 * Set the default theme (Boostrap dark)
 */
function setDefaultValues() {
    'use strict';

    // set default values
    $('#theme').selectFirstOption();
    $('#background').selectFirstOption();

    // update
    applyTheme();
}

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $('#edit-form').initValidator();

    // bind events
    $('#default').on('click', setDefaultValues);
    $('#theme, #background').on('change', function () {
        $('#edit-form').updateTimer(applyTheme, 400);
    });
}(jQuery));
