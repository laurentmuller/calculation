/**! compression tag for ftp-deployment */

/* globals tinymce */

/**
 * jQuery Validation Plugin extensions.
 */
(function($) {
    'use strict';

    /**
     * -------------- JQuery Extensions --------------
     */
    $.fn.extend({

        /**
         * Gets password strength score or -1 if not found.
         */
        findPasswordScore: function() {
            const $that = $(this);
            const data = $that.data("passwordstrength");
            if (data && data.verdict && !$.isUndefined(data.verdict.score)) {
                return data.verdict.score;
            }
            return -1;
        },

        /**
         * Finds the Summernote editor container within the current element.
         */
        findEditor: function() {
            const data = $(this).data("summernote");
            if (data) {
                const $editor = $(data.layoutInfo.editor);
                if ($editor.length) {
                    return $editor;
                }
            }
            return null;
        },

        /**
         * Finds the TinyMCE editor container within the current element.
         */
        findTinyEditor: function() {
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
         * Finds the reCaptcha frame within the current element.
         */
        findReCaptcha: function() {
            const $element = $(this);

            // find within the current element or the parent element
            return $element.findExists('iframe:first') || $element.parent().findExists('iframe:first');
        },

        /**
         * Finds the parent div within a file input.
         */
        findFileInput() {
            const $element = $(this);
            const $parent = $element.parents('.form-group');
            return $parent.findExists('.fileinput.input-group, .fileinput-preview.img-thumbnail');
        },

        /**
         * Finds the color-picker drop-down
         */
        findColorPicker() {
            const $element = $(this);
            const $parent = $element.parents('.form-group');
            return $parent.findExists('.dropdown.color-picker');
        },

        /**
         * Initialize default validator options.
         * 
         * @returns the validator.
         */
        initValidator: function(options) {
            // get options
            const editor = options && options.editor;
            const inline = options && options.inline;
            const recaptcha = options && options.recaptcha;
            const fileInput = options && options.fileInput;
            const colorpicker = options && options.colorpicker;
            const tinyeditor = options && options.tinyeditor;
            
            if (editor) {
                // override elementValue function
                $.validator.prototype.elementValue = (function(parent) {
                    return function(element) {
                        // check editor text if applicable
                        if ($(element).data('summernote')) {
                            const code = $(element).summernote('code');
                            return $(code).text().trim();
                        }

                        // call the original function
                        return parent.apply(this, arguments);
                    };

                })($.validator.prototype.elementValue);
            }
            
            if (tinyeditor) {
                // override elementValue function
                $.validator.prototype.elementValue = (function(parent) {
                    return function(element) {
                        // get editor content
                        const id = $(element).attr('id');
                        const editor = tinymce.get(id);
                        if (editor) {
                            return  editor.getContent({format: 'text'}).trim();
                        }

                        // call the original function
                        return parent.apply(this, arguments);
                    };

                })($.validator.prototype.elementValue);
            }
            

            // override focusInvalid function
            $.validator.prototype.focusInvalid = (function(parent) {
                return function() {
                    if (this.settings.focusInvalid) {
                        // get invalid elements
                        const $elements = $(this.findLastActive() || this.errorList.length && this.errorList[0].element || []);

                        // editor
                        if (editor) {
                            if ($elements.data('summernote')) {
                                $elements.summernote('focus');
                                $elements.trigger("focusin");
                                return;
                            }
                        }

                        // tinyeditor
                        if (tinyeditor) {
                            const $editor = $elements.findTinyEditor();
                            if ($editor) {
                                const id = $elements.attr('id');
                                const editor = tinymce.get(id);
                                editor.focus();
                                return;
                            }
                        }
                        
                        // reCaptcha
                        if (recaptcha) {
                            const $recaptcha = $elements.findReCaptcha();
                            if ($recaptcha) {
                                $recaptcha.focus();
                                $elements.trigger("focusin");
                                return;
                            }
                        }

                        // color-picker
                        if (colorpicker) {
                            const $colorpicker = $elements.findColorPicker();
                            if ($colorpicker) {
                                $colorpicker.focus();
                                return;
                            }
                        }

                        // select
                        try {
                            $elements.selectFocus();
                        } catch (e) {
                            // ignore
                        }

                        // call the original function
                        parent.apply(this, arguments);
                    }
                };
            })($.validator.prototype.focusInvalid);

            // default options
            let defaults = {
                focus: true,
                errorElement: 'small',
                errorClass: 'is-invalid',
                ignore: ':hidden:not(.must-validate),.note-editable.card-block',

                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    if (inline) {
                        error.appendTo($(element).closest('div'));
                    } else {
                        error.appendTo($(element).closest('.form-group'));
                    }
                },

                highlight: function(element, errorClass) {
                    const $element = $(element);
                    $element.addClass(errorClass);

                    // find custom element
                    let $toUpdate = null;
                    if (editor && !$toUpdate) {
                        $toUpdate = $element.findEditor();
                    }
                    if (tinyeditor && !$toUpdate) {
                        $toUpdate = $element.findTinyEditor();
                    }                    
                    if (recaptcha && !$toUpdate) {
                        $toUpdate = $element.findReCaptcha();
                    }
                    if (fileInput && !$toUpdate) {
                        $toUpdate = $element.findFileInput();
                    }
                    if (colorpicker && !$toUpdate) {
                        $toUpdate = $element.findColorPicker();
                    }
                    if ($toUpdate) {
                        $toUpdate.addClass('border-danger');
                        if ($toUpdate.hasClass('field-valid')) {
                            $toUpdate.removeClass('field-valid').addClass('field-invalid');
                        }
                    }
                },

                unhighlight: function(element, errorClass) {
                    // default
                    const $element = $(element);
                    $element.removeClass(errorClass);

                    // find custom element
                    let $toUpdate = null;
                    if (editor && !$toUpdate) {
                        $toUpdate = $element.findEditor();
                    }
                    if (tinyeditor && !$toUpdate) {
                        $toUpdate = $element.findTinyEditor();
                    }                    
                    if (recaptcha && !$toUpdate) {
                        $toUpdate = $element.findReCaptcha();
                    }
                    if (fileInput && !$toUpdate) {
                        $toUpdate = $element.findFileInput();
                    }
                    if (colorpicker && !$toUpdate) {
                        $toUpdate = $element.findColorPicker();
                    }
                    if ($toUpdate) {
                        $toUpdate.removeClass('border-danger');
                        if ($toUpdate.hasClass('field-invalid')) {
                            $toUpdate.removeClass('field-invalid').addClass('field-valid');
                        }
                    }
                }
            };

            // add spinner on submit
            if (!options || !options.submitHandler) {
                defaults.submitHandler = function(form) {
                    const $submit = $(form).find(':submit');
                    if ($submit.length) {
                        const spinner = '<span class="spinner-border spinner-border-sm"></span>';
                        $submit.addClass('disabled').html(spinner);
                    }
                    const $cancel = $(form).find('.btn-cancel');
                    if ($cancel.length) {
                        $cancel.addClass('disabled');
                    }
                    form.submit();
                };
            }

            // merge
            const settings = $.extend(true, defaults, options);

            // validate
            const $that = $(this);
            const validator = $that.validate(settings);

            // focus
            if (settings.focus) {
                $that.initFocus();
            }

            return validator;
        },

        /**
         * Sets focus to the first invalid field, if any; to the first editable
         * field otherwise.
         */
        initFocus: function() {
            let found = false;
            const $that = $(this);
            const toFind = 'input,select,textarea';
            const selector = ':visible:enabled:not([readonly]):first';
            // :hidden:not(.must-validate)

            // find first invalid field
            $that.find('span.form-error-message').each(function() {
                const $group = $(this).closest('.form-group');
                if ($group.length) {
                    const $input = $group.find(toFind).filter(selector);
                    if ($input.length) {
                        $input.selectFocus();
                        found = true;
                    }
                }
                return !found;
            });

            // set focus to first editable field
            if (!found) {
                const $input = $that.find(toFind).filter(selector);
                if ($input.length) {
                    $input.selectFocus();
                }
            }
            return this;
        },

        /**
         * Initialize a Summernote editor.
         */
        initTinyEditor: function(options) {
            // concat values
            // var plugins = ['autolink', 'lists', 'image', 'table', 'paste',
            // 'help']
            // var c = plugins.concat(b.filter((item) => plugins.indexOf(item)
            // === -1))
            // plugins.join(' ');
            // [].concat(array1, array2);
            const plugins = 'autolink lists link image table paste help ' + (options.plugins || '');
            const toolbar = 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | help ' + (options.toolbar || '');
            const help_tabs = ['shortcuts'].concat(options.help_tabs || []);
            
            // remove
            if (options) {
                delete options.plugins;
                delete options.toolbar;
                delete options.help_tabs;
            }
            
            const defaults = {
                menubar: false,
                statusbar: true,
                contextmenu: false,
                elementpath: false,
                branding: false,                
                language: 'fr_FR',
                min_height: 150,
                max_height: 500,
                height: 250,
                plugins: plugins.trim(),
                toolbar: toolbar.trim(),
                help_tabs: help_tabs,                
                setup: function(editor) {
                    editor.on('init', function() {
                        const $container = $(editor.getContainer());
                        $container.addClass('rounded');
                    });
                    editor.on('change', function() {
                        editor.save();
                        $(editor.getElement()).valid();
                    });
                    editor.on('focus', function() {
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
                    editor.on('blur', function() {
                        const $container = $(editor.getContainer());
                        $container.removeClass('field-valid field-invalid');
                    });
                }
            };

            // merge
            const settings = $.extend(true, defaults, options, $(this).data());

            // initialize
            return $(this).tinymce(settings);
        },
        
        /**
         * Initialize a Summernote editor.
         */
        initEditor: function(options) {
            // merge toolbar
            const toolbar = [
                ['startButtons', [].concat(options.startButtons)],
                ['styleButtons', ['style'].concat(options.styleButtons)], //
                ['fontButtons', ['bold', 'underline'].concat(options.fontButtons)], // ,
                                                                                    // 'clear'
                ['fontnameButtons', ['fontname'].concat(options.fontnameButtons)], //
                ['paraButtons', ['ul', 'ol', 'paragraph'].concat(options.paraButtons)], //
                ['colorButtons', [].concat(options.colorButtons)], // 'color'
                ['tableButtons', ['table', 'hr'].concat(options.tableButtons)], //
                ['insertButtons', ['link'].concat(options.insertButtons)], //
                ['editButtons', [].concat(options.editButtons)], // 'undo',
                                                                    // 'redo'
                ['endButtons', [].concat(options.endButtons)] //
            ];

            const defaults = {
                lang: 'fr-FR',
                minHeight: 200,
                maxHeight: 500,
                shortcuts: false,
                // tooltip: false,
                dialogsFade: true,
                toolbar: toolbar,

                callbacks: {
                    onFocus: function() {
                        const $that = $(this).trigger('focus');
                        const validator = $that.parents('form').data('validator');
                        if (validator) {
                            validator.lastActive = $that;
                        }
                        const $editor = $that.findEditor();
                        if ($editor) {
                            if ($editor.hasClass('border-danger')) {
                                $editor.addClass('field-invalid');
                            } else {
                                $editor.addClass('field-valid');
                            }
                        }
                    },
                    onBlur: function() {
                        const $editor = $(this).findEditor();
                        if ($editor) {
                            $editor.removeClass('field-valid field-invalid');
                        }
                    },
                    onChange: function(contents) {
                        const $that = $(this);
                        const oldText = $that.data('text') || '';
                        const newText = $(contents).text().trim();
                        if (oldText !== newText) {
                            $that.data('text', newText);
                            $that.valid();
                        }
                    },
                    onInit: function() {
                        // update UI
                        $(".card.note-editor.note-editor").addClass("border");
                        // $('.link-dialog
                        // span.custom-control-description').removeClass('custom-control-description').addClass('custom-control-label');
                    }
                }
            };

            // remove tab keys
            // if ($.summernote.options.keyMap.pc.TAB) {
            // delete $.summernote.options.keyMap.pc.TAB;
            // delete $.summernote.options.keyMap.mac.TAB;
            // delete $.summernote.options.keyMap.pc['SHIFT+TAB'];
            // delete $.summernote.options.keyMap.mac['SHIFT+TAB'];
            // }

            // missing translation
            $.summernote.lang['fr-FR'].link.useProtocol = 'Utiliser le protocole par défaut';
            
            // merge
            const settings = $.extend(true, defaults, options, $(this).data());
            
            // initialize
            return $(this).summernote(settings);
        },

        /**
         * Intitialize an input type file.
         * 
         * @param {function}
         *            callback - the optional callback function to use after
         *            change.
         */
        initFileType: function(callback) {
            return this.each(function() {
                const $that = $(this);
                const isThumbnail = $that.parents('.form-group').findExists('.img-thumbnail');
                $that.on('change', function() {
                    $that.valid();
                    if ($.isFunction(callback)) {
                        callback($that);
                    }
                    if (!isThumbnail) {
                        const isFiles = $that.getInputFiles().length !== 0;
                        $that.parent().updateClass('rounded-right', !isFiles).css('border-right', isFiles  ? '' : '0');
                    }
                });

                // find group
                const $group = $that.findFileInput();
                if ($group) {
                    // update class
                    $that.on('focus', function() {
                        if ($that.hasClass('is-invalid')) {
                            $group.addClass('field-invalid');
                        } else {
                            $group.addClass('field-valid');
                        }
                    }).on('blur', function() {
                        $group.removeClass('field-valid field-invalid');
                    });

                    // focus when select file
                    $group.find('.fileinput-filename,.fileinput-exists').on('click', function() {
                        $that.focus();
                    });
                }
            });
        },

        /**
         * Intitialize an input type color within the color-picker plugin.
         * 
         * @param {Object}
         *            options - the options
         */
        initColorPicker: function(options) {            
            return this.each(function() {
                const $that = $(this);
                $that.colorpicker(options);
                const $colorpicker = $that.findColorPicker();
                if ($colorpicker) {
                    // update class
                    $colorpicker.on('focus', function() {
                        if ($that.hasClass('is-invalid')) {
                            $colorpicker.addClass('field-invalid');
                        } else {
                            $colorpicker.addClass('field-valid');
                        }
                    }).on('blur', function() {
                        $colorpicker.removeClass('field-valid field-invalid');
                    });
                }
            });
        },

        /**
         * Reset the form content and the validator (if any).
         */
        resetValidator: function() {
            const $that = $(this);
            const validator = $that.data('validator');
            if (validator) {
                validator.resetForm();
            }
            $that[0].reset();
            $that.find('.is-invalid').removeClass('is-invalid');
            return $that;
        }
    });

    /**
     * -------------- Additional and override methods --------------
     */

    $.extend($.validator, {

        /*
         * format message within the label (if any)
         */
        formatLabel: function(element, message, fallback, params) {
            // parameters
            if ($.isUndefined(params)) {
                params = [];
            }
            if (arguments.length > 3 && params.constructor !== Array) {
                params = $.makeArray(arguments).slice(3);
            }
            if (params.constructor !== Array) {
                params = [params];
            }

            // get text
            const text = $(element).getLabelText();
            if (text) {
                params.unshift(text);
                return $.validator.format(message, params);
            }
            return $.validator.format(fallback, params);
        },
    });

    /*
     * check password score
     */
    $.validator.addMethod("password", function(value, element, param) {
        if (this.optional(element)) {
            return true;
        }
        const score = $(element).findPasswordScore();
        return score === -1 || score >= param;
    }, $.validator.format("The password must have a minimum score of {0}."));

    /*
     * check if the value contains the user name
     */
    $.validator.addMethod("notUsername", function(value, element, param) {
        if (this.optional(element)) {
            return true;
        }
        const $target = $(param);
        if (this.settings.onfocusout && $target.not(".validate-notUsername-blur").length) {
            $target.addClass("validate-notUsername-blur").on("blur.validate-notUsername", function() {
                $(element).valid();
            });
        }
        const target = $target.val().trim();
        return target.length === 0 || value.indexOfIgnoreCase(target) === -1;    
    }, $.validator.format("The field can not contain the user name."));

    /*
     * check if value is not an email
     */
    $.validator.addMethod("notEmail", function(value, element) {
        if (this.optional(element)) {
            return true;
        }
        return !$.validator.methods.email.call(this, value, element);
    }, $.validator.format("The field can not be an email."));

    /*
     * check if contains a lower case character
     */
    $.validator.addMethod("lowercase", function(value, element) {
        if (this.optional(element)) {
            return true;
        }
        return /[a-z\u00E0-\u00FC]/g.test(value);
    }, $.validator.format("The field must contain a lower case character."));

    /*
     * check if contains a upper case character
     */
    $.validator.addMethod("uppercase", function(value, element) {
        if (this.optional(element)) {
            return true;
        }
        return /[A-Z\u00C0-\u00DC]/g.test(value);
    }, $.validator.format("The field must contain an upper case character."));

    /*
     * check if contains a upper and lower case characters
     */
    $.validator.addMethod("mixedcase", function(value, element, param) {
        if (this.optional(element)) {
            return true;
        }
        return $.validator.methods.lowercase.call(this, value, element, param) && $.validator.methods.uppercase.call(this, value, element, param);
    }, $.validator.format("The field must contain both upper and lower case characters."));

    /*
     * check if contains a upper or lower case characters
     */
    $.validator.addMethod("letter", function(value, element, param) {
        if (this.optional(element)) {
            return true;
        }
        return $.validator.methods.lowercase.call(this, value, element, param) || $.validator.methods.uppercase.call(this, value, element, param);
    }, $.validator.format("The field must contain a letter."));

    /*
     * check if contains a digit character
     */
    $.validator.addMethod("digit", function(value, element) {
        if (this.optional(element)) {
            return true;
        }
        return /\d/g.test(value);
    }, $.validator.format("The field must contain a digit character."));

    /*
     * check if contains a special character
     */
    $.validator.addMethod("specialchar", function(value, element) {
        if (this.optional(element)) {
            return true;
        }
        const regex = /[-!$%^&*()_+|~=`{}[:;<>?,.@#\]]/g;
        return regex.test(value);
    }, $.validator.format("The field must contain a special character."));


    /*
     * override URL by adding the protocol prefix (if any)
     */
    $.validator.methods.url = (function(parent) {
        return function(value, element) {
            const regex = new RegExp(/^[\w+.-]+:\/\//);
            if (!regex.test(value)) {
                const protocol = $(element).data('protocol');
                if (protocol) {
                    value = protocol + '://' + value;
                }
            }
            return parent.call(this, value, element);
        };
    })($.validator.methods.url);

    /*
     * replace email with a simple <string>@<string>.<string> value
     */
    $.validator.methods.email = function(value, element) {
        return this.optional(element) || /\S+@\S+\.\S{2,}/.test(value);
    };

})(jQuery);