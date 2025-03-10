/**
 * ------------------------------------
 * Plugin to handle a sidebar.
 * ------------------------------------
 */

$(function () {
    'use strict';

    // ------------------------------------
    // Public class definition
    // ------------------------------------

    /**
     * @property {jQuery<HTMLDivElement>} $verticalNavigation
     * @property {jQuery<HTMLDivElement>} $horizontalNavigation
     * @property {jQuery<HTMLButtonElement>} $showButton
     * @property {jQuery<HTMLButtonElement>} $hideButton
     * @property {jQuery<HTMLDivElement>} $verticalTarget
     * @property {jQuery<HTMLDivElement>} $horizontalTarget
     * @property {String} NAME
     * @property {Object} DEFAULTS
     */
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
            if (this.$verticalNavigation) {
                this.$verticalNavigation.off('shown.bs.collapse hidden.bs.collapse', 'div.collapse', this.toggleCollapseProxy);
            }
            this._destroyHideButton(false);
            this._destroyShowButton(false);
            this.$element.removeData(Sidebar.NAME);
            $(window).off('resize', this.resizeProxy);
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
            this.$verticalNavigation = this._ensureInstance(options.verticalSelector);
            this.$horizontalNavigation = this._ensureInstance(options.horizontalSelector);

            this.$verticalTarget = this._ensureInstance(options.verticalTarget);
            this.$horizontalTarget = this._ensureInstance(options.horizontalTarget);

            // create proxies
            this.toggleCollapseProxy = () => this._updateToggleButtons();
            this.showSidebarProxy = () => this._showVerticalNavigation();
            this.hideSidebarProxy = () => this._showHorizontalNavigation();
            this.resizeProxy = () => this._resize();

            this._initShowButton();
            this._initHideButton();
            this._highlightPath();
            if (this.$verticalNavigation) {
                this._initVerticalNavigation();
            }

            if (!this.$verticalTarget && this.$showButton) {
                this._destroyShowButton(true);
            }
            if (!this.$horizontalTarget && this.$hideButton) {
                this._destroyHideButton(true);
            }

            $(window).on('resize', this.resizeProxy)
                .trigger('resize');
        }

        /**
         * Initialize the button used to show vertical navigation.
         * @private
         */
        _initShowButton() {
            const options = this.options;
            this.$showButton = this._ensureInstance(options.showButtonSelector);
            if (!this.$showButton) {
                return;
            }
            this.$showButton.click(this.showSidebarProxy);
            if (options.timeout > 0) {
                this._initShowButtonTimeout(options.timeout);
            }
        }

        /**
         * Initialize the button used to hide vertical navigation.
         * @private
         */
        _initHideButton() {
            const options = this.options;
            this.$hideButton = this._ensureInstance(options.hideButtonSelector);
            if (!this.$hideButton) {
                return;
            }
            this.$hideButton.click(this.hideSidebarProxy);
            if (options.timeout > 0) {
                this._initHideButtonTimeout(options.timeout);
            }
        }

        /**
         * Initialize the vertical navigation.
         * @private
         */
        _initVerticalNavigation() {
            this.$verticalNavigation.on('shown.bs.collapse hidden.bs.collapse', 'div.collapse', this.toggleCollapseProxy);
            this._updateToggleButtons();
            this._updateSiblingMenus();
        }

        /**
         * Initialize the timeout to show vertical navigation automatically.
         * @param {number} timeout
         * @private
         */
        _initShowButtonTimeout(timeout) {
            const that = this;
            const removeTimer = () => that.$showButton.removeTimer();
            that.$showButton.on('mouseenter', function () {
                if (that._isHorizontalNavigationVisible()) {
                    that.$showButton.createTimer(function () {
                        removeTimer();
                        that._showVerticalNavigation();
                    }, timeout);
                }
            }).on('mouseleave click', removeTimer);
        }

        /**
         * Initialize the timeout to hide vertical navigation automatically.
         * @param {number} timeout
         * @private
         */
        _initHideButtonTimeout(timeout) {
            const that = this;
            const removeTimer = () => that.$hideButton.removeTimer();
            that.$hideButton.on('mouseenter', function () {
                if (that._isVerticalNavigationVisible()) {
                    that.$hideButton.createTimer(function () {
                        removeTimer();
                        that._showHorizontalNavigation();
                    }, timeout);
                }
            }).on('mouseleave click', removeTimer);
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
         * @private
         */
        _resize() {
            if (!this._isClientTooSmall() || !this._isVerticalNavigationVisible() || this.loading) {
                return;
            }
            this._showHorizontalNavigation();
        }

        /**
         * Show the horizontal navigation.
         * @private
         */
        _showHorizontalNavigation() {
            if (this._isHorizontalNavigationVisible()) {
                return;
            }
            if (!this.$horizontalNavigation) {
                this._loadHorizontalNavigation();
            } else {
                this._toggleNavigation();
            }
        }

        /**
         * Show the vertical navigation.
         * @private
         */
        _showVerticalNavigation() {
            if (this._isVerticalNavigationVisible()) {
                return;
            }
            if (!this.$verticalNavigation) {
                this._loadVerticalNavigation();
            } else {
                this._toggleNavigation();
            }
        }

        /**
         * Load the vertical navigation content.
         * @private
         */
        _loadVerticalNavigation() {
            const that = this;
            const options = that.options;
            if (!options.verticalUrl || !that.$verticalTarget) {
                that._destroyShowButton(true);
                return;
            }
            that.loading = true;
            $.getJSON(options.verticalUrl, function (response) {
                if (!response) {
                    that._destroyShowButton(true);
                    return;
                }
                that.$verticalNavigation = $(response);
                that._initVerticalNavigation();
                that.$verticalTarget.prepend(that.$verticalNavigation);
                if (!that.$hideButton) {
                    that._initHideButton();
                }
                that._toggleNavigation();
            }).fail(function () {
                that._destroyShowButton(true);
            }).always(function () {
                that.loading = false;
            });
        }

        /**
         * Load the horizontal navigation content.
         * @private
         */
        _loadHorizontalNavigation() {
            const that = this;
            const options = that.options;
            if (!options.horizontalUrl || !that.$horizontalTarget) {
                that._destroyHideButton(true);
                return;
            }
            that.loading = true;
            $.getJSON(options.horizontalUrl, function (response) {
                if (!response) {
                    that._destroyHideButton(true);
                    return;
                }
                that.$horizontalNavigation = $(response);
                that.$horizontalTarget.prepend(that.$horizontalNavigation);
                if (!that.$showButton) {
                    that._initShowButton();
                }
                that._toggleNavigation();
            }).fail(function () {
                that._destroyHideButton(true);
            }).always(function () {
                that.loading = false;
            });
        }

        /**
         * Toggle the navigation visibility.
         * @private
         */
        _toggleNavigation() {
            $.hideTooltips();
            $.hideDropDownMenus();
            const className = this.options.sidebarClassName;
            this.$element.toggleClass(className)
                .trigger('toggle-navigation');
            this._highlightPath();
            this._saveState();
        }

        /**
         * Update toggle buttons.
         * @private
         */
        _updateToggleButtons() {
            const options = this.options;
            this.$verticalNavigation.find('div.collapse').each(function (index, element) {
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
            const rootId = that._ensureId(this.$verticalNavigation);
            this.$verticalNavigation.find('div.collapse').each(function (index, element) {
                const $element = $(element);
                const count = $element.parents('div.collapse').length;
                if (count === 0) {
                    $element.attr('data-bs-parent', rootId);
                } else {
                    $element.parents('div.collapse:first').each(function (index, child) {
                        const id = that._ensureId($(child));
                        $element.attr('data-bs-parent', id);
                    });
                }
            });
        }

        /**
         * Ensure that the given element has a unique identifier.
         *
         * @param {jQuery|HTMLElement|*} $element the element to validate or update.
         * @return {string} the unique identifier selector.
         * @private
         */
        _ensureId($element) {
            let id = $element.attr('id');
            if (!id) {
                id = 'id-' + Math.floor(Math.random() * Date.now()).toString(16);
                $element.attr('id', id);
            }
            return '#' + id;
        }

        /**
         * Add active class to the selected url (if any).
         * @private
         */
        _highlightPath() {
            if (!this.options.selectionClassName) {
                return;
            }
            const $navigations = this._getNavigations();
            if (!$navigations) {
                return;
            }

            const pathname = this.options.pathname || '';
            const params = (new URL(document.location)).searchParams;
            const search = params.get(pathname) || window.location.pathname;
            let paths = search.split('/');
            while (paths.length > 1) {
                const path = paths.join('/');
                const selector = `a[href="${path}"]`;
                const $element = $navigations.find(selector);
                if ($element.length) {
                    // skip brand class
                    if (!$element.hasClass('navbar-brand')) {
                        $element.addClass(this.options.selectionClassName);
                    }
                    break;
                }
                paths.pop();
            }
        }

        /**
         * Gets the horizontal and vertical navigations, if applicable.
         * @returns {jQuery|HTMLElement|*|null}
         * @private
         */
        _getNavigations() {
            if (this.$horizontalNavigation && this.$verticalNavigation) {
                return this.$horizontalNavigation.add(this.$verticalNavigation);
            } else if (this.$horizontalNavigation) {
                return this.$horizontalNavigation;
            } else if (this.$verticalNavigation) {
                return this.$verticalNavigation;
            } else {
                return null;
            }
        }

        /**
         * Returns if the vertical navigation is visible.
         *
         * @return {boolean} true if visible; false if hidden.
         * @private
         // */
        _isVerticalNavigationVisible() {
            const className = this.options.sidebarClassName;
            return this.$element.hasClass(className);
        }

        /**
         * Returns if the horizontal navigation is visible.
         *
         * @return {boolean} true if visible; false if hidden.
         * @private
         */
        _isHorizontalNavigationVisible() {
            return !this._isVerticalNavigationVisible();
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
            this.$verticalNavigation.find(selector).each(function (index, element) {
                menus[element.id] = element.classList.contains('show');
            });
            menus[options.menuShow] = this._isVerticalNavigationVisible();
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

        /**
         * Find an element.
         *
         * @param {string} selector the selector.
         * @return {jQuery|HTMLElement|*|null}
         * @private
         */
        _ensureInstance(selector) {
            const $instance = $(selector);
            if ($instance && $instance.length) {
                return $instance;
            }
            return null;
        }

        /**
         * Unbind the show button
         * @param {boolean} remove true to remove the button
         * @private
         */
        _destroyShowButton(remove) {
            if (this.$showButton) {
                this.$showButton.off('click', this.showSidebarProxy);
                if (remove) {
                    this.$showButton.remove();
                }
                this.$showButton = null;
            }
        }

        /**
         * Unbind the hide button
         * @param {boolean} remove true to remove the button
         * @private
         */
        _destroyHideButton(remove) {
            if (this.$hideButton) {
                this.$hideButton.off('click', this.hideSidebarProxy);
                if (remove) {
                    this.$hideButton.remove();
                }
                this.$hideButton = null;
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
        // the timeout to display/hide sidebar automatically (0 = disabled)
        timeout: 1500,
        // the minimum client width to hide sidebar
        minWidth: 1200,
        // texts
        showMenu: 'Expand menu',
        hideMenu: 'Collapse menu',
        // the path name to search in query parameters to highlight URL
        pathname: null,
        // collapse sibling's menus
        collapseSiblingMenus: true,
        // the URL to load the vertical navigation
        verticalUrl: null,
        // the selector where to prepend the loaded vertical navigation
        verticalTarget: 'body',
        // vertical navigation selector
        verticalSelector: '.navbar-vertical',
        // the URL to load the horizontal navigation
        horizontalUrl: null,
        // the selector where to prepend the loaded horizontal navigation
        horizontalTarget: 'body',
        // horizontal navigation selector
        horizontalSelector: '.navbar-horizontal',
        // show vertical navigation button selector
        showButtonSelector: '.show-sidebar',
        // hide vertical navigation button selector
        hideButtonSelector: '.hide-sidebar',
        // the vertical navigation show class name
        sidebarClassName: 'sidebar-show',
        // the selected path class name
        selectionClassName: 'bg-body-secondary',
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

});
