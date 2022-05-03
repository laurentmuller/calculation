/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function (window) {
    'use strict';

    window.Logger = {

        /**
         * Output data.
         *
         * @param {any} data - the data to output.
         */
        log: function (data) {
            if (this.isConsole('log')) {
                window.console.log(data);
            }
        },

        /**
         * Output a message as JSON.
         *
         * @param {any} data - the data to output.
         */
        logJson: function (data) {
            this.log(this.stringify(data));
        },

        /**
         * Output an information message.
         *
         * @param {any} data - the data to output.
         */
        info: function (data) {
            if (this.isConsole('info')) {
                window.console.info(data);
            }
        },

        /**
         * Output an information message as JSON.
         *
         * @param {any} data - the data to output.
         */
        infoJson: function (data) {
            this.info(this.stringify(data));
        },

        /**
         * Outputs a warning message.
         *
         * @param {any} data - the data to output.
         */
        warn: function (data) {
            if (this.isConsole('warn')) {
                window.console.warn(data);
            }
        },

        /**
         * Output a warning message as JSON.
         *
         * @param {any} data - the data to output.
         */
        warnJson: function (data) {
            this.warn(this.stringify(data));
        },

        /**
         * Outputs an error message.
         *
         * @param {any} data - the data to output.
         */
        error: function (data) {
            if (this.isConsole('error')) {
                window.console.error(data);
            }
        },

        /**
         * Output an error message as JSON.
         *
         * @param {any} data - the data to output.
         */
        errorJson: function (data) {
            this.error(this.stringify(data));
        },

        /**
         * Clear.
         */
        clear: function () {
            if (this.isConsole('clear')) {
                window.console.clear();
            }
        },

        /**
         * Converts a JavaScript object or value to a JSON string.
         *
         * @param {any} data - the value to convert to a JSON string.
         *
         * @return {string} a JSON string representing the given value.
         */
        stringify: function (data) {
            return JSON.stringify(data, '', '    ');
        },

        /**
         * Check if console is present
         */
        isConsole(method) {
            return window.console && window.console[method];
        }
    };

}(window));
