/**! compression tag for ftp-deployment */

/**
 * Apply the selected theme (if any).
 */
function applyTheme() {
    'use strict';

    // get values
    const $form = $('#edit-form');
    const $theme = $('#theme');
    const theme = $theme.val();
    const background = $('#background').val();

    // need update?
    if (theme && background && (theme !== $form.data('theme') || background !== $form.data('background'))) {
        // get theme option
        const $option = $theme.getSelectedOption();
        const title = $option.text();
        const description = $option.data('description');
        const href = $form.data('asset') + $option.data('css');

        // update link
        $('head link[title][rel="stylesheet"]').attr('href', href);

        // update texts
        $('#example_name').text(title);
        $('#example_description').text(description);

        // update toolbar
        $('#navigation').setClass('navbar navbar-expand-md ' + background);

        // update vertical toolbar
        $('.navbar-vertical.navbar').setClass('navbar-vertical navbar ' + background);

        // save values
        $form.data('theme', theme).data('background', background);
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
