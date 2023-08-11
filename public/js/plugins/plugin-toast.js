/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function (window, $) {
    'use strict';

    // ------------------------------------
    // Toaster public class definition
    // ------------------------------------
    window.Toaster = {

        // ------------------------
        // Public API
        // ------------------------

        /**
         * Display a toast.
         *
         * @param {string} type - The type.
         * @param {string} message - The message.
         * @param {string} [title] - The title.
         * @param {Object} [options] - The options.
         * @returns {Object} this instance.
         */
        notify: function (type, message, title, options) {
            // merge and update options
            const data = this._getDataset(options);
            const settings = $.extend({}, this.DEFAULTS, data, options);
            settings.closeButton = settings.closeButton || !settings.autohide;
            settings.displayClose = settings.displayClose || settings.closeButton;
            settings.position = this._checkPosition(settings.position);
            settings.type = this._checkType(type);
            settings.message = message;
            settings.title = title;
            if (!settings.title && settings.displaySubtitle && settings.subtitle) {
                settings.title = settings.subtitle;
                settings.displaySubtitle = false;
                settings.subtitle = null;
            }

            // get container and create toast
            const $container = this._getContainer(settings);
            const $toast = this._createToast(settings);
            if (this._isPrepend(settings.position)) {
                $container.prepend($toast);
            } else {
                $container.append($toast);
            }

            //save identifier
            this.id = $container.attr('id');

            // show
            return this._showToast($toast, settings);
        },

        /**
         * Display a toast with information style.
         *
         * @param {string} message - The message.
         * @param {string} [title] - The title.
         * @param {Object} [options] - The options.
         * @returns {Object} this instance.
         */
        info: function (message, title, options) {
            return this.notify(this.NotificationTypes.INFO, message, title, options);
        },

        /**
         * Display a toast with success style.
         *
         * @param {string} message - The message.
         * @param {string} [title] - The title.
         * @param {Object} [options] - The options.
         * @returns {Object} this instance.
         */
        success: function (message, title, options) {
            return this.notify(this.NotificationTypes.SUCCESS, message, title, options);
        },

        /**
         * Display a toast with warning style.
         *
         * @param {string} message - The message.
         * @param {string} [title] - The title.
         * @param {Object} [options] - The options.
         * @returns {Object} this instance.
         */
        warning: function (message, title, options) {
            return this.notify(this.NotificationTypes.WARNING, message, title, options);
        },

        /**
         * Display a toast with danger style.
         *
         * @param {string} message - The message.
         * @param {string} [title] - The title.
         * @param {Object} [options] - The options.
         * @returns {Object} this instance.
         */
        danger: function (message, title, options) {
            return this.notify(this.NotificationTypes.DANGER, message, title, options);
        },

        /**
         * Display a toast with primary style.
         *
         * @param {string} message - The message.
         * @param {string} [title] - The title.
         * @param {Object} [options] - The options.
         * @returns {Object} this instance.
         */
        primary: function (message, title, options) {
            return this.notify(this.NotificationTypes.PRIMARY, message, title, options);
        },

        /**
         * Display a toast with secondary style.
         *
         * @param {string} message - The message.
         * @param {string} [title] - The title.
         * @param {Object} [options] - The options.
         * @returns {Object} this instance.
         */
        secondary: function (message, title, options) {
            return this.notify(this.NotificationTypes.SECONDARY, message, title, options);
        },

        /**
         * Display a toast with dark style.
         *
         * @param {string} message - The message.
         * @param {string} [title] - The title.
         * @param {Object} [options] - The options.
         * @returns {Object} this instance.
         */
        dark: function (message, title, options) {
            return this.notify(this.NotificationTypes.DARK, message, title, options);
        },

        /**
         * Remove this toasts DIV container.
         *
         * @returns {Object} this instance.
         */
        removeContainer: function () {
            if (this.id) {
                $(`#${this.id}`).remove();
                this.id = null;
            }
            return this;
        },

        /**
         * The allowed message types.
         */
        NotificationTypes: {
            INFO: 'info',
            SUCCESS: 'success',
            WARNING: 'warning',
            DANGER: 'danger',
            PRIMARY: 'primary',
            SECONDARY: 'secondary',
            DARK: 'dark'
        },

        /**
         * The allowed positions.
         */
        NotificationPositions: {
            TOP_LEFT: 'top-left',
            TOP_CENTER: 'top-center',
            TOP_RIGHT: 'top-right',

            CENTER_LEFT: 'center-left',
            CENTER_CENTER: 'center-center',
            CENTER_RIGHT: 'center-right',

            BOTTOM_LEFT: 'bottom-left',
            BOTTOM_CENTER: 'bottom-center',
            BOTTOM_RIGHT: 'bottom-right'
        },

        /**
         * The default options.
         */
        DEFAULTS: {
            // the target to append toasts container to
            target: 'body',

            // the container identifier
            id: 'div_toast_container_div',

            // the toasts width
            containerWidth: '350px',

            // the container margins
            marginTop: '20px',
            marginBottom: '20px',
            marginLeft: '20px',
            marginRight: '20px',

            // the show duration in milliseconds
            timeout: 4000,

            // the close button
            displayClose: true,
            closeTitle: 'Close',

            // the subtitle
            subtitle: null,
            displaySubtitle: false,

            // the toasts position
            position: 'bottom-right',

            // the toast icon.
            // Possible values:
            // - false: No icon is displayed.
            // - true: The default icon is displayed depending on the type.
            // - string: A custom icon is displayed.
            icon: true,

            // the progress bar height or 0 for none
            progress: 1,

            // auto hide after delay
            autohide: true,

            // handler when a toast is hidden
            onHide: null,

            // the jQuery selector to get data options from
            dataset: '#flashes'
        },

        // ------------------------
        // Private API
        // ------------------------

        /**
         * Check the notification type.
         *
         * @param {string} type - The type to validate.
         * @returns {string} A valid type.
         * @private
         */
        _checkType: function (type) {
            const types = this.NotificationTypes;
            switch (type) {
                case types.INFO:
                case types.SUCCESS:
                case types.WARNING:
                case types.DANGER:
                case types.PRIMARY:
                case types.SECONDARY:
                case types.DARK:
                    return type;
                default:
                    return types.INFO;
            }
        },

        /**
         * Check the position type.
         *
         * @param {string} position - The position to validate.
         * @returns {string} A valid position.
         * @private
         */
        _checkPosition: function (position) {
            const positions = this.NotificationPositions;
            switch (position) {
                case positions.TOP_LEFT:
                case positions.TOP_CENTER:
                case positions.TOP_RIGHT:
                case positions.CENTER_LEFT:
                case positions.CENTER_CENTER:
                case positions.CENTER_RIGHT:
                case positions.BOTTOM_LEFT:
                case positions.BOTTOM_CENTER:
                case positions.BOTTOM_RIGHT:
                    return position;
                default:
                    return positions.BOTTOM_RIGHT;
            }
        },

        /**
         * Returns if toast must be prepended or appended to the list; depending on the position.
         *
         * @param {string} position - The toast position.
         * @returns {boolean} true to prepend, false to append.
         * @private
         */
        _isPrepend: function (position) {
            const positions = this.NotificationPositions;
            switch (position) {
                case positions.TOP_LEFT:
                case positions.TOP_CENTER:
                case positions.TOP_RIGHT:
                case positions.CENTER_LEFT:
                case positions.CENTER_CENTER:
                case positions.CENTER_RIGHT:
                    return false;
                default: // BOTTOM_XXX
                    return true;
            }
        },

        /**
         * Gets target container.
         *
         * @param {Object} options - The toast options.
         * @returns {jQuery} The target container.
         * @private
         */
        _getTarget: function (options) {
            const $target = $(options.target);
            return $target.length ? $target : $('body');
        },

        /**
         * Gets or creates the toast container div.
         *
         * @param {Object} options - The toast options.
         * @returns {jQuery} The toasts container.
         * @private
         */
        _getContainer: function (options) {
            // get div
            const id = options.id;
            const $div = $('#' + id);

            // class
            const className = `toast-container toast-plugin position-fixed ${options.position}`;

            // style
            const css = {};
            options.position.split('-').forEach(function (edge) {
                const key = 'margin-' + edge;
                const value = options[key.camelize()];
                if (value) {
                    css[key] = value;
                }
            });

            // exist?
            if ($div.length === 0) {
                const $target = this._getTarget(options);
                return $('<div/>', {
                    id: id,
                    css: css,
                    class: className
                }).appendTo($target);
            }

            // update
            return $div.css(css).attr('class', className);
        },

        /**
         * Return if the dark them is created
         * @return {boolean}
         * @private
         */
        _isDarkTheme: function () {
            return document.documentElement.getAttribute('data-bs-theme') === 'dark';
        },

        /**
         * Return if the title must be created
         * @param {Object} options - The toast options.
         * @return {boolean}
         * @private
         */
        _isTitle: function (options) {
            return options.title || options.icon !== false || options.closeButton || options.displayClose || options.subtitle && options.displaySubtitle;
        },

        /**
         * Append the toast header.
         *
         * @param {jQuery} $parent - the parent to append header to.
         * @param {Object} options - The toast options.
         * @private
         */
        _createHeader: function ($parent, options) {
            if (!this._isTitle(options)) {
                return;
            }
            // header
            const clazz = `toast-header column-gap-2 text-bg-${options.type}`;
            const $div = $('<div/>', {
                'class': clazz
            });
            this._createHeaderIcon($div, options);
            this._createHeaderTitle($div, options);
            this._createHeaderSubtitle($div, options);
            this._createHeaderCloseButton($div, options);
            $parent.append($div);
        },

        /**
         * Append the header icon.
         *
         * @param {jQuery} $parent - the parent to append icon to.
         * @param {Object} options - The options.
         * @returns {jQuery|undefined} The icon or null if no icon.
         * @private
         */
        _createHeaderIcon: function ($parent, options) {
            if (options.icon === false) {
                return;
            }
            if ($.isString(options.icon)) {
                $parent.append($(options.icon));
                return;
            }
            let className = 'fas fa-lg fa-';
            switch (options.type) {
                case this.NotificationTypes.INFO:
                    className += 'info-circle';
                    break;
                case this.NotificationTypes.SUCCESS:
                    className += 'check-circle';
                    break;
                case this.NotificationTypes.WARNING:
                    className += 'exclamation-circle';
                    break;
                case this.NotificationTypes.DANGER:
                    className += 'exclamation-triangle';
                    break;
                default:
                    className += 'check-circle';
                    break;
            }

            // icon only
            if (!options.title && !options.displayClose && !options.displaySubtitle) {
                className += ' py-2';
            }

            // create
            const $icon = $('<i/>', {
                'class': className,
                'aria-hidden': true
            });
            $parent.append($icon);
        },

        /**
         * Append the header subtitle.

         * @param {jQuery} $parent - the parent to append header subtitle to.
         * @param {Object} options - The toast options.
         * @private
         */
        _createHeaderSubtitle: function ($parent, options) {
            if (options.displaySubtitle && options.subtitle) {
                const $subtitle = $('<small/>', {
                    'html': options.subtitle
                });
                $parent.append($subtitle);
            }
        },

        /**
         * Append the header close button.
         *
         * @param {jQuery} $parent - the parent to append close button to.
         * @param {Object} options - The toast options.
         * @private
         */
        _createHeaderCloseButton: function ($parent, options) {
            if (!options.displayClose) {
                return;
            }
            let className = 'btn-close btn-close-white ms-0';
            if (options.type === this.NotificationTypes.WARNING ||
                options.type === this.NotificationTypes.INFO) {
                className = 'btn-close ms-0';
            }
            const title = options.closeTitle || 'Close';
            const $button = $('<button/>', {
                'data-bs-dismiss': 'toast',
                'class': className,
                'aria-label': title,
                'type': 'button',
                'title': title
            });
            $parent.append($button);
        },

        /**
         * Append the header title.
         *
         * @param {jQuery} $parent - the parent to append header title to.
         * @param {Object} options - The toast options.
         * @private
         */
        _createHeaderTitle: function ($parent, options) {
            const $title = $('<span/>', {
                'class': 'me-auto',
                'html': options.title || ''
            });
            $parent.append($title);
        },

        /**
         * Append the toast message.
         *
         * @param {jQuery} $parent - the parent to append message to.
         * @param {Object} options - The toast options.
         * @private
         */
        _createBodyMessage: function ($parent, options) {
            const $body = $('<div/>', {
                'class': 'toast-body',
            });
            const $message = $('<div/>', {
                'html': options.message
            });
            $body.append($message);
            $parent.append($body);
        },

        /**
         * Creates the toast.
         *
         * @param {Object} options - The toast options.
         * @returns {jQuery} The div toast.
         * @private
         */
        _createToast: function (options) {
            const $toast = $('<div/>', {
                'role': 'alert',
                'aria-atomic': 'true',
                'aria-live': 'assertive',
                'class': `toast border-${options.type}`,
                'css': {
                    'max-width': options.containerWidth,
                    'flex-basis': options.containerWidth
                }
            });
            this._createHeader($toast, options);
            this._createBodyMessage($toast, options);
            this._createProgressBar($toast, options);

            return $toast;
        },

        /**
         * Append the bottom progress bar.
         *
         * @param {jQuery} $parent - the parent to append progress bar to.
         * @param {Object} options - The toast options.
         * @private
         */
        _createProgressBar: function ($parent, options) {
            if (!options.progress) {
                return;
            }
            let className = `bg-${options.type}`;
            if (options.type === this.NotificationTypes.DARK && this._isDarkTheme()) {
                className = 'bg-body-secondary';
            }
            const $bar = $('<div/>', {
                'class': `progress-bar overflow-hidden ${className}`,
                'role': 'progressbar',
                'aria-valuenow': '0',
                'aria-valuemin': '0',
                'aria-valuemax': '100',
            });
            const $progress = $('<div/>', {
                'class': 'progress bg-transparent rounded-0 rounded-bottom',
                'css': {
                    'height': `${options.progress}px`
                },
            });
            $progress.append($bar);
            $parent.append($progress);
        },

        /**
         * Show the toast.
         *
         * @param {jQuery} $toast - The toast to show.
         * @param {Object} options - The toast options.
         * @return {Object} This instance.
         * @private
         */
        _showToast: function ($toast, options) {
            const that = this;
            if (options.progress && options.timeout > 100) {
                const $progress = $toast.find('.progress-bar');
                if ($progress.length) {
                    $toast.on('show.bs.toast', function () {
                        options.start = new Date();
                        $toast.createInterval(that._updateProgressBar, 250, $progress, options);
                    }).on('hide.bs.toast', function () {
                        $toast.removeInterval();
                    });
                }
            }
            $toast.toast({
                delay: options.timeout,
                autohide: options.autohide
            }).on('hidden.bs.toast', function () {
                $toast.remove();
                if (typeof options.onHide === 'function') {
                    options.onHide(options);
                }
            }).toast('show');

            return that;
        },

        /**
         * Update the progress bar.
         *
         * @param {jQuery} $progress - The progress bar to update.
         * @param {Object} options - The toast options.
         * @private
         */
        _updateProgressBar: function ($progress, options) {
            const elapsed = new Date() - options.start;
            const percent = Math.ceil(((elapsed / options.timeout) * 100));
            if (percent > 100) {
                $progress.parents('.toast').removeInterval();
                $progress.remove();
            } else {
                $progress.css('width', `${percent}%`)
                    .attr('aria-valuenow', percent);
            }
        },

        /**
         * Gets dataset options.
         *
         * @param {Object} [options] - The toast options.
         * @return {Object}
         * @private
         */
        _getDataset: function (options) {
            if (options && options.dataset) {
                return $(options.dataset).data() || {};
            }
            return $(this.DEFAULTS.dataset).data() || {};
        }
    };
}(window, jQuery));
