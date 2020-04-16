/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    // messsage
    const $element = $('#form_message');

    // custom buttons
    const AppButton = function (context) {
        const ui = $.summernote.ui;
        const button = ui.button({
            contents: '<i class="fas fa-link fa-fw" aria-hidden="true"></i>',
            tooltip: "Insérer le nom et la version de l'application",
            click: function () {
                context.invoke('editor.insertText', $('#app_name_version').text());
            }
        });
        return button.render();
    };
    const ClearButton = function (context) {
        const ui = $.summernote.ui;
        const button = ui.button({
            contents: '<i class="far fa-trash-alt fa-fw" aria-hidden="true"></i>',
            tooltip: "Effacer tout le contenu",
            click: function () {
                const $note = context.layoutInfo.note;
                $note.summernote('code', '');
                context.invoke('editor.focus');
            }
        });
        return button.render();
    };

    // initialize editor
    $element.initEditor({
        focus: false,
        startButtons: ['appButton'],
        endButtons: ['clearButton'],
        buttons: {
            appButton: AppButton,
            clearButton: ClearButton
        }
    });

    // initialize color picker
    $('#form_color').initColorPicker();

    // initialize validator
    $("form").initValidator({
        editor: true,
        colorpicker: true
    });
});