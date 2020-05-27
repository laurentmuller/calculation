/**! compression tag for ftp-deployment */

/* globals tinymce */

/**
 * Ready function
 */
$(function () {
    'use strict';

    tinymce.PluginManager.add('clearContent', function (editor) {
        const onAction = function () {
            if (editor.getContent() !== '') {
                editor.setContent('');
                editor.focus();
                editor.fire('change');
            }
        };
        const onSetup = function (button) {
            const callback = function () {
                button.setDisabled(editor.getContent() === '');
            };
            editor.on('change', callback);
            return function () {
                editor.off('change', callback);
            };
        };
        editor.ui.registry.addButton('clearContent', {
            icon: 'remove',
            disabled: true,
            tooltip: 'Clear content',
            onAction: onAction,
            onSetup: onSetup
        });
        editor.ui.registry.addMenuItem('clearContent', {
            icon: 'remove',
            disabled: true,
            text: 'Clear content',
            onAction: onAction,
            onSetup: onSetup
        });

        return {
            getMetadata: function () {
                return {
                    name: "Clear content plugin",
                    url: "https://www.bibi.nu"
                };
            }
        };
    });

    // initialize editor
    $('#form_message').initTinyEditor({
        plugins: 'clearContent help',
        toolbar: 'clearContent | help',
        focus: true
    });

    // initialize validator
    $("form").initValidator({
        tinyeditor: true
    });
});