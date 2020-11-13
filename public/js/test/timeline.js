/**! compression tag for ftp-deployment */

///**
// * Update the URL of the given link.
// * 
// * @param {jQuery}
// *            $link - the link to update.
// * 
// * @return {jQuery} the link for method chaining.
// */
//function updateUrl($link) {
//    'use strict';
//
//    const date = $('#date').val();
//    const interval = $('#interval').val();
//    const url = new URL($link.attr('href'));
//
//    const params = url.searchParams;
//    params.set('interval', interval);
//    params.set('date', date);
//
//    $link.attr('href', url.href);
//
//    return $link;
//}
/**
 * Ready function
 */
(function ($) {
    'use strict';

    $('#date').focus();
    $('form#search .btn-submit').on('click', function () {
        const spinner = '<span class="spinner-border spinner-border-sm"></span>';
        $(this).addClass('disabled').html(spinner);
    });

    // $('#date, #interval').on('input', function () {
    // const date = $('#date').val();
    // const interval = $('#interval').val();
    // if (date && interval) {
    // const time = new Date(date);
    // console.log(time.toLocaleDateString());
    // }
    // });

}(jQuery));
