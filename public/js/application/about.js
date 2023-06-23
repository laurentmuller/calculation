/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    const $configuration = $('#configuration');

    // jQuery extensions
    $.fn.extend({
        loadContent: function () {
            const $this = $(this);
            const url = $this.data('url');
            if (url) {
                $.getJSON(url).done(function (response) {
                    if (response.result && response.content) {
                        $this.html(response.content);
                    } else {
                        $this.showError();
                    }
                }).fail(function () {
                    $this.showError();
                });
            } else {
                $this.showError();
            }
        },

        showError: function () {
            const content = $configuration.data("error");
            const html = "<i class='fas fa-lg fa-exclamation-triangle me-2'></i>" + content;
            $(this).find(".alert:first").addClass("alert-danger").html(html);
        }
    });

    // update title on toggle
    $('#parent_accordion .accordion-item').on('shown.bs.collapse', function () {
        $(this).find('.accordion-button').attr('title', $configuration.data('collapse'));
    }).on('hidden.bs.collapse', function () {
        $(this).find('.accordion-button').attr('title', $configuration.data('expand'));
    });

    // load content on first show
    $('#parent_accordion .accordion-item').one('show.bs.collapse', function () {
        $(this).find('.accordion-body').loadContent();
    });

    // $('#parent_accordion .accordion-item').on('mousedown', 'a', function (e) {
    //     e.stopPropagation();
    //     e.preventDefault();
    //     e.target.click();
    //     // const $target = $(e.target);
    //     // $target.trigger('click');
    //     // if ($target.is('a') || $target.parent().is('a')) {
    //     //     window.console.log($target);
    //     //     // e.preventDefault();
    //     //     e.stopPropagation();
    //     // }
    // });

    // $('#parent_accordion .accordion-item').on('show.bs.collapse', function (e) {
    //     window.console.log('show', e);
    // });
    // $('#parent_accordion .accordion-item').on('click', 'a', function (e) {
    //     window.console.log('click', e);
    // });

    // $("#parent_accordion button.accordion-button").attr("data-bs-toggle", "collapse");
}(jQuery));
