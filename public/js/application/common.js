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
        // get values
        const text = $element.text();
        const type = $element.data('type');
        const title = $element.data('title');

        // remove
        $element.remove();

        // display
        if (text) {
            Toaster.notify(type, text, title, $("#flashbags").data());
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

    // search?
    const $form = $("#navigation-search-form");
    if ($form.length === 0) {
        return;
    }
    const $button = $("#navigation-search-button");
    const $query = $("#navigation-search-form #query");

    const hideForm = function () {
        $form.animate({
            width: 0
        }, function () {
            $form.hide();
            $query.val("").removeClass('is-invalid');
            $button.show().focus();
        });
    };

    $button.on("click", function () {
        $button.hide();
        $form.show().animate({
            width: 200
        }, function () {
            $query.focus();
        });
    });

    $query.on("keyup", function (e) {
        if (e.which === 27) { // escape
            hideForm();
        } else {
            // validate
            const val = $query.val();
            if (val.length < 2) {
                $query.addClass('is-invalid');
            } else {
                $query.removeClass('is-invalid');
            }
        }
    }).on("blur", function () {
        hideForm();
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
            $('html, body').animate({
                scrollTop: 0
            }, 700);
            return false;
        });
    }
}

/**
 * Ready function
 */
(function ($) { // jshint ignore:line
    'use strict';
    showFlashbag();
    initBackToTop();
    initSearchToolbar();
}(jQuery));
