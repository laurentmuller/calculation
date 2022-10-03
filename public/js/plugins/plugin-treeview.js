/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // ------------------------------------------
    // BoostrapTreeView public class definition
    // ------------------------------------------

    /**
     * @typedef Node
     * @type {object}
     * @property {string} [id] the optional node identifier.
     * @property {string} [text] - the optional node text.
     * @property {string} [badgeValue] the optional node badge value.
     * @property {string} [href] the optional node URL.
     * @property {string} [class] the optional node class.
     * @property {boolean} expanded - the expanded state.
     * @property {boolean} disable - the disabled state.
     * @property {string} icon - the state icon.
     * @property {Node[]} [nodes] - the optional children nodes.
     *
     * @type {BoostrapTreeView}
     */
    const BoostrapTreeView = class {

        // -----------------------------
        // public functions
        // -----------------------------

        /**
         * Constructor
         *
         * @param {HTMLElement} element - the element to handle.
         * @param {Object|string} options - the plugin options.
         */
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, {}, BoostrapTreeView.DEFAULTS, this.$element.data(), options);
            this._init();
        }

        /**
         * Remove handlers and data.
         */
        destroy() {
            this._removeProxies();
            this.$element.removeData(BoostrapTreeView.NAME);
        }

        /**
         * Expand all items.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        expandAll() {
            const that = this;
            const $groups = this.$element.find('.list-group:not(:visible)');
            if ($groups.length === 0) {
                return that;
            }

            const $items = $groups.map(function () {
                return $(this).prev('.list-group-item');
            });
            const params = {
                'items': $items
            };
            if (!that._trigger('expandall.bs.treeview', params)) {
                return that;
            }

            $groups.each(function () {
                that._toggleGroup($(this));
            });

            return that;
        }

        /**
         * Collapse all items.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        collapseAll() {
            const that = this;
            const $groups = that.$element.find('.list-group:visible');
            if ($groups.length === 0) {
                return that;
            }

            const $items = $groups.map(function () {
                return $(this).prev('.list-group-item');
            });
            const params = {
                'items': $items
            };
            if (!that._trigger('collapseall.bs.treeview', params)) {
                return that;
            }

            $groups.each(function () {
                that._toggleGroup($(this));
            });

            return that._selectFirst();
        }

        /**
         * Expand to the given level.
         *
         * @param {number} level - the level.
         * @return {BoostrapTreeView} this instance for chaining.
         */
        expandToLevel(level) {
            const that = this;
            const $groups = that.$element.find('.list-group').filter(function () {
                const $this = $(this);
                const visible = $this.is(':visible');
                const $item = $this.prev('.list-group-item');
                const groupLevel = Number.parseInt($item.attr('aria-level'), 10);
                return visible && groupLevel > level || !visible && groupLevel <= level;
            });
            if ($groups.length === 0) {
                return that;
            }
            const $items = $groups.map(function () {
                return $(this).prev('.list-group-item');
            });
            const params = {
                'level': level,
                'items': $items
            };
            if (!that._trigger('expandtolevel.bs.treeview', params)) {
                return that;
            }

            $groups.each(function () {
                that._toggleGroup($(this));
            });

            return that._selectFirst();
        }

        /**
         * Refresh data.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        refresh() {
            this._removeProxies();
            this.$element.children().remove();
            this._init();
            return this;
        }

        /**
         * Set focus to the selected item ,if any; the element otherwise.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         */
        focus() {
            const $selection = this._getSelection();
            if ($selection) {
                $selection.trigger('focus');
            } else {
                this.$element.trigger('focus');
            }
            return this;
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * @private
         */
        _init() {
            const that = this;
            const options = that.options;
            const $element = that.$element;

            that.tree = [];
            that.nodes = [];
            that.clickProxy = function (e) {
                that._click(e);
            };
            that.doubleClickProxy = function (e) {
                that._doubleClick(e);
            };
            that.keyDownProxy = function (e) {
                that._keydown(e);
            };
            that.toggling = false;

            // retrieve Json Data.
            if (options.data) {
                // get data
                if (options.data.isPrototypeOf(String)) {
                    options.data = JSON.parse(options.data);
                }
                that.tree = $.extend(true, [], options.data);

                // initialise
                if (that.tree.length) {
                    that._initData({
                        nodes: that.tree
                    });
                    that._build($element, that.tree, 0);
                    that._updateBorders()._selectFirst();
                }
            } else if (options.url) {
                const $loading = $(options.templates.item)
                    .addClass(options.loadingClass)
                    .text(options.loadingText)
                    .appendTo($element);
                $.getJSON(options.url, function (data) {
                    // get data
                    if (data.isPrototypeOf(String)) {
                        data = JSON.parse(data);
                    }
                    that.tree = $.extend(true, [], data);

                    // initialise
                    if (that.tree.length) {
                        that._initData({
                            nodes: that.tree
                        });
                        that._build(that.$element, that.tree, 0);
                    }

                }).always(function () {
                    $loading.remove();
                    that._updateBorders()._selectFirst();
                });
            }

            $element.on('click', '.list-group-item, .state-icon', that.clickProxy);
            $element.on('dblclick', '.list-group-item', that.doubleClickProxy);
            $element.on('keydown', '.list-group-item', that.keyDownProxy);
        }

        /**
         * Initialize data.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         * @private
         */
        _initData(node) {
            const that = this;
            if (node.nodes && node.nodes.length) {
                const parent = node;
                $.each(node.nodes, function (_index, node) {
                    node.nodeId = that.nodes.length;
                    node.parentId = parent.nodeId;
                    that.nodes.push(node);
                    if (node.nodes && node.nodes.length) {
                        that._initData(node);
                    }
                });
            }
            return that;
        }

        /**
         * Build nodes.
         * @param {JQuery} $parent -the parent element.
         * @param {Node[]} nodes - the children nodes.
         * @param {number} depth - the node depth.
         * @return {BoostrapTreeView} this instance for chaining.
         * @private
         */
        _build($parent, nodes, depth) {
            const that = this;
            const options = that.options;
            const templates = options.templates;

            // calculate padding
            let paddingLeft = options.parentIndent;
            if (depth > 0) {
                paddingLeft += depth * options.indent;
            }
            depth++;

            // add each node and sub-nodes
            $.each(nodes, function (_index, node) {
                // create node element.
                const $item = $(templates.item)
                    .css('padding-left', paddingLeft + 'rem')
                    .attr('aria-level', depth);

                // add toggle icons
                if (node.nodes && node.nodes.length) {
                    $(templates.stateIcon)
                        .addClass(node.expanded ? options.expandIcon : options.collapseIcon)
                        .attr('title', node.expanded ? options.texts.collapse : options.texts.expand)
                        .appendTo($item);
                }

                // set node icon if present
                if (node.icon) {
                    $(templates.itemIcon)
                        .addClass(node.icon)
                        .appendTo($item);
                }

                // set node text if present
                if (node.text) {
                    $(templates.itemText)
                        .text(node.text)
                        .appendTo($item);
                }

                // set badge if present
                const badgeValue = node.badgeValue || (options.badgeCount && node.nodes ? node.nodes.length : false);
                if (badgeValue) {
                    $(templates.itemBadge)
                        .addClass(node.badgeClass || options.badgeClass)
                        .text(badgeValue)
                        .appendTo($item);
                }

                // set node href if present
                if (node.href) {
                    $item.attr('href', node.href);
                }

                // add disable class if present
                if (node.disable) {
                    $item.addClass('disable')
                        .attr('disable', 'disable');
                }

                // add class to node if present
                if (node.class) {
                    $item.addClass(node.class);
                }

                // add custom id to node if present
                if (node.id) {
                    $item.attr('id', node.id);
                }

                // attach item to parent
                $parent.append($item);

                // build child nodes
                if (node.nodes && node.nodes.length) {
                    // group item
                    const $groupItem = $(templates.groupItem)
                        .toggle(node.expanded)
                        .appendTo($parent);

                    // children
                    that._build($groupItem, node.nodes, depth);
                }
            });
            return that;
        }

        /**
         * Handle item and state icon click.
         *
         * @param {Event} e - the source event.
         * @return {BoostrapTreeView} this instance for chaining.
         * @private
         */
        _click(e) {
            const $target = $(e.currentTarget);
            const $item = $target.closest('.list-group-item');
            if ($target.is('.state-icon')) {
                this._toggleGroup($item.next('.list-group'));
            } else {
                const href = $item.attr('href');
                if (href) {
                    if (this.options.openNodeLinkOnNewTab) {
                        window.open(href, '_blank');
                    } else {
                        window.location = href;
                    }
                }
            }

            return this._setSelection($item);
        }

        /**
         * Handle the item double-click.
         *
         * @param {Event} e - the source event.
         * @return {BoostrapTreeView} this instance for chaining.
         * @private
         */
        _doubleClick(e) {
            const $target = $(e.currentTarget);
            this._toggleGroup($target.next('.list-group'));
            return this._setSelection($target);
        }

        /**
         * Handle item key down event
         *
         * @param {Event} e - the source event.
         * @return {BoostrapTreeView} this instance for chaining.
         * @private
         */
        _keydown(e) {
            if (this.toggling) {
                return this;
            }

            const $target = $(e.currentTarget);
            switch (e.which) {
                case 35: { // end => last
                    const $item = this.$element.find('.list-group-item:visible:last');
                    this._setSelection($item);
                    break;
                }

                case 36: { // home => first
                    const $item = this.$element.find('.list-group-item:visible:first');
                    this._setSelection($item);
                    break;
                }

                case 37: { // arrow left => collapse or parent
                    const $group = $target.next('.list-group:visible');
                    if ($group.length) {
                        this._toggleGroup($group);
                    } else {
                        const $item = $target.parents('.list-group').prev('.list-group-item:first');
                        this._setSelection($item);
                    }
                    break;
                }

                case 38: { // arrow up => select previous
                    const $items = this.$element.find('.list-group-item:visible');
                    const index = $items.index($target);
                    if (index > 0) {
                        const $item = $items.eq(index - 1);
                        this._setSelection($item);
                    }
                    break;
                }

                case 39: { // arrow right => expand or select first child
                    const $group = $target.next('.list-group:first:not(:visible)');
                    if ($group.length) {
                        this._toggleGroup($group);
                    } else {
                        const $item = $target.next('.list-group:first').find('.list-group-item:first');
                        this._setSelection($item);
                    }
                    break;
                }

                case 40: {  // arrow down => select next
                    const $items = this.$element.find('.list-group-item:visible');
                    const index = $items.index($target);
                    const length = $items.length;
                    if (index !== -1 && index < length - 1) {
                        const $item = $items.eq(index + 1);
                        this._setSelection($item);
                    }
                    break;
                }

                case 49:
                case 107: { // + => expand
                    const $group = $target.next('.list-group:first:not(:visible)');
                    this._toggleGroup($group);
                    break;
                }

                case 109:
                case 189: { // - => collapse
                    const $group = $target.next('.list-group:first:visible');
                    this._toggleGroup($group);
                    break;
                }
            }

            return this;
        }

        /**
         * Toggle the visibility of the given group.
         *
         * @param {JQuery} $group - the group to toggle.
         * @return {BoostrapTreeView} this instance for chaining.
         * @private
         */
        _toggleGroup($group) {
            const that = this;
            if ($group && $group.length) {
                const options = that.options;
                const $item = $group.prev('.list-group-item');
                const $icon = $item.find('.state-icon');
                const params = {
                    'item': $item,
                    'expanding': $icon.hasClass(options.collapseIcon)
                };
                if (!that._trigger('togglegroup.bs.treeview', params)) {
                    return that;
                }

                that.toggling = true;
                $item.removeClass('rounded-bottom');
                $icon.toggleClass(options.collapseIcon).toggleClass(options.expandIcon);
                $group.toggle(options.toggleDuration, function () {
                    const title = $icon.hasClass(options.expandIcon) ? options.texts.collapse : options.texts.expand;
                    $icon.attr('title', title);
                    that._updateBorders().toggling = false;
                });
            }
            return that;
        }

        /**
         * Update the borders.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         * @private
         */
        _updateBorders() {
            this.$element.find('.list-group-item:first').removeClass('border-top-0');
            this.$element.find('.list-group-item.rounded-bottom').removeClass('rounded-bottom');
            this.$element.find('.list-group-item:visible:last').addClass('rounded-bottom');
            return this;
        }

        /**
         * Select the first element.
         *
         * @return {BoostrapTreeView} this instance for chaining.
         * @private
         */
        _selectFirst() {
            return this._setSelection(this.$element.find('.list-group-item:first'));
        }

        /**
         * Gets the selected item.
         *
         * @return {JQuery} the selected item, if any; null otherwise.
         * @private
         */
        _getSelection() {
            const selectionClass = this.options.selectionClass;
            const $filter = this.$element.find('.list-group-item:visible').filter(function () {
                return $(this).hasClass(selectionClass);
            });
            return $filter.length ? $filter : null;
        }

        /**
         * Select the given element.
         *
         * @param {JQuery} $selection - the item to select.
         * @return {BoostrapTreeView} this instance for chaining.
         * @private
         */
        _setSelection($selection) {
            const selectionClass = this.options.selectionClass;
            if ($selection && $selection.length) {
                this.$element.find('.list-group-item').removeClass(selectionClass);
                $selection.addClass(selectionClass).trigger('focus');
            }
            return this;
        }

        /**
         * Trigger the given event.
         *
         * @param {string} name - the event name to trigger.
         * @param {Object} parameters - the event parameters.
         * @return true if the event is not prevented; false otherwise.
         * @private
         */
        _trigger(name, parameters) {
            const e = new $.Event(name, parameters);
            this.$element.trigger(e);
            return !e.isDefaultPrevented();
        }

        /**
         * Remove handlers proxies.
         * @private
         */
        _removeProxies() {
            this.$element.off('click', '.list-group-item, .state-icon', this.clickProxy);
            this.$element.off('dblclick', '.list-group-item', this.doubleClickProxy);
            this.$element.off('keydown', '.list-group-item', this.keyDownProxy);
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    BoostrapTreeView.DEFAULTS = {
        url: null,
        toggleDuration: 350,
        loadingText: 'Loading...',
        loadingClass: 'list-group-item-success',
        selectionClass: 'list-group-item-primary',
        expandIcon: 'fas fa-caret-down fa-fw',
        collapseIcon: 'fas fa-caret-right fa-fw',
        indent: 1.25,
        parentIndent: 1.25,
        openNodeLinkOnNewTab: true,
        badgeCount: false,
        badgeClass: 'badge-pill badge-primary',
        texts: {
            expand: 'Expand',
            collapse: 'Collapse'
        },
        templates: {
            item: '<button type="button" role="treeitem" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 border-top-0" />',
            stateIcon: '<i class="state-icon mr-1" />',
            itemIcon: '<i class="item-icon mr-1" />',
            itemText: '<span class="item-text w-100" />',
            itemBadge: '<span class="badge" />',
            groupItem: '<div role="group" class="group-item list-group rounded-0" />'
        }
    };

    /**
     * The plugin name.
     */
    BoostrapTreeView.NAME = 'bs.tree-view';

    // -----------------------------------
    // BoostrapTreeView plugin definition
    // -----------------------------------
    const oldBoostrapTreeView = $.fn.boostrapTreeView;

    $.fn.boostrapTreeView = function (options) { // jslint ignore:line
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(BoostrapTreeView.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(BoostrapTreeView.NAME, new BoostrapTreeView(this, settings));
            }
        });
    };

    $.fn.boostrapTreeView.Constructor = BoostrapTreeView;

    // -----------------------------------
    // BoostrapTreeView no conflict
    // -----------------------------------
    $.fn.boostrapTreeView.noConflict = function () {
        $.fn.boostrapTreeView = oldBoostrapTreeView;
        return this;
    };

}(jQuery));
