/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function (window, $) {
    'use strict';

    /**
     * Shared Instance.
     */
    window.Toaster = {

        // ------------------------
        // Public API
        // ------------------------

        /**
         * Display a toast.
         * 
         * @param {string}
         *            type - The type.
         * @param {string}
         *            message - The message.
         * @param {string}
         *            [title] - The title.
         * @param {Object}
         *            [options] - The options.
         * @returns {jQuery} this instance.
         */
        notify: function (type, message, title, options) {
            // merge options
            const settings = $.extend({}, this.DEFAULTS, options);
            settings.position = this.checkPosition(settings.position);
            settings.type = this.checkType(type);
            settings.message = message;
            settings.title = title;

            // create DOM
            const $container = this.getContainer(settings);
            const $title = this.createTitle(settings);
            const $message = this.createMessage(settings);
            const $toast = this.createToast(settings);

            // save identifier
            this.containerId = $container.attr('id');

            // add children
            if ($title) {
                $toast.append($title);
            }
            $toast.append($message);
            if (this.isPrepend(settings)) {
                $container.prepend($toast);
            } else {
                $container.append($toast);
            }

            // update close style
            if ($title && settings.closeButton) {
                $title.find('.close').css({
                    'background-color': $title.css('background-color'),
                    'color': $title.css('color')
                });
            }

            // show
            return this.showToast($toast, settings);
        },

        /**
         * Display a toast with info style.
         * 
         * @param {string}
         *            message - The message.
         * @param {string}
         *            [title] - The title.
         * @param {Object}
         *            [options] - The options.
         * @returns {jQuery} this instance.
         */
        info: function (message, title, options) {
            return this.notify(this.NotificationTypes.INFO, message, title, options);
        },

        /**
         * Display a toast with success style.
         * 
         * @param {string}
         *            message - The message.
         * @param {string}
         *            [title] - The title.
         * @param {Object}
         *            [options] - The options.
         * @returns {jQuery} this instance.
         */
        success: function (message, title, options) {
            return this.notify(this.NotificationTypes.SUCCESS, message, title, options);
        },

        /**
         * Display a toast with warning style.
         * 
         * @param {string}
         *            message - The message.
         * @param {string}
         *            [title] - The title.
         * @param {Object}
         *            [options] - The options.
         * @returns {jQuery} this instance.
         */
        warning: function (message, title, options) {
            return this.notify(this.NotificationTypes.WARNING, message, title, options);
        },

        /**
         * Display a toast with danger style.
         * 
         * @param {string}
         *            message - The message.
         * @param {string}
         *            [title] - The title.
         * @param {Object}
         *            [options] - The options.
         * @returns {jQuery} this instance.
         */
        danger: function (message, title, options) {
            return this.notify(this.NotificationTypes.DANGER, message, title, options);
        },

        /**
         * Display a toast with primary style.
         * 
         * @param {string}
         *            message - The message.
         * @param {string}
         *            [title] - The title.
         * @param {Object}
         *            [options] - The options.
         * @returns {jQuery} this instance.
         */
        primary: function (message, title, options) {
            return this.notify(this.NotificationTypes.PRIMARY, message, title, options);
        },

        /**
         * Display a toast with secondary style.
         * 
         * @param {string}
         *            message - The message.
         * @param {string}
         *            [title] - The title.
         * @param {Object}
         *            [options] - The options.
         * @returns {jQuery} this instance.
         */
        secondary: function (message, title, options) {
            return this.notify(this.NotificationTypes.SECONDARY, message, title, options);
        },

        /**
         * Display a toast with dark style.
         * 
         * @param {string}
         *            message - The message.
         * @param {string}
         *            [title] - The title.
         * @param {Object}
         *            [options] - The options.
         * @returns {jQuery} this instance.
         */
        dark: function (message, title, options) {
            return this.notify(this.NotificationTypes.DARK, message, title, options);
        },

        /**
         * Remove this toasts DIV container.
         * 
         * @returns {jQuery} this instance.
         */
        removeContainer: function () {
            if (this.containerId) {
                $('#' + this.containerId).remove();
                this.containerId = null;
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
            containerId: 'div_toasts_div',

            // the sub-title
            subtitle: null,
            displaySubtitle: false,

            // the toasts width
            containerWidth: 350,

            // the toasts position
            position: 'bottom-right',

            // the container margins
            marginTop: 20,
            marginBottom: 10,
            marginLeft: 20,
            marginRight: 20,

            // the show duration in milliseconds
            timeout: 4000,

            // the close button
            closeButton: true,
            closeText: 'Close',

            // the toast icon.
            // Possible values:
            // - false: No icon is displayed.
            // - true: The default icon is displayed depending on the type.
            // - string: A custom icon is displayed.
            icon: true,

            // the toast z-index
            zindex: 3,

            // auto hide
            autohide: true,

            // handler when a toast is hidden
            onHide: null
        },

        // ------------------------
        // Private API
        // ------------------------

        /**
         * Check the type.
         * 
         * @param {string}
         *            type - The type to valdiate.
         * 
         * @returns {string} A valid type.
         */
        checkType: function (type) {
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
         * @param {string}
         *            type - The position type.
         * 
         * @returns {string} A valid position.
         */
        checkPosition: function (position) {
            const positions = this.NotificationPositions;
            switch (position) {
            case positions.TOP_LEFT:
            case positions.TOP_CENTER:
            case positions.TOP_RIGHT:
            case positions.CENTER_LEFT:
            case positions.CENTER_CENTER:
            case positions.CENTER_RIGHT:
                return position;
            case positions.BOTTOM_LEFT:
            case positions.BOTTOM_CENTER:
            case positions.BOTTOM_RIGHT:
                return position;
            default:
                return positions.BOTTOM_RIGHT;
            }
        },

        /**
         * Returns if toast must be prepend or append to the list; depending of
         * the position.
         * 
         * @param {Object}
         *            [options] - The custom options.
         * 
         * @returns {boolean} true to prepend (top positions), false to append
         *          (bottom positions).
         */
        isPrepend: function (options) {
            const positions = this.NotificationPositions;
            switch (options.position) {
            case positions.TOP_LEFT:
            case positions.TOP_CENTER:
            case positions.TOP_RIGHT:
            case positions.CENTER_LEFT:
            case positions.CENTER_CENTER:
            case positions.CENTER_RIGHT:
                return false;
            default:  // BOTTOM_XXX
                return true;
            }
        },

        /**
         * Returns the given value with the appended pixel (px) unit.
         * 
         * @param {int}
         *            value - The value to append unit to.
         * 
         * @return {string} The value within the pixel unit.
         */
        toPixel: function (value) {
            return value + 'px';
        },

        /**
         * Gets or creates the toasts container div.
         * 
         * @param {Object}
         *            [options] - The custom options.
         * 
         * @returns {jQuery} The toasts container.
         */
        getContainer: function (options) {
            // check if div is already created
            const id = options.containerId;
            const $div = $('#' + id);
            if ($div.length) {
                return $div;
            }

            // global style
            const css = {
                'position': 'fixed',
                'z-index': options.zindex
            };

            // position
            const positions = this.NotificationPositions;
            switch (options.position) {
            case positions.TOP_LEFT:
                css.top = 0;
                css.left = 0;
                css['margin-top'] = this.toPixel(options.marginTop);
                css['margin-left'] = this.toPixel(options.marginLeft);                
                break;
            case positions.TOP_CENTER:
                css.top = 0;
                css.left = '50%';
                css['margin-top'] = this.toPixel(options.marginTop);
                css.transform = 'translateX(-50%)';
                break;
            case positions.TOP_RIGHT:
                css.top = 0;
                css.right = 0;
                css['margin-top'] = this.toPixel(options.marginTop);
                css['margin-right'] = this.toPixel(options.marginRight);
                break;
            
            case positions.CENTER_LEFT:
                css.top = '50%';
                css.left = 0;
                css.transform ='translateY(-50%)';
                css['margin-left'] = this.toPixel(options.marginLeft);
                break;
            case positions.CENTER_CENTER:
                css.top = '50%';
                css.left = '50%';
                css.transform = 'translate(-50%,-50%)';
                break;
            case positions.CENTER_RIGHT:
                css.top = '50%';
                css.right = 0;
                css.transform ='translateY(-50%)';
                css['margin-right'] = this.toPixel(options.marginRight);
                break;
                
            case positions.BOTTOM_LEFT:
                css.bottom = 0;
                css.left = 0;
                css['margin-bottom'] = this.toPixel(options.marginBottom);
                css['margin-left'] = this.toPixel(options.marginLeft);
                break;
            case positions.BOTTOM_CENTER:
                css.bottom = 0;
                css.left = '50%';
                css['margin-bottom'] = this.toPixel(options.marginBottom);
                css.transform = 'translateX(-50%)';
                break;  
            case positions.BOTTOM_RIGHT:
            default:
                css.bottom = 0;
                css.right = 0;
                css['margin-bottom'] = this.toPixel(options.marginBottom);
                css['margin-right'] = this.toPixel(options.marginRight);
                break;
            }

            // target
            const $target = $(options.target);

            // create and append
            return $('<div/>', {
                id: id,
                css: css
            }).appendTo($target);
        },

        /**
         * Creates the div title.
         * 
         * @param {Object}
         *            options - The custom options.
         * 
         * @returns {jQuery} The div title or null if no title.
         */
        createTitle: function (options) {
            if (options.title || options.icon !== false || options.closeButton || options.subtitle && options.displaySubtitle) {
                // header
                const clazz = 'toast-header toast-header-' + options.type;
                const $div = $('<div/>', {
                    'class': clazz
                });

                // icon
                const $icon = this.createIcon(options);
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
                const $subtitle = this.createSubtitle(options);
                if ($subtitle) {
                    $div.append($subtitle);
                }

                // close button
                const $close = this.createCloseButton(options);
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
         * @param {Object}
         *            options - The options.
         * 
         * @returns {jQuery} The icon or null if no icon.
         */
        createIcon: function (options) {
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

            // create
            return $('<i/>', {
                'class': clazz,
                'aria-hidden': true
            });
        },

        /**
         * Creates the sub-title.
         * 
         * @param {Object}
         *            options - The custom options.
         * 
         * @returns {jQuery} The sub-title or null if no sub-title defined.
         */
        createSubtitle: function (options) {
            if (options.displaySubtitle && options.subtitle) {
                return $('<span/>', {
                    'class': 'small mt-1 ml-2',
                    'html': options.subtitle
                });
            }
            return null;
        },

        /**
         * Creates the close button.
         * 
         * @param {Object}
         *            options - The custom options.
         * 
         * @returns {jQuery} The close button or null if no button.
         */
        createCloseButton: function (options) {
            if (options.closeButton) {
                const $span = $('<span />', {
                    'aria-hidden': 'true',
                    'html': '&times;'
                });
                const title = options.closeText || 'Close';
                const $button = $('<button/>', {
                    'class': 'close ml-2 mb-1',
                    'data-dismiss': 'toast',
                    'aria-label': title,
                    'title': title,
                    'type': 'button'
                });
                return $button.append($span);
            }
            return null;
        },

        /**
         * Creates the div message.
         * 
         * options - The custom options.
         * 
         * @returns {jQuery} The div message.
         */
        createMessage: function (options) {
            return $('<div/>', {
                'class': 'toast-body',
                'html': options.message
            });
        },

        /**
         * Creates the div toast.
         * 
         * @param {Object}
         *            options - The custom options.
         * 
         * @returns {jQuery} The div toast.
         */
        createToast: function (options) {
            return $('<div/>', {
                'role': 'alert',
                'aria-atomic': 'true',
                'aria-live': 'assertive',
                'class': 'toast border-' + options.type
            });
        },

        /**
         * Show the toast.
         * 
         * @param {jQuery}
         *            [$toast] - The toast to show.
         * @param {Object}
         *            [options] - The custom options.
         * @return {Object} This instance.
         */
        showToast: function ($toast, options) {
            // set options
            $toast.toast({
                delay: options.timeout,
                autohide: options.autohide
            });

            // remove toast when hidden
            $toast.on('hidden.bs.toast', function () {
                $toast.remove();
                if ($.isFunction(options.onHide)) {
                    options.onHide(options);
                }
            });

            // show
            $toast.toast('show');

            return this;
        }
    };

}(window, jQuery));
