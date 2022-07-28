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
 * Set the default theme (Boostrap dark) and submit form.
 */
function setDefaultValues() {
    'use strict';
    $('#theme').selectFirstOption();
    $('#background').selectFirstOption();
    $('#default').toggleDisabled(true);
    $('#edit-form').trigger('submit');
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('#edit-form').initValidator();
    $('#default').on('click', function () {
        setDefaultValues();
    });
    $('#theme, #background').on('change', function () {
        $('#edit-form').updateTimer(applyTheme, 400);
    });
}(jQuery));
