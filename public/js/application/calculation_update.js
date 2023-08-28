/**! compression tag for ftp-deployment */

// /**
//  * @param {jQuery} $input
//  * @return {Date}
//  */
// function getDate($input) {
//     'use strict';
//     return new Date($input.val());
// }
//
// /**
//  * @param {jQuery} $input
//  * @return {Date}
//  */
// function getOldDate($input) {
//     'use strict';
//     return new Date($input.data('value') || $input.val());
// }
//
// /**
//  * @param {jQuery} $input
//  * @param {Date} date
//  */
// function setDate($input, date) {
//     'use strict';
//     const value = date.toISOString().split('T')[0];
//     $input.val(value).data('value', $input.val());
// }
//
// /**
//  * @param {jQuery} $input
//  * @return {number}
//  */
// function getDelta($input) {
//     'use strict';
//     const oldDate = getOldDate($input);
//     const newDate = getDate($input);
//     return newDate - oldDate;
// }

/**
 * Ready function
 */
(function ($) {
    'use strict';
    const $form = $('#edit-form');
    $form.simulate().initValidator({
        rules: {
            'form[states][]': {
                /* eslint camelcase: "off" */
                require_from_group: [1, '#form_states .form-check-input']
            }
        }, messages: {
            'form[states][]': $form.data('error')
        }, spinner: {
            text: $('.card-title').text() + '...'
        }
    });
    $('#form_sources .custom-switch').addClass('me-4');

    // handle dates range
    // const $dateFrom = $('#form_dateFrom');
    // const $dateTo = $('#form_dateTo');
    // $dateFrom.data('value', $dateFrom.val()).on('input', () => {
    //     const delta = getDelta($dateFrom);
    //     const newDate = new Date(getDate($dateTo).getTime() + delta);
    //     $dateFrom.data('value', $dateFrom.val());
    //     setDate($dateTo, newDate);
    // });
    // $dateTo.data('value', $dateTo.val()).on('input', () => {
    //     const delta = getDelta($dateTo);
    //     const newDate = new Date(getDate($dateFrom).getTime() + delta);
    //     $dateTo.data('value', $dateTo.val());
    //     setDate($dateFrom, newDate);
    // });
}(jQuery));
