/**! compression tag for ftp-deployment */

/* globals Toaster, bootstrap, html2canvas, htmlToImage */

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

    /**
     * Save an image to the help cache.
     * @param {string} image
     * @param {number} index
     * @param {string} location
     */
    function sendImage(image, index, location) {
        $.post('/help/download', {
            'image': image,
            'index': index,
            'location': location
        }, (data) => {
            const title = 'Html2Image';
            if (data.result) {
                Toaster.success(data.message, title);
            } else {
                Toaster.warning(data.message, title);
            }
        });
    }

    /**
     * Create the copy button.
     * @param {NodeListOf<Element>} cards
     * @param {function} callback
     */
    function createCopyButton(cards, callback) {
        const icon = document.createElement('i');
        icon.classList.add('fa-fw', 'fa-regular', 'fa-copy');
        const link = document.createElement('a');

        link.append(icon);
        link.title = 'Save Card Image';
        link.style.marginRight = '15px';
        link.classList.add('btn', 'btn-outline-secondary', 'position-absolute',
            'top-50', 'end-0', 'translate-middle-y', 'd-print-none');
        document.querySelector('.page-content').append(link);

        link.addEventListener('click', () => {
            const location = window.location.pathname;
            const options = {backgroundColor: null};
            cards.forEach(function (card, index) {
                link.disabled = true;
                link.setAttribute('disabled', 'disabled');
                $('*').css('cursor', 'wait');
                callback(card, index, location, options);
                link.disabled = false;
            });
        });
    }

    /**
     * Save card images.
     */
    function initHtml2Image() {
        if (typeof htmlToImage === 'undefined') {
            return;
        }
        const cards = document.querySelectorAll('.page-content .card');
        if (!cards.length) {
            return;
        }

        /**
         * @param {Element} card
         * @param {number} index
         * @param {String} location
         * @param {Object} options
         */
        const callback = function (card, index, location, options) {
            htmlToImage.toPng(card, options).then((image) => {
                $('*').css('cursor', 'wait');
                sendImage(image, index, location);
            }).catch(function (error) {
                $('*').css('cursor', '');
                window.console.warn(error);
            });
        };
        createCopyButton(cards, callback);
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
        initHtml2Image();
    });
}(jQuery));
