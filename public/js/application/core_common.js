/**! compression tag for ftp-deployment */

/* globals Toaster */


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
        const $button = $('#search-form-vertical #button');
        const $label = $('#search-form-vertical #invalid');
        const hideInvalid = function () {
            $input.removeClass('is-invalid');
            $label.hide();
        };
        const showInvalid = function () {
            $input.addClass('is-invalid');
            $button.addClass('disabled');
            $label.show();
        };
        $input.on('input', function () {
            if ($input.val().trim().length < 2) {
                $button.addClass('disabled');
                showInvalid();
            } else {
                $button.removeClass('disabled');
                hideInvalid();
            }
        }).on('blur', function () {
            $button.addClass('disabled');
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

    /**
     * Handle theme change.
     */
    function initThemeListener() {
        /** @param {CustomEvent<string>} e */
        window.addEventListener('theme', (e) => {
            const $link = $(`[data-bs-theme-value="${e.detail}"]:first`);
            if ($link.length) {
                const message = $link.data('message');
                const title = $link.data('title');
                const options = $('#flashes').data();
                Toaster.success(message, title, options);
            }
            // notify for the sidebar
            window.dispatchEvent(new Event('resize'));
        });
    }

    /**
     * Handle theme dialog.
     */
    function initThemeDialog() {
        // window.console.log(bootstrap.Modal.getOrCreateInstance('#theme_modal'));
        const $dialog = $('#theme_modal');
        if ($dialog.length === 0) {
            return;
        }

        const getCookieValue = function () {
            const name = "THEME=";
            const decodedCookie = decodeURIComponent(document.cookie);
            const entries = decodedCookie.split(';');
            for (let i = 0; i < entries.length; i++) {
                const entry = entries[i].trimStart();
                if (entry.indexOf(name) === 0) {
                    return entry.substring(name.length, entry.length);
                }
            }
            return 'auto';
        };

        const setCookieValue = function (value) {
            const date = new Date();
            date.setFullYear(date.getFullYear() + 1);
            const path = document.body.dataset.cookiePath || '/';
            let entry = 'THEME=' + encodeURIComponent(value) + ';';
            entry += 'expires=' + date.toUTCString() + ';';
            entry += 'path=' + path + ';';
            entry += 'samesite=lax;';
            document.cookie = entry;
        };

        $dialog.on('show.bs.modal', () => {
            $dialog.data('theme', false);
            const theme = getCookieValue();
            document.querySelectorAll('#theme_modal .form-check-input').forEach(e => {
                e.checked = e.value === theme;
            });
            $(window).trigger('resize');
        });
        $dialog.on('shown.bs.modal', () => {
            $('#theme_modal .form-check-input:checked').trigger('focus');
        });
        $dialog.on('hidden.bs.modal', () => {
            const theme = $dialog.data('theme');
            if (theme) {
                document.body.setAttribute('data-bs-theme', theme);
                setCookieValue(theme);
            }
        });

        const $btnOk = $('#theme_modal .btn-ok');
        $btnOk.on('click', () => {
            const input = document.querySelector('#theme_modal .form-check-input:checked');
            if (input) {
                $dialog.data('theme', input.value);
                const label = input.parentElement.querySelector('label');
                const link = document.querySelector('.nav-link-modal');
                if (label && link) {
                    link.querySelector('.theme-icon').textContent = label.querySelector('.theme-icon').textContent;
                    link.querySelector('.theme-text').textContent = label.querySelector('.theme-text').textContent;
                }
            }
            $dialog.modal('hide');
        });

        $('#theme_modal .help-text').on('click', function () {
            $(this).parent().children('.form-check-input').trigger('click');
        });

        $('#theme_modal .form-check').on('dblclick', () => $btnOk.trigger('click'));
        $dialog.on('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                $btnOk.trigger('click');
            }
        });
    }

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
    function handleSubMenus() {
        // prevent closing from click inside dropdown
        document.querySelectorAll('.dropdown-menu').forEach(function (element) {
            element.addEventListener('click', function (e) {
                if (this.querySelectorAll('.submenu').length) {
                    e.stopPropagation();
                }
            });
        });

        // close all inner dropdowns when parent is closed
        document.querySelectorAll('.navbar .dropdown').forEach(function (dropdown) {
            dropdown.addEventListener('hidden.bs.dropdown', function () {
                // after dropdown is hidden, hide all submenus too
                this.querySelectorAll('.submenu').forEach(function (submenu) {
                    submenu.style.display = 'none';
                });
            });
        });

        // toggle submenu style
        document.querySelectorAll('.dropdown-menu a').forEach(function (element) {
            element.addEventListener('click', function (e) {
                const nextElement = this.nextElementSibling;
                if (nextElement && nextElement.classList.contains('submenu')) {
                    // prevent opening link if link needs to open dropdown
                    e.preventDefault();
                    if (nextElement.style.display === 'block') {
                        nextElement.style.display = 'none';
                    } else {
                        nextElement.style.display = 'block';
                    }
                }
            });
        });
    }

    initHorizontalSearch();
    initVerticalSearch();
    initBackToTop();
    initSidebar();

    /**
     * Must be called after content loaded.
     */
    window.addEventListener("DOMContentLoaded", function () {
        showFlashBag();
        handleSubMenus();
        initThemeListener();
        initThemeDialog();
    });
}(jQuery));
