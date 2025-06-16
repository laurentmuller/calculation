/* globals MenuBuilder, Toaster, Cookie */
(function ($) {
    'use strict';

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
            const $elements = $(this).parents('.row-item:first').find('.dropdown-menu').children();
            const builder = new MenuBuilder({
                classSelector: 'btn-default'
            });
            return builder.fill($elements).getItems();
        }
    });

    /**
     * @param {jQuery} $input
     * @return {boolean}
     */
    function isDefaultValue($input) {
        const oldValue = $input.data('value');
        const newValue = $input.isChecked();
        return oldValue === newValue;
    }

    /**
     * Handle the restriction and custom checkboxes.
     */
    function updateView() {
        const $custom = $('#custom');
        const $restrict = $('#restrict');
        if (isDefaultValue($custom) && isDefaultValue($restrict)) {
            return;
        }

        const params = {
            custom: $custom.isChecked(),
            restrict: $restrict.isChecked(),
        };
        const id = $('.row-item.table-primary').data('id');
        if (id) {
            params.id = id;
        }

        const root = $('.card-footer-content').data('url');
        const url = root + '?' + $.param(params);
        window.location.assign(url);
    }

    /**
     * Update the selection.
     *
     * @param {jQuery} $oldSelection - the old selection.
     * @param {jQuery} $newSelection - the new selection.
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
     * @param {jQuery} $source - the source to find row to select.
     * @param {KeyboardEvent} e - the optional event
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
     * @param {jQuery} $selection
     */
    function onKeyEnter(e, $selection) {
        editRow($selection, e);
    }

    /**
     * @param {KeyboardEvent} e
     * @param {jQuery} $selection
     * @param {jQuery} $parent
     */
    function onKeyHome(e, $selection, $parent) {
        const $first = $parent.find('.row-item:first');
        toggleSelection($selection, $first);
        e.preventDefault();
    }

    /**
     * @param {KeyboardEvent} e
     * @param {jQuery} $selection
     * @param {jQuery} $parent
     */
    function onKeyEnd(e, $selection, $parent) {
        const $last = $parent.find('.row-item:last');
        toggleSelection($selection, $last);
        e.preventDefault();
    }

    /**
     * @param {KeyboardEvent} e
     * @param {jQuery} $selection
     * @param {jQuery} $rows
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
     * @param {jQuery} $selection
     * @param {jQuery} $rows
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
     * @param {jQuery} $selection
     */
    function onKeyDelete(e, $selection) {
        const $link = $selection.find('.btn-delete');
        if ($link.length) {
            e.preventDefault();
            $link[0].click();
        }
    }

    /**
     * Creates the key down handler for the calculation table.
     *
     * @param {jQuery} $parent - the parent to handle.
     * @return {(function(*): void)}
     */
    function createKeydownHandler($parent) {
        /** @param {KeyboardEvent} e */
        // eslint-disable-next-line
        return function (e) {
            if ((e.key === 0 || e.ctrlKey || e.metaKey || e.altKey) && !(e.ctrlKey && e.altKey)) {
                return;
            }
            const $rows = $parent.find('.row-item');
            if ($rows.length === 0) {
                return;
            }
            const $selection = $parent.find('.row-item.table-primary');
            switch (e.key) {
                case 'Enter':
                    onKeyEnter(e, $selection);
                    break;
                case 'Delete':
                    onKeyDelete(e, $selection);
                    break;
                case 'Home':
                    onKeyHome(e, $selection, $parent);
                    break;
                case 'End':
                    onKeyEnd(e, $selection, $parent);
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
    }

    /**
     * Hide a panel.
     *
     * @param {JQuery} $source - the source event.
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
     * Update the displayed calculations.
     *
     * @param {Event} e - the source event.
     */
    function updateCounter(e) {
        const $this = $(e.currentTarget);
        if ($this.hasClass('active')) {
            return;
        }

        const count = $this.data('value');
        const $parent = $this.parents('.dropdown');
        const $card = $this.parents('.card');
        const title = $card.find('.card-title').text();
        $parent.find('.dropdown-toggle').text(count);

        const url = $parent.data('path');
        $.post(url, {'count': count}, function (message) {
            window.location.reload();
            Toaster.info(message, title);
        });
    }

    /**
     * Select a row.
     *
     * @param {jQuery} $source - the source to find row to select.
     */
    function selectRow($source) {
        const $oldSelection = $('#calculations .row-item.table-primary');
        const $newSelection = $source.closest('.row-item');
        toggleSelection($oldSelection, $newSelection);
    }

    /**
     * Handle a collapse panel.
     *
     * @param {jQuery} $link the content selector.
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
        // handle table
        const $calculations = $('#calculations');
        if ($calculations.length && $calculations.find('.row-item').length) {
            // select
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
            }).on('focus', '.item-link,button[data-bs-toggle="dropdown"]', function () {
                selectRow($(this));
            }).initContextMenu('.row-item td:not(.context-menu-skip),.row-item div:not(.context-menu-skip)', function () {
                selectRow($(this));
            });

            // remove separators
            $('#calculations .dropdown-menu').removeSeparators();

            // handle key down event include context-menu
            const $body = $(document.body);
            const handler = createKeydownHandler($calculations);
            const selector = ':input, .btn, .dropdown-item, .rowlink-skip, .modal, a';
            $body.on('focus', selector, function () {
                $body.off('keydown', handler);
            }).on('contextmenu', function () {
                $body.off('keydown', handler);
            }).on('blur', selector, function () {
                $body.on('keydown', handler);
            }).on('contextmenu:hide', function () {
                $body.on('keydown', handler);
            }).on('keydown', handler);
        }

        // enable tooltips
        $('.card-body').tooltip({
            customClass: 'tooltip-danger',
            selector: '.has-tooltip',
            html: true
        });

        // handle checkbox options
        const $options = $('#restrict, #custom');
        if ($options.length) {
            $options.on('input', function () {
                $(this).updateTimer(updateView, 450);
            });
        }

        // handle hide panels
        const $panels = $('.hide-panel');
        if ($panels.length) {
            $panels.on('click', function () {
                hidePanel($(this));
            });
        }

        // update displayed calculations
        const $counters = $('.dropdown-item-counter');
        if ($counters.length) {
            $counters.on('click', function (e) {
                updateCounter(e);
            });
            const $parent = $counters.parents('.dropdown');
            $parent.on('shown.bs.dropdown', function () {
                $parent.find('.active').trigger('focus');
            });
        }

        // initialize collapse panels
        $('.card a.drop-down-icon-left[data-bs-toggle="collapse"]').each(function () {
            initCollapsePanel($(this));
        });
    });
}(jQuery));
