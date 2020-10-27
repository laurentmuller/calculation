/**! compression tag for ftp-deployment */

///**
// * Update the URL of the given link.
// * 
// * @param {JQuery}
// *            $link - the link to update.
// * 
// * @return {JQuery} the link for method chaining.
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
    // $('#date, #interval').on('input', function () {
    // updateUrl($('.btn-previous'));
    // updateUrl($('.btn-next'));
    //
    // });
}(jQuery));
