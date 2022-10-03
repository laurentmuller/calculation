/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // ------------------------------------
    // Sidebar public class definition
    // ------------------------------------
    const Sidebar = class {

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
            this.options = $.extend(true, {}, Sidebar.DEFAULTS, this.$element.data(), options);
            this._init();
        }

        /**
         * Destructor.
         */
        destroy() {
            this.$element.off('click', '.nav-link-toggle', this.toggleMenuProxy);
            this.$sidebarToggle.off('click', this.toggleSidebarProxy);
            $(window).off('resize', this.resizeProxy);
            this.$element.removeData(Sidebar.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------

        /**
         * Initialize the plugin.
         * @private
         */
        _init() {
            // get elements
            const that = this;
            that.wasHidden = false;
            that.$pageContent = $(that.options.pageContent);
            that.$sidebarToggle = $(that.options.sidebarToggle);
            that.$sidebarHorizontal = $(that.options.sidebarHorizontal);

            // create and add proxies
            that.toggleSidebarProxy = function (e) {
                that._toggleSidebar(e);
            };
            that.toggleMenuProxy = function (e) {
                that._toggleMenu(e);
            };
            that.resizeProxy = function (e) {
                that._validateSize(e);
            };

            that.$element.on('click', '.nav-link-toggle', that.toggleMenuProxy);
            that.$sidebarToggle.on('click', that.toggleSidebarProxy);
            $(window).on('resize', that.resizeProxy);

            // update titles
            that._updateSidebar();
            that._updateMenus();

            // toggle sidebar if too small
            if (that._isClientTooSmall() && !that._isSideBarHidden()) {
                $(window).trigger('resize');
            }

            // show the sidebar, if hidden, after 1 second
            that.$sidebarToggle.hover(function (e) {
                if (that._isSideBarHidden()) {
                    that.$element.createTimer(function () {
                        that.$element.removeTimer();
                        if (that._isSideBarHidden()) {
                            that._toggleSidebar(e);
                        }
                    }, 1000);
                }
            }, function () {
                that.$element.removeTimer();
            });
        }

        /**
         * Returns a value indicating if the client width is smaller than the minimum width option.
         *
         * @return {boolean}
         * @private
         */
        _isClientTooSmall() {
            const width = document.documentElement.clientWidth;
            return width < this.options.minWidth;
        }

        /**
         * Handle the window resize event.
         *
         * @param {Event} e - the event.
         * @private
         */
        _validateSize(e) {
            if (this._isClientTooSmall()) {
                this._hideSidebar(e);
            } else if (this.wasHidden) {
                this._showSidebar(e);
            }
        }

        /**
         * Hide the sidebar if visible.
         *
         * @param {Event} e - the event.
         * @private
         */
        _hideSidebar(e) {
            if (!this._isSideBarHidden()) {
                this._toggleSidebar(e);
                this.wasHidden = true;
            }
        }

        /**
         * Show the sidebar if hidden.
         *
         * @param {Event} e - the event.
         * @private
         */
        _showSidebar(e) {
            if (this._isSideBarHidden()) {
                this._toggleSidebar(e);
                this.wasHidden = false;
            }
        }

        /**
         * Toggle the sidebar.
         *
         * @param {Event} e - the event.
         * @private
         */
        _toggleSidebar(e) {
            if (e) {
                e.preventDefault();
            }
            $.hideDropDownMenus();
            this.$element.add(this.$pageContent).toggleClass('sidebar-hide');
            const isHidden = this._isSideBarHidden();
            const $toggle = this.$sidebarHorizontal.find('.nav-sidebar-horizontal');
            if (isHidden) {
                $toggle.show(350);
            } else {
                $toggle.hide(350);
            }
            this._updateSidebar();
            this._saveState();

            // notify
            this.$element.trigger('toggle.sidebar');
        }

        /**
         * Toggle a menu.
         *
         * @param {Event} e - the event.
         * @private
         */
        _toggleMenu(e) {
            e.preventDefault();
            const that = this;
            const $link = $(e.currentTarget);
            const $parent = $link.parents('.nav-item-dropdown');
            const $menu = $parent.find('.navbar-menu');
            if (!$menu.is(':visible')) {
                that._collapseMenus();
            }
            $menu.toggle(350, function () {
                $link.addClass('active');
                that._updateMenus();
                that._saveState();
            });
        }

        /**
         * Update the sidebar content.
         * @private
         */
        _updateSidebar() {
            const isHidden = this._isSideBarHidden();
            const title = isHidden ? this.options.showSidebar : this.options.hideSidebar;
            this.$sidebarToggle.attr({
                'aria-expanded': String(!isHidden),
                'title': title,
            });
        }

        /**
         * Update the menus content.
         * @private
         */
        _updateMenus() {
            const options = this.options;
            this.$element.find('.nav-item-dropdown .nav-link-toggle').each(function () {
                const $menu = $(this);
                const visible = $menu.parents('.nav-item-dropdown').find('.navbar-menu').is(':visible');
                const title = visible ? options.hideMenu : options.showMenu;
                $menu.attr({
                    'aria-expanded': String(visible),
                    'title': title
                });
            });
        }

        /**
         * Collapse all expanded menus
         * @private
         */
        _collapseMenus() {
            const $toggle = this.$element.find('.nav-item-dropdown .nav-link-toggle[aria-expanded="true"]');
            if ($toggle.length) {
                $toggle.removeClass('active').attr({
                    'title': this.options.showMenu,
                    'aria-expanded': 'false'
                });
                this.$element.find('.navbar-menu:visible').hide(350);
            }
        }

        /**
         * Returns if the sidebar is hidden.
         *
         * @return {boolean} true if hidden; false if visible.
         * @private
         */
        _isSideBarHidden() {
            return this.$element.hasClass('sidebar-hide');
        }

        /**
         * Gets the navigation state.
         * @private
         */
        _getState() {
            const menus = {
                'menu_sidebar_hide': this._isSideBarHidden()
            };
            let visible;
            let wasVisible = false;
            this.$element.find('.nav-item-dropdown[id]').each(function () {
                const $element = $(this);
                visible = $element.find('.navbar-menu').is(':visible');
                // check that only one menu is visible
                if (wasVisible && visible) {
                    visible = false;
                } else if (!wasVisible && visible) {
                    wasVisible = true;
                }
                menus[$element.attr('id')] = visible;
            });

            return menus;
        }

        /**
         * Save the navigation state.
         * @private
         */
        _saveState() {
            const url = this.options.url;
            if (url) {
                $.ajaxSetup({global: false});
                $.post(url, this._getState()).always(function () {
                    $.ajaxSetup({global: true});
                });
            }
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    Sidebar.DEFAULTS = {
        url: null,
        sidebarHorizontal: '.navbar-horizontal',
        sidebarToggle: '.sidebar-toggle',
        pageContent: '.page-content',
        showSidebar: 'Show Sidebar',
        hideSidebar: 'Hide Sidebar',
        showMenu: 'Expand',
        hideMenu: 'Collapse',
        minWidth: 960
    };

    /**
     * The plugin name.
     */
    Sidebar.NAME = 'bs.sidebar';

    // -----------------------------
    // sidebar plugin definition
    // -----------------------------
    const oldSidebar = $.fn.sidebar;

    $.fn.sidebar = function (options) {
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(Sidebar.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(Sidebar.NAME, new Sidebar(this, settings));
            }
        });
    };

    $.fn.sidebar.Constructor = Sidebar;

    // ------------------------------------
    // sidebar no conflict
    // ------------------------------------
    $.fn.sidebar.noConflict = function () {
        $.fn.sidebar = oldSidebar;
        return this;
    };

}(jQuery));
