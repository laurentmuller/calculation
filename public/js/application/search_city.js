/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $form = $("form");
    // const $unit = $('#form_units');
    // const $query = $('#form_query');
    // // submit form when units change
    // $unit.data('value', $unit.val()).on('input', function () {
    //     $unit.createTimer(function () {
    //         $unit.removeTimer();
    //         const newValue = $unit.val();
    //         const oldValue = $unit.data('value');
    //         if (newValue !== oldValue && $query.val().trim().length >= 2) {
    //             $form.trigger('submit');
    //         }
    //         $unit.data('value', newValue);
    //     }, 500);
    // });

    // validation
    $form.initValidator({
        spinner: {
            text: $('.card-title').text() + '...',
        }
    });
}(jQuery));
