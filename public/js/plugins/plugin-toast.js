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
         * @returns {JQuery} this instance.
         */
        notify: function (type, message, title, options) {
            // merge options
            const settings = $.extend({}, this.DEFAULTS, options);
            settings.closeButton = settings.closeButton || !settings.autohide;
            settings.position = this._checkPosition(settings.position);
            settings.type = this._checkType(type);
            settings.message = message;
            settings.title = title;
            if (!settings.title && settings.displaySubtitle) {
                settings.displaySubtitle = false;
                settings.title = settings.subtitle;
                settings.subtitle = null;
            }

            // create DOM
            const $container = this._getContainer(settings);
            const $title = this._createTitle(settings);
            const $message = this._createMessage(settings);
            const $progress = this._createProgressBar(settings);
            const $toast = this._createToast(settings);

            // save identifier
            this.id = $container.attr('id');

            // add children
            if ($title) {
                $toast.append($title);
            }
            $toast.append($message);
            if ($progress) {
                $toast.append($progress);
            }
            if (this._isPrepend(settings.position)) {
                $container.prepend($toast);
            } else {
                $container.append($toast);
            }

            // update background color
            const background = $toast.css('background-color');
            if (background && background.startsWith('rgba')) {
                const start = background.indexOf('(');
                const end = background.indexOf(')', start + 1);
                if (start !== -1 && end !== -1) {
                    const rgb = background.substring(start + 1, end).split(',').splice(0, 3).join(',');
                    $toast.css('background-color', `rgb(${rgb})`);
                }
            }

            // show
            return this._showToast($toast, settings);
        },

        /**
         * Display a toast with information style.
         *
         * @param {string} message - The message.
         * @param {string} [title] - The title.
         * @param {Object} [options] - The options.
         * @returns {JQuery} this instance.
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
         * @returns {JQuery} this instance.
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
         * @returns {JQuery} this instance.
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
         * @returns {JQuery} this instance.
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
         * @returns {JQuery} this instance.
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
         * @returns {JQuery} this instance.
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
         * @returns {JQuery} this instance.
         */
        dark: function (message, title, options) {
            return this.notify(this.NotificationTypes.DARK, message, title, options);
        },

        /**
         * Remove this toasts DIV container.
         *
         * @returns {JQuery} this instance.
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

            // the container identifier prefix
            id: 'div_toaster_container',

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

            // the toast z-index
            zIndex: 3,

            // auto hide after delay
            autohide: true,

            // handler when a toast is hidden
            onHide: null
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
         * Gets or creates the toast container div.
         *
         * @param {Object} options - The toast options.
         * @returns {JQuery} The toasts container.
         * @private
         */
        _getContainer: function (options) {
            // check if div is already created
            const id = options.id;
            const $div = $('#' + id);
            if ($div.length) {
                return $div;
            }

            // global style
            const css = {
                'z-index': options.zIndex
            };

            // margin styles
            options.position.split('-').forEach(function (edge) {
                const key = 'margin-' + edge;
                const value = options[key.camelize()];
                if (value) {
                    css[key] = value;
                }
            });

            // target
            let $target = $(options.target);
            if ($target.length === 0) {
                $target = $('body');
            }

            // create and append
            return $('<div/>', {
                id: id,
                css: css,
                class: 'toast-plugin ' + options.position
            }).appendTo($target);
        },

        /**
         * Creates the div title.
         *
         * @param {Object} options - The toast options.
         * @returns {JQuery} The div title or null if no title.
         * @private
         */
        _createTitle: function (options) {
            if (options.title || options.icon !== false || options.displayClose || options.subtitle && options.displaySubtitle) {
                // header
                const clazz = 'toast-header toast-header-' + options.type;
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
                    'class': 'mr-auto',
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
         * @returns {JQuery} The icon or null if no icon.
         * @private
         */
        _createIcon: function (options) {
            if (options.icon === false) {
                return null;
            } else if ($.isString(options.icon)) {
                return $(options.icon);
            }


            let clazz = 'mr-2 mt-1 fas fa-lg fa-';
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

            // icon only ?
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
         * @returns {JQuery} The subtitle or null if no subtitle defined.
         * @private
         */
        _createSubtitle: function (options) {
            if (options.displaySubtitle && options.subtitle) {
                return $('<small/>', {
                    'class': 'ml-2',
                    'html': options.subtitle
                });
            }
            return null;
        },

        /**
         * Creates the close button.
         *
         * @param {Object} options - The toast options.
         * @returns {JQuery} The close button or null if no button.
         * @private
         */
        _createCloseButton: function (options) {
            if (options.displayClose) {
                const title = options.closeTitle || 'Close';
                const $button = $('<button/>', {
                    'class': 'close ml-2 mb-1',
                    'data-dismiss': 'toast',
                    'aria-label': title,
                    'type': 'button',
                    'title': title,
                    'css': {
                         'color': 'inherit'
                    }
                });
                const $span = $('<span />', {
                    'aria-hidden': 'true',
                    'html': '&times;'
                });
                return $button.append($span);
            }
            return null;
        },

        /**
         * Creates the div message.
         *
         * @param {Object} options - The toast options.
         * @returns {JQuery} The div message.
         * @private
         */
        _createMessage: function (options) {
            return $('<div/>', {
                'class': 'toast-body',
                'html': options.message
            });
        },

        /**
         * Creates the div toast.
         *
         * @param {Object} options - The toast options.
         * @returns {JQuery} The div toast.
         * @private
         */
        _createToast: function (options) {
            return $('<div/>', {
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
        },

        /**
         * Creates the progress bar.
         *
         * @param {Object} options - The toast options.
         * @returns {JQuery} The progress bar or null if no progress.
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
         * @param {JQuery} $toast - The toast to show.
         * @param {Object} options - The toast options.
         * @return {Object} This instance.
         * @private
         */
        _showToast: function ($toast, options) {
            const that = this;
            $toast.toast({
                delay: options.timeout,
                autohide: options.autohide
            });
            $toast.on('show.bs.toast', function () {
                if (options.progress) {
                    const $progress = $toast.find('.progress-bar');
                    if ($progress.length) {
                        const timeout = options.timeout;
                        const endTime = new Date().getTime() + timeout;
                        $toast.createInterval(that._updateProgress, 100, $progress, endTime, timeout);
                    }
                }
            }).on('hide.bs.toast', function () {
                if (options.progress) {
                    $toast.removeInterval();
                }
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
         * @param {JQuery} $progress - The progress bar to update.
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
        }
    };
}(window, jQuery));
