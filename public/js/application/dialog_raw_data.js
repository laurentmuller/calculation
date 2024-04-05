/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';
    $('.modal-raw-data .btn-copy').copyClipboard({
        title: $('.card-title:first').text(),
        copySuccess: function (e) {
            $(e.trigger).parents('.modal-raw-data').modal('hide');
        },
        copyError: function (e) {
            $(e.trigger).parents('.modal-raw-data').modal('hide');
        }
    });
    $('.modal.modal-raw-data').on('hide.bs.modal', function () {
        $(this).find('.pre-scrollable').scrollTop(0);
    });
}(jQuery));
