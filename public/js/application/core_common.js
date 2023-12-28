/**! compression tag for ftp-deployment */

/* globals Toaster, bootstrap */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    /**
     * Handle horizontal search form.
     */
    function initHorizontalSearch() {
        const $form = $('#search-form-horizontal');
        if ($form.length === 0) {
            return;
        }
        const formSize = 150;
        const breakPoint = 992;
        const $button = $('#search-button-horizontal');
        const $input = $('#search-form-horizontal #search');
        const hideInvalid = () => {
            $input.removeClass('is-invalid').data('display', false).tooltip('hide');
        };
        const hideForm = () => {
            $input.val('');
            hideInvalid();
            if (window.innerWidth > breakPoint) {
                $form.animate({
                    width: 0
                }, () => {
                    $form.hide();
                    $button.show().trigger('focus');
                });
            }
        };
        $button.on('click', () => {
            $button.hide();
            $form.show().animate({
                width: formSize
            }, () => {
                $input.trigger('focus');
            });
        });
        $(window).on('resize', () => {
            if (window.innerWidth > breakPoint) {
                $button.show();
                $form.css('width', 0).hide();
            } else {
                $button.hide();
                $form.css('width', formSize).show();
            }
        });
        $input.on('keyup', (e) => {
            if (e.key === 'Escape') {
                hideForm();
            } else {
                if ($input.val().trim().length < 2) {
                    $input.addClass('is-invalid');
                    if (!$input.data('display')) {
                        $input.data('display', true).tooltip('show');
                    }
                } else {
                    hideInvalid();
                }
            }
        }).on('blur', () => {
            hideForm();
        });
        $form.on('submit', (e) => {
            if ($input.val().trim().length < 2 || $input.hasClass('is-invalid')) {
                e.preventDefault();
            }
        });
    }

    /**
     * Initialize the sidebar.
     */
    function initSidebar() {
        $('.navbar-vertical').sidebar({
            pathname: 'caller'
        });
    }

    /**
     * Initialize theme menu tooltips.
     */
    function initThemeTooltip() {
        $('[data-theme][data-bs-toggle="tooltip"]').tooltip();
    }

    /**
     * Handle back to top button.
     */
    function initBackToTop() {
        const $button = $('.btn-back-to-top');
        if ($button.length) {
            $(window).on('scroll', () => {
                if ($(window).scrollTop() > 100) {
                    $button.fadeIn('slow');
                } else {
                    $button.fadeOut('slow');
                }
            });
            $button.on('click', () => $(window).scrollTop(0));
        }
    }

    /**
     * Show the flash bag messages.
     */
    function showFlashBag() {
        const $element = $('.flash:first');
        if ($element.length) {
            const options = $('#flashes').data();
            const title = options.title ? $element.data('title') : null;
            const text = $element.text();
            const type = $element.data('type');
            $element.remove();
            if (text) {
                Toaster.notify(type, text, title, options);
            }
            if ($('.flash').length) {
                setTimeout(function () {
                    showFlashBag();
                }, 1500);
            }
        }
    }

    initHorizontalSearch();
    initBackToTop();
    initSidebar();

    /**
     * Must be called after content loaded.
     */
    window.addEventListener('DOMContentLoaded', () => {
        initThemeTooltip();
        showFlashBag();
    });
}(jQuery));
