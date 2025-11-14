/* globals MenuBuilder, Toaster, Cookie */

(function ($) {

    'use strict';

    /**
     * Update the selection.
     *
     * @param {jQuery|*} $oldSelection - the old selection.
     * @param {jQuery|*} $newSelection - the new selection.
     */
    function toggleSelection($oldSelection, $newSelection) {
        if (!$oldSelection.is($newSelection)) {
            $oldSelection.removeClass('table-primary');
            $newSelection.addClass('table-primary').scrollInViewport();
        }
    }

    /**
     * Edit the given row.
     *
     * @param {jQuery|*} $source - the source to find row to select.
     * @param {KeyboardEvent} e - the event
     */
    function editRow($source, e) {
        const $link = $source.find('.btn-default');
        if ($link.length) {
            $link[0].click();
            e.preventDefault();
        }
    }

    /**
     * @param {KeyboardEvent} e
     * @param {jQuery|*} $selection
     */
    function onKeyEnter(e, $selection) {
        editRow($selection, e);
    }

    /**
     * @param {KeyboardEvent} e
     * @param {jQuery|*} $selection
     * @param {jQuery|*} $parent
     */
    function onKeyHome(e, $selection, $parent) {
        const $first = $parent.find('.row-item:first');
        toggleSelection($selection, $first);
        e.preventDefault();
    }

    /**
     * @param {KeyboardEvent} e
     * @param {jQuery|*} $selection
     * @param {jQuery|*} $parent
     */
    function onKeyEnd(e, $selection, $parent) {
        const $last = $parent.find('.row-item:last');
        toggleSelection($selection, $last);
        e.preventDefault();
    }

    /**
     * @param {KeyboardEvent} e
     * @param {jQuery|*} $selection
     * @param {jQuery|*} $rows
     */
    function onKeyPrevious(e, $selection, $rows) {
        const index = $selection.length ? $rows.index($selection) - 1 : $rows.length - 1;
        const $prev = $rows.eq(index);
        const $last = $rows.eq($rows.length - 1);
        if ($prev.length) {
            toggleSelection($selection, $prev);
            e.preventDefault();
        } else if ($last.length) {
            toggleSelection($selection, $last);
            e.preventDefault();
        }
    }

    /**
     * @param {KeyboardEvent} e
     * @param {jQuery|*} $selection
     * @param {jQuery|*} $rows
     */
    function onKeyNext(e, $selection, $rows) {
        const index = $selection.length ? $rows.index($selection) + 1 : 0;
        const $next = $rows.eq(index);
        const $first = $rows.eq(0);
        if ($next.length) {
            toggleSelection($selection, $next);
            e.preventDefault();
        } else if ($first.length) {
            toggleSelection($selection, $first);
            e.preventDefault();
        }
    }

    /**
     * @param {KeyboardEvent} e
     * @param {jQuery|*} $selection
     */
    function onKeyDelete(e, $selection) {
        const $link = $selection.find('.btn-delete');
        if ($link.length) {
            e.preventDefault();
            $link[0].click();
        }
    }

    /**
     * Select a row.
     *
     * @param {jQuery|*} $source - the source to find row to select.
     */
    function selectRow($source) {
        const $oldSelection = $('#calculations .row-item.table-primary');
        const $newSelection = $source.closest('.row-item');
        toggleSelection($oldSelection, $newSelection);
    }

    /**
     * -------------- jQuery extensions --------------
     */
    $.fn.extend({
        /**
         * Creates the context menu items.
         *
         * @returns {Object} the context menu items.
         */
        getContextMenuItems: function () {
            const $elements = $(this).parents('.row-item:first')
                .find('.dropdown-menu').children();
            return new MenuBuilder({
                classSelector: 'btn-default',
                elements: $elements
            }).getItems();
        },

        /**
         * Returns if this has the right-checked class.
         *
         * @returns {bool}
         */
        isRightChecked: function () {
            return $(this).hasClass('dropdown-item-checked-right');
        },

        /**
         * Add the right-checked class.
         *
         * @returns {jQuery|*}
         */
        setRightChecked: function () {
            return $(this).addClass('dropdown-item-checked-right');
        },


        /**
         * Remove the right-checked class.
         *
         * @returns {jQuery|*}
         */
        removeRightChecked: function () {
            return $(this).removeClass('dropdown-item-checked-right');
        },

        /**
         * Disabled the key handler for calculations.
         */
        disableKeyHandler: function () {
            const $this = $(this);
            const keyHandler = $this.data('key.handler');
            if (keyHandler) {
                $('body').off('.calculations');
            }
            return $this;
        },

        /**
         * Enabled the key handler for calculations.
         */
        enableKeysHandler: function () {
            const $this = $(this);
            /** @param {KeyboardEvent} e */
            const keyHandler = function (e) {
                if ((e.key === '' || e.ctrlKey || e.metaKey || e.altKey) && !(e.ctrlKey && e.altKey)) {
                    return;
                }
                const $rows = $this.find('.row-item');
                if ($rows.length === 0) {
                    return;
                }
                const $selection = $this.find('.row-item.table-primary');
                switch (e.key) {
                    case 'Enter':
                        onKeyEnter(e, $selection);
                        break;
                    case 'Delete':
                        onKeyDelete(e, $selection);
                        break;
                    case 'Home':
                        onKeyHome(e, $selection, $this);
                        break;
                    case 'End':
                        onKeyEnd(e, $selection, $this);
                        break;
                    case '-':
                    case 'ArrowLeft':
                    case 'ArrowUp':
                        onKeyPrevious(e, $selection, $rows);
                        break;
                    case '+':
                    case 'ArrowRight':
                    case 'ArrowDown':
                        onKeyNext(e, $selection, $rows);
                        break;
                }
            };
            $this.disableKeyHandler()
                .data('key.handler', keyHandler);

            const $body = $('body');
            const selector = ':input, .btn, .dropdown-item, .rowlink-skip, .modal, a';
            $body.on('focus.calculations', selector, function () {
                $body.off('keydown', keyHandler);
            }).on('blur.calculations', selector, function () {
                $body.on('keydown', keyHandler);
            }).on('contextmenu.calculations', function () {
                $body.off('keydown', keyHandler);
            }).on('contextmenu:hide.calculations', function () {
                $body.on('keydown', keyHandler);
            }).on('keydown.calculations', keyHandler);
        }
    });

    /**
     * Handle the calculations view
     */
    function handleCalculations() {
        // find calculations
        const $calculations = $('#calculations');
        if ($calculations.length === 0 || $calculations.find('.row-item').length === 0) {
            return;
        }

        // set selection
        let $selection = $calculations.find('.row-item.table-primary');
        if ($selection.length === 0) {
            $selection = $calculations.find('.row-item:first').addClass('table-primary');
        }
        $selection.scrollInViewport();

        // handle table events and context menu
        $calculations.on('mousedown', '.row-item', function (e) {
            switch (e.button) {
                case 0:
                    selectRow($(this));
                    break;
                case 2:
                    $.hideDropDownMenus();
                    break;
            }
        }).on('click', '.row-item [data-bs-toggle="dropdown"]', function () {
            selectRow($(this));
        }).on('dblclick', '.row-item', function (e) {
            editRow($(this), e);
        }).on('focus', '.item-link, button[data-bs-toggle="dropdown"]', function () {
            selectRow($(this));
        }).initContextMenu('.row-item td:not(.context-menu-skip),.row-item div:not(.context-menu-skip)', function () {
            selectRow($(this));
        });

        // remove separators
        $calculations.find('.dropdown-menu').removeSeparators();

        // handle key down event
        $calculations.enableKeysHandler();
    }

    /**
     * Initialize danger tooltips.
     */
    function handleDangerTooltips() {
        $('.card-body .has-tooltip')
            .tooltip('dispose')
            .tooltip({
                customClass: 'tooltip-danger',
                html: true
            });
    }

    /**
     * Update the calculations view
     */
    function updateView() {
        // disable key handler
        $('#calculations').disableKeyHandler();

        // build parameters
        const params = {
            restrict: $('.dropdown-item-restrict').hasClass('dropdown-item-checked-right'),
            custom: $('.dropdown-item-view.dropdown-item-checked-right').data('value'),
            count: $('.dropdown-item-counter.dropdown-item-checked-right').data('value'),
            id: $('.row-item.table-primary').data('id') || 0
        };

        // get and update content
        const url = $('#DISPLAY_CALCULATION').data('url');
        $.getJSON(url, params, function (data) {
            $('#DISPLAY_CALCULATION .card-body').replaceWith(data);
            handleDangerTooltips();
            handleCalculations();

        });
    }

    /**
     * Hide a panel.
     *
     * @param {jQuery|*} $source - the source event.
     */
    function hidePanel($source) {
        const $card = $source.parents('.card');
        const title = $source.find('.card-title').text();
        const url = $card.data('path');
        $.post(url, function (message) {
            $card.fadeOut(200, function () {
                $card.remove();
                Toaster.info(message, title);
            });
        });
    }


    /**
     * Handle a collapse panel.
     *
     * @param {jQuery|*} $link the content selector.
     */
    function initCollapsePanel($link) {
        const href = $link.attr('href');
        const $element = $(href);
        const path = $('body').data('cookie-path');
        const key = $element.attr('id').toUpperCase();
        $element.on('show.bs.collapse', function () {
            $link.attr('title', $link.data('collapse'));
            Cookie.setValue(key, true, path);
        }).on('hide.bs.collapse', function () {
            $link.attr('title', $link.data('expand'));
            Cookie.setValue(key, false, path);
        });
    }

    /**
     * Ready function
     */
    $(function () {
        // handle tooltips
        handleDangerTooltips();

        // handle calculations
        handleCalculations();

        // handle hide panels
        const $panels = $('.hide-panel');
        if ($panels.length) {
            $panels.on('click', function () {
                hidePanel($(this));
            });
        }

        // update displayed calculations
        $('.dropdown-item-counter').on('click', function () {
            const $this = $(this);
            if ($this.isRightChecked()) {
                return;
            }
            $('.dropdown-item-counter.dropdown-item-checked-right').removeRightChecked();
            $this.setRightChecked();
            updateView();
        });

        // update restrict calculations
        $('.dropdown-item-restrict').on('click', function () {
            $(this).toggleClass('dropdown-item-checked-right');
            updateView();
        });

        // update calculations view
        $('.dropdown-item-view').on('click', function () {
            const $this = $(this);
            if ($this.isRightChecked()) {
                return;
            }
            $('.dropdown-item-view.dropdown-item-checked-right').removeRightChecked();
            $this.setRightChecked();
            updateView();
        });

        // initialize collapse panels
        $('.card a.drop-down-icon-left[data-bs-toggle="collapse"]').each(function () {
            initCollapsePanel($(this));
        });
    });
}(jQuery));
