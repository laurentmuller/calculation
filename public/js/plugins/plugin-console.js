/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function (window) {
    'use strict';

    window.Logger = {

        /**
         * Output a message.
         */
        log: function (data) {
            if (this.isConsole('log')) {
                console.log(data);
            }
        },

        /**
         * Output a message as JSON.
         */
        logJson: function (data) {
            this.log(this.stringify(data));
        },

        /**
         * Output an information message.
         */
        info: function (data) {
            if (this.isConsole('info')) {
                console.info(data);
            }
        },

        /**
         * Output an information message as JSON.
         */
        infoJson: function (data) {
            this.info(this.stringify(data));
        },

        /**
         * Outputs a warning message.
         */
        warn: function (data) {
            if (this.isConsole('warn')) {
                console.warn(data);
            }
        },

        /**
         * Output a warning message as JSON.
         */
        warnJson: function (data) {
            this.warn(this.stringify(data));
        },

        /**
         * Outputs an error message.
         */
        error: function (data) {
            if (this.isConsole('error')) {
                console.error(data);
            }
        },

        /**
         * Output an error message as JSON.
         */
        errorJson: function (data) {
            this.error(this.stringify(data));
        },

        /**
         * Clear.
         */
        clear: function () {
            if (this.isConsole('clear')) {
                console.clear();
            }
        },

        /**
         * Converts a JavaScript object or value to a JSON string.
         */
        stringify: function (data) {
            return JSON.stringify(data, '', '    ');
        },
        
        /**
         * Check if console is present
         */
        isConsole(method) {
            return console && console[method];
        }
    };
})(window);
