/**! compression tag for ftp-deployment */

/* globals Toaster, bootstrap, html2canvas */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    /**
     * Handle horizontal search form.
     */
    function initHorizontalSearch() {
        const $div = $('#search-div-horizontal');
        if ($div.length === 0) {
            return;
        }
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

    // /**
    //  * @param {string} data
    //  * @return {Uint8Array}
    //  */
    // function base64ToArrayBuffer(data) {
    //     const binary = window.atob(data);
    //     const length = binary.length;
    //     const bytes = new Uint8Array(length);
    //     for (let i = 0; i < length; i++) {
    //         bytes[i] = binary.charCodeAt(i);
    //     }
    //     return bytes;
    // }

    /**
     * Save card images.
     */
    function initHtml2canvas() {
        if (typeof html2canvas === 'undefined') {
            return;
        }
        const cards = document.querySelectorAll('.page-content .card');
        if (!cards.length) {
            return;
        }

        const icon = document.createElement('i');
        icon.classList.add('fa-fw', 'fa-regular', 'fa-copy');
        const link = document.createElement('a');

        link.append(icon);
        link.title = 'Save Image Card';
        link.style.marginRight = '15px';
        link.classList.add('btn', 'btn-outline-secondary',
            'position-absolute', 'top-50', 'end-0', 'translate-middle-y');
        document.querySelector('.page-content').append(link);

        link.addEventListener('click', () => {
            const location = window.location.pathname;
            const options = {backgroundColor: null};
            cards.forEach(function (card, index) {
                html2canvas(card, options).then((canvas) => {
                    $.post('/help/download', {
                        'index': index,
                        'location': location,
                        'image': canvas.toDataURL('image/png'),
                    }, (data) => window.console.log(data));
                });
            })
        });
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
        initHtml2canvas();
    });
}(jQuery));
