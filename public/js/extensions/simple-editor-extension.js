/**! compression tag for ftp-deployment */

/**
 * jQuery Simple-Editor extensions.
 */
(function ($) {
    'use strict';

    /**
     * -------------- jQuery Extensions --------------
     */
    $.fn.extend({

        /**
         * Finds the Simple-Editor container within the current element.
         * 
         * @return {jQuery} the editor or null if not found.
         */
        findSimpleEditor: function () {
            const $editor = $(this).parents('div.simple-editor');
            return $editor.length ? $editor : null;
        },

        /**
         * Set focus to the editor.
         * 
         * @return {boolean} true if focused.
         */
        focusSimpleEditor: function () {
            const $editor = $(this).findSimpleEditor();
            if ($editor) {
                const $content = $editor.find('.simple-editor-content');
                if ($content.length) {
                    $content.focus();
                    return true;
                }
            }
            return false;
        },

        /**
         * Gets the editor content as text.
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
         * Initialize a Simple-Editor.
         * 
         * @param {Object}
         *            options - the initialisation options.
         * @return {jQuery} the input for chaining.
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
            const borderRadius = $.isBorderRadius();
            return this.each(function () {
                const $this = $(this);
                const $editor = $this.parents('div.simple-editor');
                const $content = $editor.find('div.simple-editor-content');
                if (borderRadius) {
                    $editor.addClass('rounded');
                }

                // actions
                let oldGroup = false;
                $editor.find('.simple-editor-toolbar button').each(function (index) {
                    const $button = $(this);
                    const data = $button.data();
                    const exec = data.exec || false;
                    const state = exec && data.state || false;
                    const enabled = exec && data.enabled || false;

                    if (index === 0 && borderRadius) {
                        $button.addClass('rounded-left');
                    }
                    // exec
                    if (exec) {
                        $button.on('click', function () {
                            if (queryCommandEnabled(exec)) {
                                return $content.focus() && execCommand(exec, data.parameter || null);
                            }
                            return $content.focus();
                        });
                    } else {
                        $button.toggleDisabled(true);
                    }

                    // state
                    if (state) {
                        $content.on('click focus keyup mouseup input', function () {
                            $button.toggleClass('active', queryCommandState(state));
                        });
                    }

                    // enabled
                    if (enabled) {
                        $content.on('click focus keyup mouseup input', function () {
                            $button.toggleDisabled(!queryCommandEnabled(enabled));
                        });
                    }

                    // separator
                    const newGroup = data.group || false;
                    if (newGroup && oldGroup && newGroup !== oldGroup) {
                        $('<div/>', {
                            'class': 'border-left separator'
                        }).insertBefore($button);
                    }
                    oldGroup = newGroup;

                    // remove attributes
                    $button.removeAttr('data-exec data-parameter data-state data-enabled data-group');
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
                    } else if ($content.html() === '<br>') {
                        html = '';
                    }
                    $this.val(html).valid();
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
