/**! compression tag for ftp-deployment */

/* globals tinymce */

/**
 * jQuery TinyMCE editor extensions.
 */
(function ($) {
    'use strict';

    /**
     * -------------- jQuery Extensions --------------
     */
    $.fn.extend({

        /**
         * Finds the TinyMCE editor container within the current element.
         * 
         * @return {jQuery} the container, if found; null otherwise.
         */
        findTinymceEditor: function () {
            if (tinymce) {
                const id = $(this).attr('id');
                const editor = tinymce.get(id);
                if (editor) {
                    return $(editor.getContainer());
                }
            }
            return null;
        },

        /**
         * Set focus to the editor.
         * 
         * @return {boolean} true if focused.
         */
        focusTinymceEditor: function () {
            const $this = $(this);
            const $editor = $this.findTinymceEditor();
            if ($editor) {
                const id = $this.attr('id');
                const editor = tinymce.get(id);
                editor.focus();
                return true;
            }
            return false;
        },

        /**
         * Gets the editor content as text.
         * 
         * @return {string} the content.
         */
        getTinymceEditorContent: function () {
            const id = $(this).attr('id');
            const editor = tinymce.get(id);
            if (editor) {
                return editor.getContent({
                    format: 'text'
                }).trim();
            }
            return '';
        },

        /**
         * Initialize a TinyMCE editor.
         * 
         * @param {Object}
         *            options - the initialisation options.
         * 
         * @return {jQuery} the element for chaining.
         */
        initTinymceEditor: function (options) {
            // concat values
            options = options || {};
            const optionPlugins = options.plugins || '';
            const optionToolbar = options.toolbar || '';
            const optionTabs = options.help_tabs || [];

            const plugins = 'autolink lists link image table paste ' + optionPlugins;
            const toolbar = 'styleselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent |' + optionToolbar;
            const help_tabs = ['shortcuts'].concat(optionTabs); // eslint-disable-line

            // remove
            delete options.plugins;
            delete options.toolbar;
            delete options.help_tabs;

            const defaults = {
                menubar: false,
                statusbar: true,
                contextmenu: false,
                elementpath: false,
                branding: false,
                language: 'fr_FR',
                min_height: 150, // eslint-disable-line
                max_height: 500, // eslint-disable-line
                height: 250,
                plugins: plugins.trim(),
                toolbar: toolbar.trim(),
                help_tabs: help_tabs, // eslint-disable-line
                setup: function (editor) {
                    editor.on('init', function () {
                        const $container = $(editor.getContainer());
                        $container.addClass('rounded');
                    });
                    editor.on('change', function () {
                        editor.save();
                        $(editor.getElement()).valid();
                    });
                    editor.on('focus', function () {
                        const $element = $(editor.getElement());
                        const validator = $element.parents('form').data('validator');
                        if (validator) {
                            validator.lastActive = $element;
                        }
                        const $container = $(editor.getContainer());
                        if ($container.hasClass('border-danger')) {
                            $container.addClass('field-invalid');
                        } else {
                            $container.addClass('field-valid');
                        }
                    });
                    editor.on('blur', function () {
                        const $container = $(editor.getContainer());
                        $container.removeClass('field-valid field-invalid');
                    });
                }
            };
            const $this = $(this);

            // merge
            const settings = $.extend(true, defaults, options, $this.data());

            // focus
            if (settings.focus) {
                delete settings.focus;
                settings.auto_focus = $this.attr('id'); // eslint-disable-line
            }

            // initialize
            return $this.tinymce(settings);
        }
    });
}(jQuery));
