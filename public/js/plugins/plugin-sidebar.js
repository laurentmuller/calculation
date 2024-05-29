/**! compression tag for ftp-deployment */

/**
 * ------------------------------------
 * Plugin to handle a sidebar.
 * ------------------------------------
 */
(function ($) {
    'use strict';

    // ------------------------------------
    // Public class definition
    // ------------------------------------
    const Sidebar = class {

        // -----------------------------
        // public functions
        // -----------------------------

        /**
         * Constructor
         *
         * @param {HTMLElement} element - the element to handle.
         * @param {Object|string} [options] - the plugin options.
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
            this.$element.off('shown.bs.collapse hidden.bs.collapse', 'div.collapse', this.toggleCollapseProxy);
            this.$showButton.off('click', this.showSidebarProxy);
            this.$hideButton.off('click', this.hideSidebarProxy);
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
            const options = this.options;
            this.$navbarHorizontal = $(options.horizontalNavbarSelector);
            this.$showButton = $(options.showSidebarSelector);
            this.$hideButton = $(options.hideSidebarSelector);
            this.$pageContent = $(options.pageContentSelector);

            // update collapse menus
            this._updateSiblingMenus();

            // update toggle buttons
            this._updateToggleButtons();

            // initialize the timeout
            this._initTimeout();

            // highlight url
            this._highlightPath();

            // create proxies
            this.toggleCollapseProxy = () => this._updateToggleButtons();
            this.showSidebarProxy = (e) => this._showSidebar(e);
            this.hideSidebarProxy = (e) => this._hideSidebar(e);
            this.resizeProxy = (e) => this._resize(e);

            // bind events
            this.$element.on('shown.bs.collapse hidden.bs.collapse', 'div.collapse', this.toggleCollapseProxy);
            this.$showButton.on('click', this.showSidebarProxy);
            this.$hideButton.on('click', this.hideSidebarProxy);
            $(window).on('resize', this.resizeProxy);

            // hide sidebar if too small
            $(window).trigger('resize');
        }

        /**
         * Initialize the timeout to display/hide sidebar automatically.
         * @private
         */
        _initTimeout() {
            // timeout?
            const timeout = this.options.timeout;
            if (timeout > 0) {
                this._initShowButtonTimeout(timeout);
                this._initHideButtonTimeout(timeout);
            }
        }

        /**
         * Initialize the timeout to show sidebar automatically.
         * @param {number} timeout
         * @private
         */
        _initShowButtonTimeout(timeout) {
            const that = this;
            if (that.$showButton.length === 0) {
                return;
            }
            const removeTimer = () => that.$showButton.removeTimer();
            that.$showButton.on('mouseenter', function (e) {
                if (!that._isSideBarVisible()) {
                    that.$showButton.createTimer(function () {
                        removeTimer();
                        that._showSidebar(e);
                    }, timeout);
                }
            }).on('mouseleave', removeTimer);
        }

        /**
         * Initialize the timeout to hide sidebar automatically.
         * @param {number} timeout
         * @private
         */
        _initHideButtonTimeout(timeout) {
            const that = this;
            if (that.$hideButton.length === 0) {
                return;
            }
            const removeTimer = () => that.$hideButton.removeTimer();
            that.$hideButton.on('mouseenter', function (e) {
                if (that._isSideBarVisible()) {
                    that.$hideButton.createTimer(function () {
                        removeTimer();
                        that._hideSidebar(e);
                    }, timeout);
                }
            }).on('mouseleave', removeTimer);
        }

        /**
         * Returns a value indicating if the client width is smaller than the minimum width option.
         *
         * @return {boolean}
         * @private
         */
        _isClientTooSmall() {
            return document.documentElement.clientWidth < this.options.minWidth;
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
            }
        }

        /**
         * Hide the sidebar if visible.
         *
         * @param {Event} e - the event.
         * @private
         */
        _hideSidebar(e) {
            if (this._isSideBarVisible()) {
                this._toggleSidebar(e);
            }
        }

        /**
         * Show the sidebar if hidden.
         *
         * @param {Event} e - the event.
         * @private
         */
        _showSidebar(e) {
            if (!this._isSideBarVisible()) {
                this._toggleSidebar(e);
            }
        }

        /**
         * Toggle the sidebar visibility.
         *
         * @param {Event} [e] - the event.
         * @private
         */
        _toggleSidebar(e) {
            if (e) {
                e.preventDefault();
            }
            $.hideTooltips();
            $.hideDropDownMenus();
            const that = this;
            that.$element.add(that.$pageContent).toggleClass('sidebar-show').promise().done(() => {
                that.$navbarHorizontal.toggle(that.options.duration, () => {
                    that._saveState();
                });
            });
        }

        /**
         * Update toggle buttons.
         * @private
         */
        _updateToggleButtons() {
            const options = this.options;
            this.$element.find('div.collapse').each(function (index, element) {
                const $element = $(element);
                const show = $element.hasClass('show');
                const title = show ? options.hideMenu : options.showMenu;
                $element.prev('.nav-link-toggle').toggleClass('active', show).attr('title', title);
            });
            this._saveState();
        }

        /**
         * Update siblings menus.
         * @private
         */
        _updateSiblingMenus() {
            if (!this.options.collapseSiblingMenus) {
                return;
            }
            const that = this;
            const rootId = that._ensureId(this.$element);
            this.$element.find('div.collapse').each(function (index, element) {
                const $element = $(element);
                const count = $element.parents('div.collapse').length;
                if (count === 0) {
                    $element.attr('data-bs-parent', rootId);
                } else {
                    $element.parents('div.collapse:first').each(function (index, element) {
                        const id = that._ensureId($(element));
                        $element.attr('data-bs-parent', id);
                    });
                }
            });
        }

        /**
         * Ensure that the given element has a unique identifier.
         *
         * @param {jQuery} $element te element to validate.
         * @return {string} the unique identifier.
         * @private
         */
        _ensureId($element) {
            const id = $element.attr('id');
            if (id) {
                return `#${id}`;
            }
            const randomClass = Math.floor(Math.random() * Date.now()).toString(16);
            $element.addClass(randomClass);
            return `.${randomClass}`;
        }

        /**
         * Add active class to selected url (if any).
         * @private
         */
        _highlightPath() {
            const pathname = this.options.pathname || '';
            const params = (new URL(document.location)).searchParams;
            const search = params.get(pathname) || window.location.pathname;
            let paths = search.split('/');
            while (paths.length > 2) {
                const path = paths.join('/');
                const $element = $(`.nav-item a[href="${path}"]`);
                if ($element.length) {
                    $element.addClass('active');
                    break;
                }
                paths.pop();
            }

            // active target element
            this.$element.find(`a[href="${search}"]:not(.navbar-brand)`).addClass('active bg-body-secondary');
        }

        /**
         * Returns if the sidebar is visible.
         *
         * @return {boolean} true if visible; false if hidden.
         * @private
         */
        _isSideBarVisible() {
            return this.$element.hasClass('sidebar-show');
        }

        /**
         * Gets the navigation state.
         * @return {Array.<string, boolean>}
         * @private
         */
        _getState() {
            const menus = {};
            const options = this.options;
            const selector = `div.collapse[id^='${options.menuPrefix}']`;
            this.$element.find(selector).each(function (index, element) {
                menus[element.id] = element.classList.contains('show');
            });
            menus[options.menuShow] = this._isSideBarVisible();
            return menus;
        }

        /**
         * Gets the cookie path.
         * @return {string}
         * @private
         */
        _getCookiePath() {
            return document.body.dataset.cookiePath || '/';
        }

        /**
         * Gets the cookie date.
         * @return {string}
         * @private
         */
        _getCookieDate() {
            const date = new Date();
            date.setFullYear(date.getFullYear() + 1);
            return date.toUTCString();
        }

        /**
         * Save the navigation state.
         * @private
         */
        _saveState() {
            const date = this._getCookieDate();
            const path = this._getCookiePath();
            const suffix = `expires=${date};path=${path};samesite=lax;secure`;
            const state = this._getState();
            for (const [key, value] of Object.entries(state)) {
                document.cookie = `${key}=${JSON.stringify(value)};${suffix}`;
            }
        }
    };

    // -----------------------------------
    // Default options
    // -----------------------------------
    Sidebar.DEFAULTS = {
        // the sidebar key used to save state
        menuShow: 'MENU_SIDEBAR_SHOW',
        // the menu prefix used to save state
        menuPrefix: 'MENU_SIDEBAR_',
        // show sidebar button selector
        showSidebarSelector: '.show-sidebar',
        // hide sidebar button selector
        hideSidebarSelector: '.hide-sidebar',
        // horizontal navigation bar selector
        horizontalNavbarSelector: '.navbar-horizontal',
        // page content selector
        pageContentSelector: '.page-content',
        // the timeout to display/hide sidebar automatically (0 = disabled)
        timeout: 1500,
        // the duration to show / hide menus
        duration: 350,
        // the minimum client width to hide sidebar
        minWidth: 1200,
        // texts
        showSidebar: 'Show sidebar',
        hideSidebar: 'Hide sidebar',
        showMenu: 'Expand menu',
        hideMenu: 'Collapse menu',
        // the path name to search in query parameters to highlight URL
        pathname: null,
        // collapse sibling's menus
        collapseSiblingMenus: true
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
