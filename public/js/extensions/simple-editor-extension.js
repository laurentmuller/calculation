/**! compression tag for ftp-deployment */

/**
 * JQuery Simple-Editor extensions.
 */
(function ($) {
    'use strict';

    /**
     * -------------- JQuery Extensions --------------
     */
    $.fn.extend({

        /**
         * Finds the simple editor container within the current element.
         *
         * @return {JQuery} the editor or null if not found.
         */
        findSimpleEditor: function () {
            const $editor = $(this).parents('div.simple-editor');
            return $editor.length ? $editor : null;
        },

        /**
         * Set focus to the simple editor.
         *
         * @return {boolean} true if focused.
         */
        focusSimpleEditor: function () {
            const $editor = $(this).findSimpleEditor();
            if ($editor) {
                const $content = $editor.find('.simple-editor-content');
                if ($content.length) {
                    $content.trigger('focus');
                    return true;
                }
            }
            return false;
        },

        /**
         * Gets the simple editor content as text.
         *
         * @return {string} the content.
         */
        getSimpleEditorContent: function () {
            const $this = $(this);
            if ($this.findSimpleEditor()) {
                const value = $this.val().trim();
                try {
                    return $(value).text().trim();
                } catch (e) {
                    return value;
                }
            }
            return '';
        },

        /**
         * Initialize a simple editor.
         *
         * @param {Object} options - the initialisation options.
         * @return {JQuery} the input for chaining.
         */
        initSimpleEditor: function (options) {
            const queryCommandState = function (command) {
                return document.queryCommandState(command);
            };
            const queryCommandEnabled = function (command) {
                return document.queryCommandEnabled(command);
            };
            const execCommand = function (command) {
                const value = arguments.length > 1 && typeof arguments[1] !== 'undefined' ? arguments[1] : null;
                return document.execCommand(command, false, value);
            };

            options = options || {};
            const events = 'click focus keyup mouseup input';
            return this.each(function () {
                const $this = $(this);
                const $editor = $this.parents('div.simple-editor');
                const $content = $editor.find('div.simple-editor-content');

                // actions
                $editor.find('.simple-editor-toolbar button').each(function (index) {
                    const $button = $(this);
                    const data = $button.data();
                    const exec = data.exec || false;
                    const state = exec && data.state || false;
                    const enabled = exec && data.enabled || false;

                    if (index === 0) {
                        $button.addClass('rounded-left');
                    }

                    // exec
                    if (exec) {
                        $button.on('click', function () {
                            if (queryCommandEnabled(exec)) {
                                return $content.trigger('focus') && execCommand(exec, data.parameter || null);
                            }
                            return $content.trigger('focus');
                        });
                    } else if (!$button.hasClass('dropdown-toggle')) {
                        $button.toggleDisabled(true);
                    }

                    // state
                    if (state) {
                        $content.on(events, function () {
                            $button.toggleClass('active', queryCommandState(state));
                        });
                    }

                    // enabled
                    if (enabled) {
                        $content.on(events, function () {
                            $button.toggleDisabled(!queryCommandEnabled(enabled));
                        });
                    }

                    // remove attributes
                    // $button.removeAttr('data-exec data-parameter data-state data-enabled');
                });

                // handle content events
                $content.on('focus', function () {
                    const validator = $this.parents('form').data('validator');
                    if (validator) {
                        validator.lastActive = $content;
                    }
                    if ($editor.hasClass('border-danger')) {
                        $editor.addClass('field-invalid');
                    } else {
                        $editor.addClass('field-valid');
                    }
                }).on('blur', function () {
                    $editor.removeClass('field-valid field-invalid');
                }).on('input', function () {
                    let html = $content.html();
                    const child = $content[0].firstChild;
                    if (child && child.nodeType === 3) {
                        html = '<div>' + html + '</div>';
                    } else if (html === '<br>') {
                        html = '';
                    }
                    $this.val(html).valid();
                }).on('paste', function (e) {
                    e = e.originalEvent;
                    if (e && e.clipboardData && e.clipboardData.getData) {
                        let html = e.clipboardData.getData('text/html');
                        if (html && html.length) {
                            const regex = /\r|\n|style="(.*?)"|class="(.*?)"|(?=<!--)([\s\S]*?)-->|<div.*>&nbsp;<\/div>/gm;
                            html = html.replace(regex, '').trim();
                            $content.html(html).trigger('input');
                            e.preventDefault();
                            return false;
                        }
                    }
                });

                // copy value
                const value = $this.val();
                if (value.length) {
                    $content.html(value);
                }

                // focus
                if (options.focus) {
                    $this.focusSimpleEditor();
                }
            });
        },
    });
}(jQuery));
