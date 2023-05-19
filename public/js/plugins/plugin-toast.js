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
            const data = this._getDataset(options.dataset);
            const settings = $.extend({}, this.DEFAULTS, data, options);
            settings.closeButton = settings.closeButton || !settings.autohide;
            settings.displayClose = settings.displayClose || settings.closeButton;
            settings.position = this._checkPosition(settings.position);
            settings.type = this._checkType(type);
            settings.message = message;
            settings.title = title;
            if (!settings.title && settings.displaySubtitle) {
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
                $('#' + this.id).remove();
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
            dataset: null
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
            const className = 'toast-container toast-plugin ' + options.position;

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
         * Creates the div title.
         *
         * @param {Object} options - The toast options.
         * @returns {jQuery|undefined} The div title or null if no title.
         * @private
         */
        _createTitle: function (options) {
            if (options.title || options.icon !== false || options.closeButton || options.displayClose || options.subtitle && options.displaySubtitle) {
                // header
                let clazz = 'toast-header bg-' + options.type;
                switch (options.type) {
                    case this.NotificationTypes.INFO:
                    case this.NotificationTypes.WARNING:
                        clazz += ' text-dark';
                        break;
                    default:
                        clazz += ' text-white';
                        break;
                }
                const $div = $('<div/>', {
                    'class': clazz
                });

                // icon
                const $icon = this._createIcon(options);
                if ($icon) {
                    $div.append($icon);
                }

                // title
                const $title = $('<span/>', {
                    'class': 'me-auto',
                    'html': options.title || ''
                });
                $div.append($title);

                // sub-title
                const $subtitle = this._createSubtitle(options);
                if ($subtitle) {
                    $div.append($subtitle);
                }

                // close button
                const $close = this._createCloseButton(options);
                if ($close) {
                    $div.append($close);
                }

                return $div;
            }
            return null;
        },

        /**
         * Creates the icon title.
         *
         * @param {Object} options - The options.
         * @returns {jQuery|undefined} The icon or null if no icon.
         * @private
         */
        _createIcon: function (options) {
            if (options.icon === false) {
                return null;
            } else if ($.isString(options.icon)) {
                return $(options.icon);
            }


            let clazz = 'me-2 fas fa-lg fa-';// mt-1
            switch (options.type) {
                case this.NotificationTypes.INFO:
                    clazz += 'info-circle';
                    break;
                case this.NotificationTypes.SUCCESS:
                    clazz += 'check-circle';
                    break;
                case this.NotificationTypes.WARNING:
                    clazz += 'exclamation-circle';
                    break;
                case this.NotificationTypes.DANGER:
                    clazz += 'exclamation-triangle';
                    break;
                default:
                    clazz += 'check-circle';
                    break;
            }

            // icon only
            if (!options.title && !options.displayClose && !options.displaySubtitle) {
               clazz += ' py-2';
            }

            // create
            return $('<i/>', {
                'class': clazz,
                'aria-hidden': true
            });
        },

        /**
         * Creates the subtitle.
         *
         * @param {Object} options - The toast options.
         * @returns {jQuery|undefined} The subtitle or null if no subtitle defined.
         * @private
         */
        _createSubtitle: function (options) {
            if (options.displaySubtitle && options.subtitle) {
                return $('<small/>', {
                    //'class': 'ms-2',
                    'html': options.subtitle
                });
            }
            return null;
        },

        /**
         * Creates the close button.
         *
         * @param {Object} options - The toast options.
         * @returns {jQuery|undefined} The close button or null if no button.
         * @private
         */
        _createCloseButton: function (options) {
            if (options.displayClose) {
                const title = options.closeTitle || 'Close';
                return $('<button/>', {
                    'data-bs-dismiss': 'toast',
                    'aria-label': title,
                    'class': 'btn-close',
                    'type': 'button',
                    'title': title
                });
            }
            return null;
        },

        /**
         * Creates the div message.
         *
         * @param {Object} options - The toast options.
         * @returns {jQuery} The div message.
         * @private
         */
        _createMessage: function (options) {
            const $body = $('<div/>', {
                'class': 'toast-body',
            });
            const $message = $('<div/>', {
                'html': options.message
            });
            return $body.append($message);
        },

        /**
         * Creates the div toast.
         *
         * @param {Object} options - The toast options.
         * @returns {jQuery} The div toast.
         * @private
         */
        _createToast: function (options) {
            const $title = this._createTitle(options);
            const $message = this._createMessage(options);
            const $progress = this._createProgressBar(options);
            const $toast = $('<div/>', {
                'role': 'alert',
                'aria-atomic': 'true',
                'aria-live': 'assertive',
                'class': 'toast border-' + options.type,
                'css': {
                    'max-width': options.containerWidth,
                    'flex-basis': options.containerWidth,
                    'border-style': 'solid',
                    'border-width': '1px'
                }
            });
            if ($title) {
                $toast.append($title);
            }
            $toast.append($message);
            if ($progress) {
                $toast.append($progress);
            }
            return $toast;
        },

        /**
         * Creates the progress bar.
         *
         * @param {Object} options - The toast options.
         * @returns {jQuery|undefined} The progress bar or null if no progress.
         * @private
         */
        _createProgressBar: function (options) {
            if (!options.progress) {
                return null;
            }
            const $bar = $('<div/>', {
                'class': 'progress-bar bg-' + options.type,
                'role': 'progressbar',
                'aria-valuenow': '0',
                'aria-valuemin': '0',
                'aria-valuemax': '100',
            });
            const $progress = $('<div/>', {
                'class': 'progress bg-transparent',
                'css': {
                    'height': options.progress + 'px'
                },
            });
            $progress.append($bar);

            return $progress;
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
            if (options.progress) {
                $toast.on('show.bs.toast', function () {
                    const $progress = $toast.find('.progress-bar');
                    if ($progress.length) {
                        const timeout = options.timeout;
                        const endTime = new Date().getTime() + timeout;
                        $toast.createInterval(that._updateProgress, 100, $progress, endTime, timeout);
                    }
                }).on('hide.bs.toast', function () {
                    $toast.removeInterval();
                });
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
         * @param {Number} endTime - The end time.
         * @param {Number} timeout - The timeout in milliseconds.
         * @private
         */
        _updateProgress: function ($progress, endTime, timeout) {
            const time = new Date().getTime();
            const delta = (endTime - time) / timeout;
            const percent = Math.min(100 - delta * 100, 100);
            $progress.css('width', percent + '%').attr('aria-valuenow', percent);
            if (percent >= 100) {
                $progress.parents('.toast').removeInterval();
            }
        },

        /**
         * Gets dataset options.
         *
         * @param {string} [dataset] - The toast options.
         * @return {Object}
         * @private
         */
        _getDataset: function (dataset) {
            if (dataset) {
                return $(dataset).data() || {};
            }
            return {};
        }
    };
}(window, jQuery));
