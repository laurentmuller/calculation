/**
 * Ready function
 */
$(function () {
    'use strict';
    $('.btn-copy').copyClipboard({
        title: $('.card-title').text()
    });
});
