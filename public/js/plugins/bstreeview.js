/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function($) {
    "use strict";

    // -----------------------------
    // Initialization
    // -----------------------------
    var BoostrapTreeView = function (element, options) {
        this.$element = $(element);
        this.options = $.extend(true, {}, BoostrapTreeView.DEFAULTS, this.$element.data(), options);
        this.itemIdPrefix = element.id + "-item-";
        this.init();
    };

    // -----------------------------
    // Prototype functions
    // -----------------------------
    BoostrapTreeView.prototype = {

        /**
         * Constructor.
         */
        constructor: BoostrapTreeView,

        /**
         * Initialize widget.
         */
        init: function () {
            const that = this;
            const options = that.options;
            const $element = that.$element;

            that.tree = [];
            that.nodes = [];

            // retrieve Json Data.
            if (options.data) {
                if (options.data.isPrototypeOf(String)) {
                    options.data = $.parseJSON(this.options.data);
                }
                that.tree = $.extend(true, [], this.options.data);
                // delete options.data;
            } else if (options.url) {
                $('*').css('cursor', 'wait');
                const $loading = $(options.templates.item)
                    .text(options.loading)
                    .appendTo($element);
                $.get(options.url, function (data) {
                    that.tree = $.extend(true, [], data);
                    // initialise
                    if (that.tree.length) {
                        that.initData({
                            nodes: that.tree
                        });
                        that.build(that.$element, that.tree, 0);
                    }
                }).always(function () {
                    $loading.remove();
                    that.selectFirst();
                    $('*').css('cursor', '');
                });
            }

            // set main class to element.
            $element.addClass('bstreeview ' + this.options.treeClass);

            if (that.tree.length) {
                // initialise
                that.initData({
                    nodes: that.tree
                });

                // build
                that.build($element, that.tree, 0);
                that.selectFirst();
            }

            // handle events
            $element.on('click', '.list-group-item', function (e) {
                // update state icon
                $('.state-icon', this)
                    .toggleClass(options.expandIcon)
                    .toggleClass(options.collapseIcon);

                // navigate to href if present
                if (e.target.hasAttribute('href')) {
                    if (options.openNodeLinkOnNewTab) {
                        window.open(e.target.getAttribute('href'), '_blank');
                    } else {
                        window.location = e.target.getAttribute('href');
                    }
                }
                that.setSelection($(this));
            });
        },

        /**
         * Initialize data.
         */
        initData: function (node) {
            if (node.nodes && node.nodes.length) {
                const that = this;
                const parent = node;
                $.each(node.nodes, function (index, node) {
                    node.nodeId = that.nodes.length;
                    node.parentId = parent.nodeId;
                    that.nodes.push(node);
                    if (node.nodes && node.nodes.length) {
                        that.initData(node);
                    }
                });
            }
        },

        /**
         * Build nodes.
         */
        build: function ($parentElement, nodes, depth) {
            const that = this;
            const options = that.options;
            const templates = options.templates;

            // calculate item padding
            let paddingLeft = options.parentIndent + "rem;";
            if (depth > 0) {
                paddingLeft = (options.indent + depth * options.indent).toString() + "rem;";
            }
            depth++;

            // add each node and sub-nodes
            $.each(nodes, function (index, node) {
                // main node element.
                const $treeItem = $(templates.item)
                    .attr('data-target', "#" + that.itemIdPrefix + node.nodeId)
                    .attr('style', 'padding-left:' + paddingLeft)
                    .attr('aria-level', depth);

                // set expand or collapse icons
                if (node.nodes && node.nodes.length) {
                    $(templates.stateIcon)
                        .addClass(node.expanded ? options.expandIcon : options.collapseIcon)
                        .appendTo($treeItem);
                }

                // set node icon if exist
                if (node.icon) {
                    $(templates.itemIcon)
                        .addClass(node.icon)
                        .appendTo($treeItem);
                }

                // set text
                $treeItem.append(node.text);

                // set badge if present
                const badgeValue = node.badgeValue || (options.badgeCount && node.nodes ? node.nodes.length : false);
                if (badgeValue) {
                    $(templates.itemBadge)
                        .addClass(node.badgeClass || 'badge-primary')
                        .text(badgeValue)
                        .appendTo($treeItem);
                }

                // reset node href if present
                if (node.href) {
                    $treeItem.attr('href', node.href);
                }

                // add class to node if present
                if (node.class) {
                    $treeItem.addClass(node.class)
                        .data('class', node.class);
                }

                // add custom id to node if present
                if (node.id) {
                    $treeItem.attr('id', node.id);
                }

                // attach node to parent.
                $parentElement.append($treeItem);

                // build child nodes
                if (node.nodes && node.nodes.length) {
                    // node group item
                    var $treeGroup = $(templates.groupItem)
                        .attr('id', that.itemIdPrefix + node.nodeId);

                    // expand the node if requested
                    if (node.expanded) {
                        $treeGroup.addClass('show');
                    }
                    $parentElement.append($treeGroup);
                    that.build($treeGroup, node.nodes, depth);
                }
            });
        },

        /**
         * Select the first element.
         */
        selectFirst: function() {
            this.setSelection(this.$element.find('.list-group-item:first'));
        },

        /**
         * Select the given element.
         */
        setSelection: function($selection) {
            const selection = this.options.selectionClass;
            this.$element.find('.list-group-item').removeClass(selection);
            if ($selection && $selection.length) {
                $selection.addClass(selection);
            }
        },

        /**
         * Gets the selected element.
         */
        getSelection: function () {
            let $selection = null;
            const selectionClass = this.options.selectionClass;
            this.$element.find('.list-group-item').each(function () {
                if ($(this).hasClass(selectionClass)) {
                    $selection = $(this);
                    return false;
                }
            });
            return $selection;
        },

        /**
         * Refresh data
         */
        refresh: function () {
            this.$element.children().remove();
            this.init();
        },

        /**
         * Collapse all.
         */
        collapseAll: function () {
            this.$element.find('.list-group.collapse.show').each(function () {
                $(this).prev('.list-group-item').trigger('click');
            });
            this.selectFirst();
        },

        /**
         * Expand all.
         */
        expandAll: function () {
            this.$element.find('.list-group.collapse:not(.show)').each(function () {
                $(this).prev('.list-group-item').trigger('click');
            });
        },

        /**
         * Expand to the given level.
         */
        expandToLevel: function (level) {
            this.$element.find('.list-group.collapse').each(function () {
                const displayed = $(this).hasClass('show');
                const $group = $(this).prev('.list-group-item');
                const groupLevel = Number.parseInt($group.attr('aria-level'), 10);
                if (displayed && groupLevel > level || !displayed && groupLevel <= level) {
                    $group.trigger('click');
                }
            });
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    BoostrapTreeView.DEFAULTS = {
        url: null,
        loading: 'Loading...',
        treeClass: 'border rounded',
        selectionClass: 'list-group-item-primary',
        expandIcon: 'fas fa-caret-down fa-fw',
        collapseIcon: 'fas fa-caret-right fa-fw',
        indent: 1.25,
        parentIndent: 1.25,
        openNodeLinkOnNewTab: true,
        badgeCount: false,
        templates: {
            groupItem: '<div role="group" class="list-group collapse" id="itemid"></div>',
            item: '<button type="button" role="treeitem" class="list-group-item list-group-item-action text-left" data-toggle="collapse"></button>',
            stateIcon: '<i class="state-icon"></i>',
            itemIcon: '<i class="item-icon"></i>',
            itemBadge: '<span class="item-badge badge badge-pill"></span>'
        }
    };

    // -----------------------------------
    // BoostrapTreeView plugin definition
    // -----------------------------------
    const oldBoostrapTreeView = $.fn.boostrapTreeView;

    $.fn.boostrapTreeView = function (option) {
        return this.each(function () {
            const $this = $(this);
            let data = $this.data('boostrapTreeView');
            if (!data) {
                const options = typeof option === 'object' && option;
                $this.data('boostrapTreeView', data = new BoostrapTreeView(this, options));
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
