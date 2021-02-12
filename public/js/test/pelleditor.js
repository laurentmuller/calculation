/**! compression tag for ftp-deployment */

/* globals pell */

/**
 * -------------- Functions extensions --------------
 */
$.fn.extend({
    /**
     * Update toolbar groups.
     */
    updateGroups() {
        'use strict';
        const $this = $(this);
        
        // find groups
        const groups = [];
        $this.find("i[class*='group']").each(function() {
            const group = $(this).attr('class').split(' ')
                .find(name => name.startsWith('group'));
            if(group && groups.indexOf(group) === -1) {
                groups.push(group);
            }
        });
        
        // create groups
        groups.forEach(function(group) {
            const $buttons = $this.find('.btn:has("i.' + group + '")');
            const $group = $('<div/>', {
                'class': 'mr-1 btn-group btn-group-sm btn-' + group,
                'role': 'group'
            });
            $buttons.appendTo($group);
            $group.appendTo($this);
        });
    },
    
    addSeparators() {
        'use strict';

        let oldGroup = false;
        const $this = $(this);
        $this.find("i[class*='group']").each(function() {
            const newGroup = $(this).attr('class').split(' ')
                .find(name => name.startsWith('group'));
            if (newGroup) {
                if (newGroup !== oldGroup) {
                    const $button = $(this).parents('.btn');
                    const $line = $('<div/>', {
                        'css':  {
                            'width': '1px',
                            'margin': '3px',
                            'border-left': '1px solid var(--secondary)'
                        } 
                    });
                    $line.insertBefore($button);
                }    
                oldGroup = newGroup;
            }
        });
    }
});

/**
 * Ready function
 */
(function($) {
    'use strict';
    const queryCommandState = function(command) {
        return document.queryCommandState(command);
    };
    
    const exec = function(command) {
        const value = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
        return document.execCommand(command, false, value);
    };
    
    const actions = [{
            name: 'bold',
            title: 'Gras',
            icon: '<i class="fa-fw fas fa-bold group-font"></i>'
        }, {
            name: 'italic',
            title: 'Italique',
            icon: '<i class="fa-fw fas fa-italic group-font"></i>'
        }, {
            name: 'underline',
            title: 'Souligné',
            icon: '<i class="fa-fw fas fa-underline group-font"></i>'
        }, {
            name: 'strikethrough',
            title: 'Barré',
            icon: '<i class="fa-fw fas fa-strikethrough group-font"></i>'
        }, {
            title: 'Exposant',
            icon: '<i class="fa-fw fas fa-superscript group-font"></i>',
            state: function() {
                return queryCommandState('superscript');
            },
            result: function() {
                return exec('superscript');
            }
        }, {
            title: 'Indice',
            icon: '<i class="fa-fw fas fa-subscript group-font"></i>',
            state: function() {
                return queryCommandState('subscript');
            },
            result: function() {
                return exec('subscript');
            }
        }, {
            title: 'Suprimer le format',
            icon: '<i class="fa-fw fas fa-remove-format group-font"></i>',
            result: function() {
                return exec('removeFormat');
            }
        }, {
            title: 'Aligné à gauche',
            icon: '<i class="fa-fw fas fa-align-left group-align"></i>',
            state: function() {
                return queryCommandState('justifyLeft');
            },
            result: function() {
                return exec('justifyLeft');
            }
        }, {
            title: 'Centrer',
            icon: '<i class="fa-fw fas fa-align-center group-align"></i>',
            state: function() {
                return queryCommandState('justifyCenter');
            },
            result: function() {
                return exec('justifyCenter');
            }
        }, {
            title: 'Aligné à droite',
            icon: '<i class="fa-fw fas fa-align-right group-align"></i>',
            state: function() {
                return queryCommandState('justifyRight');
            },
            result: function() {
                return exec('justifyRight');
            }
        }, {
            title: 'Justifier',
            icon: '<i class="fa-fw fas fa-align-justify group-align"></i>',
            state: function() {
                return queryCommandState('justifyFull');
            },
            result: function() {
                return exec('justifyFull');
            }
        }, {
            title: 'Augmenter le retrait',
            icon: '<i class="fa-fw fas fa-indent group-indent"></i>',
            result: function() {
                return exec('indent');
            }
        }, {
            title: 'Diminuer le retrait',
            icon: '<i class="fa-fw fas fa-outdent group-indent"></i>',
            result: function() {
                return exec('outdent');
            }
        }, {
            name: 'olist',
            title: 'Liste numérotée',
            icon: '<i class="fa-fw fas fa-list-ol group-list"></i>'
        }, {
            name: 'ulist',
            title: 'Liste à puces',
            icon: '<i class="fa-fw fas fa-list-ul group-list"></i>'
        }
        // , {
        // name: 'line',
        // title: 'Ligne horizontale',
        // icon: '<i class="fa-fw fas fa-grip-lines group-insert"></i>'
        // },
        // {
        // title: 'Annuler la dernière action',
        // icon: '<i class=" fa-fw fas fa-undo group-edit"></i>',
        // state: function () {
        // return queryCommandState('undo');
        // },
        // result: function () {
        // return exec('undo');
        // }
        // }, {
        // title: 'Rétablir la dernière action',
        // icon: '<i class=" fa-fw fas fa-redo group-edit"></i>',
        // state: function () {
        // return queryCommandState('redo');
        // },
        // result: function () {
        // return exec('redo');
        // }
        // }
    ];
    const classes = {
        content: 'pell-content editor-content border-top px-2',
        actionbar: 'btn-actionbar btn-group btn-group-sm d-print-none py-0',
        button: 'btn btn-outline-secondary rounded-0 border-0',
        selected: 'active'
    };
    
    const $editor = $('#editor');
    const $message = $('#form_message');
    
    pell.init({
        classes: classes,
        actions: actions,
        element: $editor[0],
        onChange: function(html) {
            $message.val(html);
        }
    });

    // actions separators
    $editor.find('.btn-actionbar').addSeparators();
    const $content = $editor.find('.editor-content');
    
    // add header style buttons
    $('#dropdown-title').prependTo('.btn-actionbar').removeClass('d-none');
    $('.dropdown-item.dropdown-title').on('click', function() {
        const selection = window.getSelection();
        // selection.modify('extend', 'backward', 'line');
        selection.modify('extend', 'forward', 'line');
        exec('removeFormat');
        exec('formatBlock', $(this).data('value'));
        $content.focus();
        return true;
    });
       
    $content.on('focus', function() {
        $editor.addClass('field-valid');
        // if ($('form_message').val().length) {
        // } else {
        // $content.addClass('field-invalid');
        // }
        // update states
    }).on('blur', function() {
        // border-danger field-invalid
        $editor.removeClass('field-valid');
    });
    
    const message = $message.val();
    if(message.length) {
        $content.html(message);
    }
    $content.focus();
    // $("form").initValidator();
}(jQuery));
