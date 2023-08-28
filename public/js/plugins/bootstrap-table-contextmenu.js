/**
 * @author David Graham <prograhammer@gmail.com>
 * @version v1.1.4
 * @link https://github.com/prograhammer/bootstrap-table-contextmenu
 */

/* globals bootstrapTable */

(function ($) {
    'use strict';

    $.extend($.fn.bootstrapTable.defaults, {
        // Option defaults
        contextMenu: null,
        contextMenuTrigger: 'right',
        contextMenuAutoClickRow: true,
        contextMenuButton: null,
        rowSelector: 'table-primary',

        beforeContextMenuRow: function (e, row, buttonElement) {
            // return false here to prevent menu showing
        },
        // Event default handlers
        onContextMenuItem: function (row, $element) {
            return false;
        },
        onContextMenuRow: function (row, $element) {
            return false;
        }
    });

    // Methods
    $.fn.bootstrapTable.methods.push('showContextMenu');
    $.fn.bootstrapTable.methods.push('getSelectedRow');
    $.fn.bootstrapTable.methods.push('selectFirstRow');
    $.fn.bootstrapTable.methods.push('selectLastRow');
    $.fn.bootstrapTable.methods.push('selectPreviousRow');
    $.fn.bootstrapTable.methods.push('selectNextRow');

    // Events
    $.extend($.fn.bootstrapTable.Constructor.EVENTS, {
        'contextmenu-item.bs.table': 'onContextMenuItem',
        'contextmenu-row.bs.table': 'onContextMenuRow'
    });

    let BootstrapTable = $.fn.bootstrapTable.Constructor,
        _initBody = BootstrapTable.prototype.initBody;

    BootstrapTable.prototype.initBody = function () {

        // Init Body
        _initBody.apply(this, Array.prototype.slice.apply(arguments));

        // Init Context menu
        if (this.options.contextMenu || this.options.contextMenuButton || this.options.beforeContextMenuRow) {
            this.initContextMenu();
        }
    };

    // Init context menu
    BootstrapTable.prototype.initContextMenu = function () {
        const that = this;

        // Context menu on Right-click
        if (that.options.contextMenuTrigger === 'right' || that.options.contextMenuTrigger === 'both') {
            that.$body.find('> tr[data-index]').off('contextmenu.contextmenu').on('contextmenu.contextmenu', function (e) {
                let rowData = that.data[$(this).data('index')],
                    beforeShow = that.options.beforeContextMenuRow.apply(this, [e, rowData, null]);

                if (beforeShow !== false) {
                    that.showContextMenu({event: e});
                }
                return false;
            });
        }

        // Context menu on Left-click
        if (that.options.contextMenuTrigger === 'left' || that.options.contextMenuTrigger === 'both') {
            that.$body.find('> tr[data-index]').off('click.contextmenu').on('click.contextmenu', function (e) {
                let rowData = that.data[$(this).data('index')],
                    beforeShow = that.options.beforeContextMenuRow.apply(this, [e, rowData, null]);

                if (beforeShow !== false) {
                    that.showContextMenu({event: e});
                }
                return false;
            });
        }

        // Context menu on Button-click
        if (typeof that.options.contextMenuButton === 'string') {
            that.$body.find('> tr[data-index]').find(that.options.contextMenuButton).off('click.contextmenu').on('click.contextmenu', function (e) {
                let rowData = that.data[$(this).closest('tr[data-index]').data('index')],
                    beforeShow = that.options.beforeContextMenuRow.apply(this, [e, rowData, this]);

                if (beforeShow !== false) {
                    that.showContextMenu({event: e, buttonElement: this});
                }
                return false;
            });
        }
    };

    // Show context menu
    BootstrapTable.prototype.showContextMenu = function (params) {
        if (!params || !params.event) {
            return false;
        }
        if (params && !params.contextMenu && typeof this.options.contextMenu !== 'string') {
            return false;
        }

        let that = this,
            $menu, screenPosX, screenPosY,
            $tr = $(params.event.target).closest('tr[data-index]'),
            item = that.data[$tr.data('index')];

        if (params && !params.contextMenu && typeof this.options.contextMenu === 'string') {
            screenPosX = params.event.clientX;
            screenPosY = params.event.clientY;
            $menu = $(this.options.contextMenu);
        }
        if (params && params.contextMenu) {
            screenPosX = params.event.clientX;
            screenPosY = params.event.clientY;
            $menu = $(params.contextMenu);
        }
        if (params && params.buttonElement) {
            screenPosX = params.buttonElement.getBoundingClientRect().left;
            screenPosY = params.buttonElement.getBoundingClientRect().bottom;
        }

        function getMenuPosition($menu, screenPos, direction, scrollDir) {
            let win = $(window)[direction](),
                scroll = $(window)[scrollDir](),
                menu = $menu[direction](),
                position = screenPos + scroll;

            if (screenPos + menu > win && menu < screenPos) {
                position -= menu;
            }

            return position;
        }

        // Bind click on menu item
        $menu.find('li').off('click.contextmenu').on('click.contextmenu', function () {
            let rowData = that.data[$menu.data('index')];
            that.trigger('contextmenu-item', rowData, $(this));
        });

        // Click anywhere to hide the menu
        $(document).triggerHandler('click.contextmenu');
        $(document).off('click.contextmenu').on('click.contextmenu', function (e) {
            // Fixes problem on Mac OSX
            if (that.pageX !== e.pageX || that.pageY !== e.pageY) {
                $menu.hide();
                $(document).off('click.contextmenu');
            }
        });
        that.pageX = params.event.pageX;
        that.pageY = params.event.pageY;

        // Show the menu
        $menu.data('index', $tr.data('index'))
            .appendTo($('body'))
            .css({
                position: "absolute",
                left: getMenuPosition($menu, screenPosX, 'width', 'scrollLeft'),
                top: getMenuPosition($menu, screenPosY, 'height', 'scrollTop'),
                zIndex: 1100
            })
            .show();

        // Trigger events
        that.trigger('contextmenu-row', item, $tr);
        if (that.options.contextMenuAutoClickRow && that.options.contextMenuTrigger === 'right') {
            that.trigger('click-row', item, $tr);
        }
    };

    BootstrapTable.prototype.getSelectedRow = function () {
        if (this.data.length) {
            const $row = this.$body.find(`tr.${this.options.rowSelector}`);
            return $row.length ? $row : null;
        }
        return null;
    };

    BootstrapTable.prototype.selectFirstRow = function (force = false) {
        if (this.data.length) {
            const item = this.data[0];
            const $row = this.$body.find('tr[data-index]:first');
            if (force || $row !== this.getSelectedRow()) {
                this.trigger('click-row', item, $row);
            }
        }
    };

    BootstrapTable.prototype.selectLastRow = function (force = false) {
        if (this.data.length) {
            const item = this.data[this.data.length - 1];
            const $row = this.$body.find('tr[data-index]:last');
            if (force || $row !== this.getSelectedRow()) {
                this.trigger('click-row', item, $row);
            }
        }
    };

    BootstrapTable.prototype.selectPreviousRow = function () {
        if (this.data.length) {
            const $row = this.getSelectedRow();
            if ($row) {
                const index = $row.index();
                if (index > 0) {
                    const item = this.data[index - 1];
                    const $prev = $row.prev('tr[data-index]');
                    this.trigger('click-row', item, $prev);
                } else {
                    this.prevPage();
                }
            } else {
                this.selectFirstRow();
            }
        }
    };

    BootstrapTable.prototype.selectNextRow = function () {
        if (this.data.length) {
            const $row = this.getSelectedRow();
            if ($row) {
                const index = $row.index();
                if (index < this.data.length) {
                    const item = this.data[index + 1];
                    const $next = $row.next('tr[data-index]');
                    this.trigger('click-row', item, $next);
                } else {
                    this.nextPage();
                }
            } else {
                this.selectFirstRow();
            }
        }
    };

}(jQuery));
