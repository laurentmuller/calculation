/**! compression tag for ftp-deployment */

/**
 * Apply the selected theme (if any).
 */
function applyTheme() {
    'use strict';

    // get values
    const $form = $('#edit-form');
    const theme = $('#theme').val();
    const background = $('#background').val();

    // need update?
    if (theme && background && (theme !== $form.data('theme') || background !== $form.data('background'))) {
        // get theme option
        const $option = $('#theme').getSelectedOption();
        const title = $option.text();
        const description = $option.data('description');

        // update links
        $('link[title]').each(function () {
            const $this = $(this);
            $this.attr('disabled', true);
            if ($this.attr('title') === title) {
                $this.attr('disabled', false);
            }
        });

        // update texts
        $('#example_name').text(title);
        $('#example_description').text(description);

        // update toolbar
        $('#navigation').setClass('navbar navbar-expand-md ' + background);

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

    // submit
    // $('#edit-form').submit();
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    $('#edit-form').initValidator();

    // bind events
    $('#default').on('click', setDefaultValues);
    $('#theme, #background').on('change', function () {
        $('#edit-form').updateTimer(applyTheme, 400);
    });
});
