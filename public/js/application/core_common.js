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
        const hideInvalid = () => {
            $input.removeClass('is-invalid').data('display', false).tooltip('hide');
        };
        const hideForm = () => {
            $input.val('');
            hideInvalid();
            $form.animate({
                width: 0
            }, () => {
                $form.hide();
                $button.show().trigger('focus');
            });
        };

        $button.on('click', () => {
            $button.hide();
            $form.show().animate({
                width: 200
            }, function () {
                $input.trigger('focus');
            });
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
     * Handle vertical search form.
     */
    function initVerticalSearch() {
        const $form = $('#search-form-vertical');
        if ($form.length === 0) {
            return;
        }
        const $input = $('#search-form-vertical #search');
        const $label = $('#search-form-vertical #invalid');
        const hideInvalid = () => {
            $input.removeClass('is-invalid');
            $label.hide();
        };
        const showInvalid = () => {
            $input.addClass('is-invalid');
            $label.show();
        };
        $input.on('input', () => {
            if ($input.val().trim().length < 2) {
                showInvalid();
            } else {
                hideInvalid();
            }
        }).on('blur', () => {
            $input.val('');
            hideInvalid();
        });
        $form.on('submit', (e) => {
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

    initHorizontalSearch();
    initVerticalSearch();
    initBackToTop();
    initSidebar();

    /**
     * Must be called after content loaded.
     */
    window.addEventListener("DOMContentLoaded", function () {
        initThemeTooltip();
        showFlashBag();
    });
}(jQuery));
