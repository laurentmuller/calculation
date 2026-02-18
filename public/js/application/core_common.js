/* globals Toaster, bootstrap */

/**
 * Ready function
 */
$(function () {
    'use strict';

    /**
     * Handle the horizontal search form.
     */
    function initHorizontalSearch() {
        const $div = $('#search-div-horizontal');
        if ($div.length === 0 || $div.data('initialized')) {
            return;
        }
        $div.data('initialized', true)
            .replaceDisplayNone();

        const formSize = 150;
        const breakPoint = 992;
        const $input = $('#search-input-horizontal');
        const $button = $('#search-button-horizontal');
        const hideInvalid = () => $input.removeClass('is-invalid').data('display', false).tooltip('hide');
        const hideForm = () => {
            hideInvalid();
            if (window.innerWidth > breakPoint) {
                $div.animate({width: 0}, () => {
                    $div.hide();
                    $button.show().trigger('focus');
                });
            }
        };
        const keyupHandler = (e) => {
            if (e.key === 'Escape') {
                hideForm();
                return;
            }
            const value = String($input.val()).trim();
            if (value.length < 2) {
                $input.addClass('is-invalid');
                if (!$input.data('display')) {
                    $input.data('display', true).tooltip('show');
                }
                return;
            }
            hideInvalid();
            if (e.key === 'Enter') {
                const url = $input.data('action');
                window.location.href = `${url}?search=${value}`;
            }
        };
        $input.on('keyup', keyupHandler).on('blur', () => hideForm());
        $button.on('click', () => {
            $button.hide();
            $div.show().animate({width: formSize}, () => $input.trigger('focus'));
        });
        $(window).on('resize', () => {
            if (window.innerWidth > breakPoint) {
                $button.show();
                $div.css('width', 0).hide();
            } else {
                $button.hide();
                $div.css('width', formSize).show();
            }
        });
    }

    /**
     * Initialize the theme switcher.
     */
    function initThemeSwitcher() {
        $('.theme-switcher').each(function () {
            const $this = $(this);
            if (!$this.data('initialized')) {
                $this.data('initialized', true);
                $this.themeListener();
            }
        });
    }

    /**
     * Initialize the sidebar.
     */
    function initSidebar() {
        $('body').sidebar({
            pathname: 'caller'
        }).on('toggle-navigation', () => {
            initHorizontalSearch();
            initThemeSwitcher();
        });
    }

    /**
     * Handle the back-to-top button.
     */
    function initBackToTop() {
        /** @type {jQuery<HTMLButtonElement>} */
        const $button = $('.btn-back-to-top');
        if (!$button.length) {
            return;
        }
        window.addEventListener('scroll', () => {
            if (window.scrollY > 150) {
                $button.fadeIn();
            } else {
                $button.fadeOut();
            }
        });
        $button.on('click', (e) => {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'auto'
            });
        });
    }

    /**
     * Show the flash bag messages.
     */
    function showFlashBag() {
        const $element = $('#flashes .flash:first');
        if ($element.length === 0) {
            return;
        }
        const options = $('#flashes').data();
        const title = options.title ? $element.data('title') : null;
        const text = $element.text();
        const type = $element.data('type');
        $element.remove();
        if (text) {
            Toaster.notify(type, text, title, options);
        }
        if ($('#flashes .flash').length === 0) {
            return;
        }
        const timeout = Math.max(1500, options.timeout - 500);
        setTimeout(function () {
            showFlashBag();
        }, timeout);
    }

    initHorizontalSearch();
    initThemeSwitcher();
    initBackToTop();
    initSidebar();
    showFlashBag();
});
