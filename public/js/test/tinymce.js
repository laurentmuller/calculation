/**! compression tag for ftp-deployment */

/* globals tinymce */

/**
 * Ready function
 */
$(function () {
    'use strict';

    tinymce.PluginManager.add('clearContent', function (editor) {
        const clearContent = function () {
            if (editor.getContent() !== '') {
                editor.setContent('');
                editor.focus();
                editor.fire('change');
            }
        };

        editor.ui.registry.addButton('clearContent', {
            icon: 'remove',
            disabled: true,
            tooltip: 'Supprimer le contenu',
            onAction: function () {
                clearContent();
            },
            onSetup: function (button) {
                const callback = function () {
                    button.setDisabled(editor.getContent() === '');
                };
                editor.on('change', callback);
                return function () {
                    editor.off('change', callback);
                };
            }
        });

        editor.ui.registry.addMenuItem('clearContent', {
            icon: 'remove',
            text: 'Supprimer le contenu',
            onAction: function () {
                clearContent();
            },
            onSetup: function (button) {
                const callback = function () {
                    button.setDisabled(editor.getContent() === '');
                };
                editor.on('change', callback);
                return function () {
                    editor.off('change', callback);
                };
            }
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
        plugins: 'clearContent',
        toolbar: 'clearContent'
    });

    // initialize validator
    $("form").initValidator({
        tinyeditor: true
    });
});