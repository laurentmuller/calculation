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
        const $button = $('#search-button-horizontal');
        const $input = $('#search-form-horizontal #search');
        const hideInvalid = function () {
            $input.removeClass('is-invalid').data('display', false).tooltip('hide');
        };
        const hideForm = function () {
            $input.val('');
            hideInvalid();
            $form.animate({
                width: 0
            }, function () {
                $form.hide();
                $button.show().trigger('focus');
            });
        };

        $button.on('click', function () {
            $button.hide();
            $form.show().animate({
                width: 200
            }, function () {
                $input.trigger('focus');
            });
        });
        $input.on('keyup', function (e) {
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
        }).on('blur', function () {
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
        const $form = $('#search-form-vertical');
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
        $input.on('input', function () {
            if ($input.val().trim().length < 2) {
                showInvalid();
            } else {
                hideInvalid();
            }
        }).on('blur', function () {
            $input.val('');
            hideInvalid();
        });
        $form.on('submit', function (e) {
            if ($input.val().trim().length < 2) {
                e.preventDefault();
                $input.trigger('select').trigger('focus');
                showInvalid();
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

    // function initThemeLinks() {
    //     $('.theme-link').themeListener({
    //         targetId: '.page-content'
    //     });
    // }

    /**
     * Handle back to top button.
     */
    function initBackToTop() {
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

    /**
     * Handle sub-menu.
     */
    // function handleSubMenus() {
    //     // prevent closing from click inside dropdown
    //     document.querySelectorAll('.dropdown-menu').forEach(function (element) {
    //         element.addEventListener('click', function (e) {
    //             if (this.querySelectorAll('.submenu').length) {
    //                 e.stopPropagation();
    //             }
    //         });
    //     });
    //
    //     // close all inner dropdowns when parent is closed
    //     document.querySelectorAll('.navbar .dropdown').forEach(function (dropdown) {
    //         dropdown.addEventListener('hidden.bs.dropdown', function () {
    //             // after dropdown is hidden, hide all submenus too
    //             this.querySelectorAll('.submenu').forEach(function (submenu) {
    //                 submenu.style.display = 'none';
    //             });
    //         });
    //     });
    //
    //     // toggle submenu style
    //     document.querySelectorAll('.dropdown-menu a').forEach(function (element) {
    //         element.addEventListener('click', function (e) {
    //             const nextElement = this.nextElementSibling;
    //             if (nextElement && nextElement.classList.contains('submenu')) {
    //                 // prevent opening link if link needs to open dropdown
    //                 e.preventDefault();
    //                 if (nextElement.style.display === 'block') {
    //                     nextElement.style.display = 'none';
    //                 } else {
    //                     nextElement.style.display = 'block';
    //                 }
    //             }
    //         });
    //     });
    // }

    function initToggleTheme() {
        $('.toggle-theme').on('click', function () {
            let icon, text, theme, title;
            const $this = $(this);
            const url = $this.data('url');
            const isDark = document.body.getAttribute('data-bs-theme') === 'dark';
            if (isDark) {
                icon = $this.data('light-icon');
                text = $this.data('light-text');
                title = $this.data('dark-title');
                theme = 'light';
            } else {
                icon = $this.data('dark-icon');
                text = $this.data('dark-text');
                title = $this.data('light-title');
                theme = 'dark';
            }
            const $themes = $('.toggle-theme');
            $themes.attr('title', title);
            $themes.find('.theme-icon').attr('class', icon);
            $themes.find('.theme-text').text(text);
            document.body.setAttribute('data-bs-theme', theme);
            $(window).trigger('resize');
            $.ajaxSetup({global: false});
            $.get(url, {'theme': theme}).always(() => $.ajaxSetup({global: true}));
            // $themes.find('.theme-icon').addClass('fa-spin').createTimer(function () {
            //     $themes.find('.theme-icon').removeClass('fa-spin');
            // }, 500);

        });
    }

    initHorizontalSearch();
    initVerticalSearch();
    initToggleTheme();
    initBackToTop();
    initSidebar();

    /**
     * Must be called after content loaded.
     */
    window.addEventListener("DOMContentLoaded", function () {
        showFlashBag();
        // handleSubMenus();
        // initThemeLinks();
    });
}(jQuery));
