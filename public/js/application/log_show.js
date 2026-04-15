/**
 * Ready function
 */
$(function () {
    'use strict';
    const $context = $('#context');
    if (!$context.length) {
        return;
    }
    const url = $context.data('url');
    const name = $context.data('name');
    $context.on('shown.bs.collapse hidden.bs.collapse', function () {
        const data = {
            name: name,
            value: $context.hasClass('show'),
        };
        $.ajaxSetup({global: false});
        $.post(url, data).always(() => $.ajaxSetup({global: true}));
    });
});
