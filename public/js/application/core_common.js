/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Show the flash bag messages.
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

    const $button = $('#navigation-search-button');
    const $input = $('#navigation-search-form #search');

    const hideInvalid = function () {
        $input.removeClass('is-invalid').tooltip('dispose');
    };

    const hideForm = function () {
        $input.val("");
        hideInvalid();
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
            $input.trigger('focus');
        });
    });

    $input.on("keyup", function (e) {
        if (e.which === 27) { // escape
            hideForm();
        } else {
            // validate
            if ($input.val().trim().length < 2) {
                $input.addClass('is-invalid').tooltip({
                    customClass: 'tooltip-danger'
                }).tooltip('show');
            } else {
                hideInvalid();
            }
        }
    }).on("blur", function () {
        hideForm();
    });

    $form.on('submit', function (e) {
        if ($input.hasClass('is-invalid')) {
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
 * Initialize the sidebar.
 */
function initSidebar() {
    'use strict';
    $('.navbar-vertical').sidebar().on('toggle.sidebar', function () {
        const left = $(this).hasClass('sidebar-hide') ? "20px" : "292px";
        $('.toast-plugin').css('margin-left', left);
    });
}

/**
 * Initialize the switch light/dark theme.
 */
function initSwitchTheme() {
    'use strict';
    const $theme = $('#theme');
    const $button = $('.dropdown-item-theme');
    if ($theme.length === 0 || $button.length === 0) {
        return;
    }
    $button.on('click', function () {
        // update CSS
        let href = $theme.attr('href');
        const lightCss = $button.data('light-css');
        const darkCss = $button.data('dark-css');
        if (href === lightCss) {
            href = darkCss;
            $('body').removeClass('light').addClass('dark');
        } else {
            href = lightCss;
            $('body').removeClass('dark').addClass('light');
        }
        $theme.attr('href', href);

        // update button
        const dark = href === darkCss;
        const text = dark ? $button.data('light-text') : $button.data('dark-text');
        const icon = dark ? $button.data('light-icon') : $button.data('dark-icon');
        const $icon = $('<i/>', {
            'class': icon
        });
        $button.text(' ' + text).prepend($icon);

        // save
        const url = $button.data('path');
        const options = $("#flashbags").data();
        const title = options.title ? $button.data('title') : '';
        Toaster.removeContainer();
        if (url) {
            $.getJSON(url, {
                dark: dark
            }, function (data) {
                if (data.result && data.message) {

                    Toaster.success(data.message, title, options);
                } else {
                    const message = $button.data('error');
                    Toaster.danger(message, title, options);
                }
            }).fail(function () {
                const message = $button.data('error');
                Toaster.danger(message, title, options);
            });
        }
    });
}

/**
 * Ready function
 */
(function ($) { // jshint ignore:line
    'use strict';
    initSidebar();
    initBackToTop();
    initSearchToolbar();
    initSwitchTheme();
    showFlashbag();
}(jQuery));
