/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Show the flashbag messages.
 */
function showFlashbag() {
    'use strict';
    // get first element (if any)
    const $element = $('.flashbag:first');
    if ($element.length) {
        // options
        const options = $("#flashbags").data();
        const title = options.title ? $element.data('title') : null;
        const text = $element.text();
        const type = $element.data('type');

        // remove
        $element.remove();

        // display
        if (text) {
            Toaster.notify(type, text, title, options);
        }

        // show next
        if ($('.flashbag').length) {
            setTimeout(function () {
                showFlashbag();
            }, 1500);
        }
    }
}

/**
 * Handle toolbar search.
 */
function initSearchToolbar() {
    'use strict';
    // search form?
    const $form = $("#navigation-search-form");
    if ($form.length === 0) {
        return;
    }

    const $button = $("#navigation-search-form");
    const $search = $("#navigation-search-form #search");

    $.fn.extend({
        hideInvalid: function () {
            return $(this).removeClass('is-invalid').tooltip('dispose');
        }
    });

    const hideForm = function () {
        $search.val("").hideInvalid();
        $form.animate({
            width: 0
        }, function () {
            $form.hide();
            $button.show().trigger('focus');
        });
    };

    $button.on("click", function () {
        $button.hide();
        $form.show().animate({
            width: 200
        }, function () {
            $search.trigger('focus');
        });
    });

    $search.on("keyup", function (e) {
        if (e.which === 27) { // escape
            hideForm();
        } else {
            // validate
            if ($search.val().trim().length < 2) {
                $search.addClass('is-invalid').tooltip({
                    customClass: 'tooltip-danger'
                }).tooltip('show');
            } else {
                $search.hideInvalid();
            }
        }
    }).on("blur", function () {
        hideForm();
    });

    $form.on('submit', function (e) {
        if ($search.hasClass('is-invalid')) {
            e.stopPropagation();
            e.preventDefault();
        }
    });
}

/**
 * Handle back to top button.
 */
function initBackToTop() {
    'use strict';
    const $button = $('.btn-back-to-top');
    if ($button.length) {
        $(window).on('scroll', function () {
            if ($(window).scrollTop() > 100) {
                $button.fadeIn('slow');
            } else {
                $button.fadeOut('slow');
            }
        });
        $button.on('click', function (e) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: $('body').offset().top
            }, 700);
            return false;
        });
    }
}

/**
 * Update the rounded class.
 */
function initRounded() {
    'use strict';
    const $button = $('<button/>', {
        'class': 'btn'
    });
    $('body').append($button);
    const border = $button.css('border-radius');
    $button.remove();
    $('.rounded').each(function () {
        $(this)[0].style.setProperty('border-radius', border, 'important');
    });
}

/**
 * Ready function
 */
(function ($) { // jshint ignore:line
    'use strict';
    initRounded();
    initBackToTop();
    initSearchToolbar();
    showFlashbag();
}(jQuery));
