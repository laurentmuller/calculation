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
            this.$showSidebarButton.off('click', this.showSidebarProxy);
            this.$hideSidebarButton.off('click', this.hideSidebarProxy);
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
            const options = this.options;
            // this.wasHidden = false;

            // get elements
            this.$showSidebarButton = $(options.showSidebarButton);
            this.$hideSidebarButton = $(options.hideSidebarButton);
            this.$navbarHorizontal = $(options.navbarHorizontal);
            this.$pageContent = $(options.pageContent);

            // create and add proxies
            this.showSidebarProxy = e => this._showSidebar(e);
            this.hideSidebarProxy = e => this._hideSidebar(e);
            this.toggleMenuProxy = e => this._toggleMenu(e);
            this.resizeProxy = e => this._resize(e);

            this.$element.on('click', '.nav-link-toggle', this.toggleMenuProxy);
            this.$showSidebarButton.on('click', this.showSidebarProxy);
            this.$hideSidebarButton.on('click', this.hideSidebarProxy);
            $(window).on('resize', this.resizeProxy);

            // update menus
            this._updateMenus();

            // toggle sidebar if too small
            if (this._isClientTooSmall() && !this._isSideBarHidden()) {
                $(window).trigger('resize');
            }

            // toggle sidebar, if after 1.5 seconds
            if (options.timeout > 0) {
                this._initTimeout();
            }
        }

        /**
         * Initialize the timeout to display/hide sidebar automatically.
         * @private
         */
        _initTimeout() {
            const that = this;
            const removeTimer = () => that.$element.removeTimer();
            const showSidebar = e => {
                if (that._isSideBarHidden()) {
                    that.$element.createTimer(function () {
                        removeTimer();
                        if (that._isSideBarHidden()) {
                            that._showSidebar(e);
                        }
                    }, 1500);
                }
            };
            const hideSidebar = e => {
                if (!that._isSideBarHidden()) {
                    that.$element.createTimer(function () {
                        removeTimer();
                        if (!that._isSideBarHidden()) {
                            that._hideSidebar(e);
                        }
                    }, 1500);
                }
            };
            that.$showSidebarButton.hover(showSidebar, removeTimer);
            that.$hideSidebarButton.hover(hideSidebar, removeTimer);
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
        _resize(e) {
            if (this._isClientTooSmall()) {
                this._hideSidebar(e);
                // } else if (this.wasHidden) {
                //     this._showSidebar(e);
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
                this.$hideSidebarButton.trigger('blur');
                this._toggleSidebar(e);
                //this.wasHidden = true;
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
                this.$showSidebarButton.trigger('blur');
                this._toggleSidebar(e);
                // this.wasHidden = false;
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
            if (this._isSideBarHidden()) {
                this.$navbarHorizontal.show(350);
            } else {
                this.$navbarHorizontal.hide(350);
            }
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
        // url to save menus state
        url: null,
        // show sidebar button
        showSidebarButton: '.show-sidebar',
        // hide sidebar button
        hideSidebarButton: '.hide-sidebar',
        // horizontal navigation bar
        navbarHorizontal: '.navbar-horizontal',
        // page content
        pageContent: '.page-content',
        // the timeout to display/hide sidebar automatically (0 = disabled)
        timeout: 1500,
        // the minimum width to hide sidebar
        minWidth: 1200,
        // texts
        showSidebar: 'Show Sidebar',
        hideSidebar: 'Hide Sidebar',
        showMenu: 'Expand',
        hideMenu: 'Collapse'
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
