/**! compression tag for ftp-deployment */

/**
 * jQuery Validation Plugin extensions.
 */
(function ($) {
    'use strict';

    /**
     * -------------- jQuery Extensions --------------
     */
    $.fn.extend({

        /**
         * Initialize captcha.
         *
         * @return {jQuery} the caller for chaining.
         */
        initCaptcha() {
            return this.each(function () {
                const $that = $(this);
                const url = $that.data('refresh');
                const $parent = $that.parents('.form-group');
                const $image = $parent.find('.captcha-image');
                const $refresh = $parent.find('.captcha-refresh');
                $refresh.on('click', function () {
                    $.get(url, function (response) {
                        if (response.result) {
                            $image.attr('src', response.data);
                            $that.val('').trigger('focus');
                        }
                    });
                });
            });
        },

        /**
         * Gets password strength score or -1 if not found.
         *
         * @return {number}
         */
        findPasswordScore: function () {
            const $that = $(this);
            const data = $that.data('passwordstrength');
            if (data && data.verdict && !$.isUndefined(data.verdict.score)) {
                return data.verdict.score;
            }
            return -1;
        },

        /**
         * Finds the reCaptcha frame within the current element.
         *
         * @return {jQuery} the frame.
         */
        findReCaptcha: function () {
            const $element = $(this);
            return $element.findExists('iframe:first') || $element.parent().findExists('iframe:first');
        },

        /**
         * Finds the color-picker drop-down
         *
         * @return {jQuery}.
         */
        findColorPicker() {
            const $element = $(this);
            const $parent = $element.parents('.form-group');
            return $parent.findExists('.dropdown.color-picker');
        },

        /**
         * Remove validation class and error
         *
         * @return {jQuery} the caller for chaining.
         */
        removeValidation: function () {
            return this.each(function () {
                const $this = $(this);
                $this.removeClass('is-invalid');
                $this.parents('.form-group').find('.is-invalid.invalid-feedback').remove();
            });
        },

        /**
         * Initialize default validator options.
         *
         * @param {Object} [options] - the options
         * @param {boolean} options.inline
         * @param {boolean} options.recaptcha
         * @param {boolean} options.fileInput
         * @param {boolean} options.colorPicker
         * @param {boolean} options.simpleEditor
         * @returns the validator.
         */
        initValidator: function (options) {
            // get options
            options = options || {};
            const inline = options.inline;
            const recaptcha = options.recaptcha;
            const fileInput = options.fileInput;
            const colorPicker = options.colorPicker;
            const simpleEditor = options.simpleEditor;

            // override elementValue function
            if (simpleEditor) {
                $.validator.prototype.elementValue = (function (parent) {
                    return function (element) {
                        if ($(element).findSimpleEditor()) {
                            return $(element).getSimpleEditorContent();
                        }
                        return parent.apply(this, arguments);
                    };
                }($.validator.prototype.elementValue));
            }

            // override focusInvalid function
            $.validator.prototype.focusInvalid = (function (parent) {
                return function () {
                    if (this.settings.focusInvalid) {
                        // get invalid elements
                        const $elements = $(this.findLastActive() || this.errorList.length && this.errorList[0].element || []);

                        // display if parent's accordion
                        const $collapse = $elements.parents('.collapse:not(.show)');
                        const $accordion = $elements.parents('.accordion');
                        if ($collapse.length && $accordion.length) {
                            $collapse.collapse('show');
                        }

                        // simple editor
                        if (simpleEditor) {
                            if ($elements.focusSimpleEditor()) {
                                return;
                            }
                        }

                        // reCaptcha
                        if (recaptcha) {
                            const $recaptcha = $elements.findReCaptcha();
                            if ($recaptcha) {
                                $recaptcha.focus();
                                $elements.trigger('focusin');
                                return;
                            }
                        }

                        // color-picker
                        if (colorPicker) {
                            const $colorPicker = $elements.findColorPicker();
                            if ($colorPicker) {
                                $colorPicker.focus();
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
            }($.validator.prototype.focusInvalid));

            /**
             * Finds the container of the given element.
             *
             * @param {JQuery} $element - the element to update.
             */
            $.validator.prototype.findElement = function ($element) {
                let $toUpdate = false;
                if (simpleEditor && !$toUpdate) {
                    $toUpdate = $element.findSimpleEditor();
                }
                if (recaptcha && !$toUpdate) {
                    $toUpdate = $element.findReCaptcha();
                }
                if (fileInput && !$toUpdate) {
                    $toUpdate = $element.findFileInput();
                }
                if (colorPicker && !$toUpdate) {
                    $toUpdate = $element.findColorPicker();
                }
                return $toUpdate;
            };

            // default options
            let defaults = {
                focus: true,
                errorElement: 'small',
                errorClass: 'is-invalid',
                ignore: '[type="hidden"]:not(".must-validate")',

                errorPlacement: function (error, element) {
                    let $parent = $(element).closest('.form-group').find('.passwordstrength');
                    if (0 === $parent.length) {
                        if (inline) {
                            $parent = $(element).closest('div');
                        } else {
                            $parent = $(element).closest('.form-group');
                        }
                    }
                    error.addClass('invalid-feedback').appendTo($parent);
                },

                highlight: function (element, errorClass) {
                    const $element = $(element);
                    const $toUpdate = this.findElement($element);
                    $element.addClass(errorClass);
                    if ($toUpdate) {
                        $toUpdate.addClass('border-danger');
                        if ($toUpdate.hasClass('field-valid')) {
                            $toUpdate.removeClass('field-valid').addClass('field-invalid');
                        }
                    }

                    // fix bug:
                    // https://getbootstrap.com/docs/4.5/components/forms/#input-group-validation-workaround
                    // const rightRadius =
                    // $left.css('border-bottom-left-radius');
                    // const $text = $element.parents('.input-group')
                    // .find('.input-group-append:last .input-group-text:last');
                    // console.log($text.css('border-bottom-right-radius'));
                    // .addClass('border-right rounded-right');
                },

                unhighlight: function (element, errorClass) {
                    const $element = $(element);
                    const $toUpdate = this.findElement($element);
                    $element.removeClass(errorClass);
                    if ($toUpdate) {
                        $toUpdate.removeClass('border-danger');
                        if ($toUpdate.hasClass('field-invalid')) {
                            $toUpdate.removeClass('field-invalid').addClass('field-valid');
                        }
                    }
                },

            };

            if (!options.submitHandler) {
                defaults.submitHandler = function (form) {
                    $(form).showSpinner();
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
        initFocus: function () {
            let found = false;
            const $that = $(this);
            const toFind = 'input,select,textarea';
            const selector = ':visible:enabled:not([readonly]):first';
            // :hidden:not(.must-validate)

            // find first invalid field
            $that.find('.invalid-feedback').each(function () {
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
                let $input = $that.find(toFind).filter(selector);
                if ($input.is(':radio') && !$input.isChecked()) {
                    $input = $input.parents('.form-group').find(':radio:checked');
                }
                if ($input.length) {
                    $input.selectFocus();
                }
            }
            return this;
        },

        /**
         * Initialize an input type color within the color-picker plugin.
         *
         * @param {Object} [options] - the options
         */
        initColorPicker: function (options) {
            return this.each(function () {
                const $that = $(this);
                $that.colorpicker(options);
                const $colorPicker = $that.findColorPicker();
                if ($colorPicker) {
                    // update class
                    $colorPicker.on('focus', function () {
                        if ($that.hasClass('is-invalid')) {
                            $colorPicker.addClass('field-invalid');
                        } else {
                            $colorPicker.addClass('field-valid');
                        }
                    }).on('blur', function () {
                        $colorPicker.removeClass('field-valid field-invalid');
                    });
                }
            });
        },

        /**
         * Reset the form content and the validator (if any).
         *
         * @return {jQuery} the caller for chaining.
         */
        resetValidator: function () {
            const $that = $(this);
            const validator = $that.data('validator');
            if (validator) {
                validator.resetForm();
            }
            $that[0].reset();
            $that.find('.is-invalid').removeClass('is-invalid');
            return $that;
        },

        /**
         * Show a spinner when form is submitted.
         *
         * @return {jQuery} the caller for chaining.
         */
        showSpinner: function() {
            const $this = $(this);
            const spinner = '<span class="spinner-border spinner-border-sm"></span>';
            $this.find(':submit').toggleDisabled(true).html(spinner);
            $this.find('.btn-cancel').toggleDisabled(true);
            return  $this;
        }
    });

    /**
     * -------------- Additional and override methods --------------
     */

    /*
     * check password score
     */
    $.validator.addMethod('password', function (_value, element, param) {
        if (this.optional(element)) {
            return true;
        }
        const score = $(element).findPasswordScore();
        return score === -1 || score >= param;
    }, $.validator.format('The password must have a minimum score of {0}.'));

    /*
     * check if the value contains the username
     */
    $.validator.addMethod('notUsername', function (value, element, param) {
        if (this.optional(element)) {
            return true;
        }
        const $target = $(param);
        if (this.settings.onfocusout && $target.not('.validate-notUsername-blur').length) {
            $target.addClass('validate-notUsername-blur').on('blur.validate-notUsername', function () {
                $(element).valid();
            });
        }
        const target = $target.val().trim();
        return target.length === 0 || value.indexOfIgnoreCase(target) === -1;
    }, 'The field can not contain the user name.');

    /*
     * check if value is not an email
     */
    $.validator.addMethod('notEmail', function (value, element) {
        if (this.optional(element)) {
            return true;
        }
        return !$.validator.methods.email.call(this, value, element);
    }, 'The field can not be an email.');

    /*
     * check if contains a lower case character
     */
    $.validator.addMethod('lowercase', function (value, element) {
        if (this.optional(element)) {
            return true;
        }
        return /[a-z\u00E0-\u00FC]/g.test(value);
    }, 'The field must contain a lower case character.');

    /*
     * check if contains an upper case character
     */
    $.validator.addMethod('uppercase', function (value, element) {
        if (this.optional(element)) {
            return true;
        }
        return /[A-Z\u00C0-\u00DC]/g.test(value);
    }, 'The field must contain an upper case character.');

    /*
     * check if contains an upper and lower case characters
     */
    $.validator.addMethod('mixedcase', function (value, element, param) {
        if (this.optional(element)) {
            return true;
        }
        return $.validator.methods.lowercase.call(this, value, element, param) && $.validator.methods.uppercase.call(this, value, element, param);
    }, 'The field must contain both upper and lower case characters.');

    /*
     * check if contains alphabetic characters
     */
    $.validator.addMethod('letter', function (value, element, param) {
        if (this.optional(element)) {
            return true;
        }
        return $.validator.methods.lowercase.call(this, value, element, param) || $.validator.methods.uppercase.call(this, value, element, param);
    }, 'The field must contain a letter.');

    /*
     * check if contains a digit character
     */
    $.validator.addMethod('digit', function (value, element) {
        if (this.optional(element)) {
            return true;
        }
        return /\d/g.test(value);
    }, 'The field must contain a digit character.');

    /*
     * check if contains a special character
     */
    $.validator.addMethod('specialchar', function (value, element) {
        if (this.optional(element)) {
            return true;
        }
        const regex = /[-!$%^&*()_+|~=`{}[:;<>?,.@#\]]/g;
        return regex.test(value);
    }, 'The field must contain a special character.');

    /*
     * check if contains a greater value
     */
    $.validator.addMethod('greaterThanValue', function (value, element, param) {
        return this.optional(element) || value > param;
    }, 'The field must contain a greater value.');

    /*
     * check if contains a greater than or equal value
     */
    $.validator.addMethod( 'greaterThanEqualValue', function (value, element, param) {
        return this.optional(element) || value >= param;
    }, 'The field must contain a greater than or equal value.' );

    /*
     * check if contains a lesser value
     */
    $.validator.addMethod('lessThanValue', function (value, element, param) {
        return this.optional(element) || value > param;
    }, 'The field must contain a lesser value.');

    /*
     * check if contains a lesser or equal value
     */
    $.validator.addMethod('lessThanEqualValue', function (value, element, param) {
        return this.optional(element) || value <= param;
    }, 'The field must contain a lesser than or equal value.' );

    /*
     * check for unique value
     */
    $.validator.addMethod('unique', function (value, element, param) {
        const $fields = $(param, element.form);
        const $fieldsFirst = $fields.eq(0);
        const validator = $fieldsFirst.data('valid_unique') ? $fieldsFirst.data('valid_unique' ) : $.extend( {}, this );

        let isValid = true;
        $fields.each(function () {
            if (element !== this && value.equalsIgnoreCase(validator.elementValue(this))) {
                isValid = false;
                return false;
            }
        });

        // store the cloned validator for future validation
        $fieldsFirst.data('valid_unique', validator);

        // If element isn't being validated, run each require_from_group field's
        // validation rules
        if (!$(element).data('being_validated') ) {
            $fields.data('being_validated', true);
            $fields.each(function () {
                validator.element(this);
            });
            $fields.data('being_validated', false);
        }

        return isValid;
    }, 'The value must be unique.');

    /*
     * override URL by adding the protocol prefix (if any)
     */
    $.validator.methods.url = (function (parent) {
        return function (value, element) {
            const regex = new RegExp(/^[\w+.-]+:\/\//);
            if (!regex.test(value)) {
                const protocol = $(element).data('protocol');
                if (protocol) {
                    value = protocol + '://' + value;
                }
            }
            return parent.call(this, value, element);
        };
    }($.validator.methods.url));

    /*
     * replace email with a simple <string>@<string>.<string> value
     */
    $.validator.methods.email = function (value, element) {
        return this.optional(element) || /\S+@\S+\.\S{2,}/.test(value);
    };

}(jQuery));
