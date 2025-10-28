/**
 * Ready function
 */
$(function () {
    'use strict';
    $('.modal-raw-data .btn-copy').copyClipboard();
    $('.modal.modal-raw-data').on('hide.bs.modal', function () {
        $(this).find('.pre-scrollable').scrollTop(0);
    });
});
