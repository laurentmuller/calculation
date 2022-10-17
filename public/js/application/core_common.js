/**! compression tag for ftp-deployment */

/* globals Toaster */

/**
 * Show the flash bag messages.
 */
function showFlashbag() {
    'use strict';
    const $element = $('.flash:first');
    if ($element.length) {
        // options
        const options = $("#flashes").data();
        const title = options.title ? $element.data('title') : null;
        const text = $element.text();
        const type = $element.data('type');
        $element.remove();
        if (text) {
            Toaster.notify(type, text, title, options);
        }
        if ($('.flash').length) {
            setTimeout(function () {
                showFlashbag();
            }, 1500);
        }
    }
}

/**
 * Handle horizontal search form.
 */
function initHorizontalSearch() {
    'use strict';
    const $form = $("#search-form-horizontal");
    if ($form.length === 0) {
        return;
    }
    const $button = $('#search-button-horizontal');
    const $input = $('#search-form-horizontal #search');
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
        if ($input.val().trim().length < 2 || $input.hasClass('is-invalid')) {
            e.preventDefault();
        }
    });
}


/**
 * Handle vertical search form.
 */
function initVerticalSearch() {
    'use strict';
    const $form = $("#search-form-vertical");
    if ($form.length === 0) {
        return;
    }
    const $input = $('#search-form-vertical #search');
    const $label = $('#search-form-vertical #invalid');
    const hideInvalid = function () {
        $input.removeClass('is-invalid');
        $label.hide();
    };
    const showInvalid = function () {
        $input.addClass('is-invalid');
        $label.show();
    };
    $input.on("input", function () {
        if ($input.val().trim().length < 2) {
            showInvalid();
        } else {
            hideInvalid();
        }
    }).on("blur", function () {
        $input.val("");
        hideInvalid();
    });
    $form.on('submit', function (e) {
        if ($input.val().trim().length < 2) {
            showInvalid();
            $input.trigger('select').trigger('focus');
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
    $('.navbar-vertical').sidebar();
}

/**
 * Initialize the switch light/dark theme.
 */
function initSwitchTheme() {
    'use strict';
    const $theme = $('#theme');
    const $button = $('.item-theme');
    if ($theme.length === 0 || $button.length === 0) {
        return;
    }
    $button.on('click', function (e) {
        // get values
        e.preventDefault();
        const options = $button.data();
        const wasDark = $theme.attr('href') === options.darkCss;
        const href = wasDark ? options.lightCss : options.darkCss;
        const text = wasDark ? options.darkText : options.lightText;
        const icon = wasDark ? options.darkIcon : options.lightIcon;
        const themeTitle = wasDark ? options.darkTitle : options.lightTitle;

        // update
        $theme.attr('href', href);
        $('body').toggleClass('light dark');
        $button.attr('title', themeTitle).text(' ' + text).prepend($('<i/>', {
            'class': icon
        }));

        // save
        if (options.path) {
            const flashBag = $("#flashes").data();
            const title = flashBag.title ? options.title : '';
            $.getJSON(options.path, {
                dark: !wasDark
            }, function (response) {
                if (response.result && response.message) {
                    Toaster.success(response.message, title, flashBag);
                } else {
                    Toaster.danger(options.error, title, flashBag);
                }
            }).fail(function () {
                Toaster.danger(options.error, title, flashBag);
            });
        }
    });
}

/**
 * Ready function
 */
(function ($) {
    'use strict';
    initHorizontalSearch();
    initVerticalSearch();
    initSwitchTheme();
    initBackToTop();
    initSidebar();
    showFlashbag();
}(jQuery));
