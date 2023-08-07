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
         * Gets the validator.
         *
         * @return {jQuery|null}
         */
        getValidator() {
            return $(this).data('validator');
        },

        /**
         * Validate elements.
         *
         * @return {jQuery}
         */
        validateElement() {
            return this.each(function () {
                const $that = $(this);
                const $form = $that.parents('form');
                if ($form.length) {
                    const validator = $form.getValidator();
                    if (validator) {
                        validator.element($that);
                    }
                }
            });
        },

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
         * Initialize simple file input.
         *
         * @return {jQuery} the caller for chaining.
         */
        initSimpleFileInput() {
            return this.each(function () {
                const $this = $(this);
                const $delete = $this.parent().find('.btn-file-delete');
                if ($delete.length) {
                    $delete.on('click', function () {
                        $this.val('').trigger('change').trigger('focus');
                    });
                    $this.on('change', function () {
                        const empty = $this.val().length === 0;
                        $delete.toggleClass('d-none', empty);
                        $this.toggleClass('rounded-end', empty);
                        $this.valid();
                    });
                }
            });
        },

        /**
         * Gets password strength score or -1 if not found.
         *
         * @return {number}
         */
        findPasswordScore: function () {
            const $that = $(this);
            const data = $that.data('password-strength');
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
         * @return {jQuery} the color-picker drop-down, if found; null otherwise.
         */
        findColorPicker() {
            const $element = $(this);
            const $parent = $element.parents('.form-group');
            return $parent.findExists('button.color-picker');
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
         * @param {boolean} [options.recaptcha]
         * @param {boolean} [options.fileInput]
         * @param {boolean} [options.colorPicker]
         * @param {boolean} [options.simpleEditor]
         * @param {function} [options.submitHandler]
         * @param {Object} [options.spinner]
         * @param {Object} [options.rules]
         * @param {function} [options.highlight]
         * @param {function} [options.unhighlight]
         * @param {function} [options.invalidHandler]
         * @returns the validator.
         */
        initValidator: function (options) {
            // get options
            options = options || {};
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
                                $recaptcha.trigger('focus');
                                $elements.trigger('focusin');
                                return;
                            }
                        }

                        // color-picker
                        if (colorPicker) {
                            const $colorPicker = $elements.findColorPicker();
                            if ($colorPicker) {
                                $colorPicker.trigger('focus');
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
             * @param {jQuery} $element - the element to update.
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

            /**
             * Find elements with the same name attribute.
             *
             * @param {jQuery} $element the element to search same name for.
             * @return {jQuery[]}} the elements, if found; the argument element otherwise.
             */
            $.validator.prototype.findNamedElements = function ($element) {
                const name = $element.attr('name') || '';
                if (name.endsWith('[]')) {
                    const $elements = $element.closest('form').find(`[name="${name}"]`);
                    if ($elements.length) {
                        return $elements;
                    }
                }
                return $element;
            };

            // default options
            let defaults = {
                focus: true,
                focusInvalid: true,
                showModification: true,
                errorElement: 'small',
                errorClass: 'is-invalid', // d-inline-block or d-block',
                ignore: ':hidden:not(".must-validate"), .skip-validation',

                errorPlacement: function (error, element) {
                    const $parent = $(element).closest('.form-group, .mb-3');
                    error.addClass('invalid-feedback').appendTo($parent);
                },

                highlight: function (element, errorClass) {
                    const $element = $(element);
                    this.findNamedElements($element).addClass(errorClass);
                    const $toUpdate = this.findElement($element);
                    if ($toUpdate) {
                        $toUpdate.addClass('border-danger');
                        if ($toUpdate.hasClass('field-valid')) {
                            $toUpdate.removeClass('field-valid').addClass('field-invalid');
                        }
                    }
                },

                unhighlight: function (element, errorClass) {
                    const $element = $(element);
                    this.findNamedElements($element).removeClass(errorClass);
                    const $toUpdate = this.findElement($element);
                    if ($toUpdate) {
                        $toUpdate.removeClass('border-danger');
                        if ($toUpdate.hasClass('field-invalid')) {
                            $toUpdate.removeClass('field-invalid').addClass('field-valid');
                        }
                    }
                },

                invalidHandler: function (e, validator) {
                    // expand collapsed parent (if any)
                    const $element = $(validator.findLastActive() || (validator.errorList.length && validator.errorList[0].element));
                    const $collapse = $element.parents('.collapse:not(.show)');
                    if ($collapse.length) {
                        $collapse.collapse('show');
                    }
                }
            };

            if (!options.submitHandler) {
                defaults.submitHandler = function (form) {
                    $(form).showSubmit(options.spinner || {});
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

            // display message on modification
            if (settings.showModification) {
                const $message = $('#footer-message');
                if ($message.length) {
                    const data = $that.serialize();
                    $that.find(':input').on('change input', function () {
                        if ($that.serialize() === data) {
                            $message.hide(300);
                        } else {
                            $message.show(300);
                        }
                    });
                }
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
         * Display an information alert while the form is submitted.
         *
         * @param {Object} [options] - the alert options.
         * @return {jQuery} this form for chaining.
         */
        showSubmit: function (options) {
            //position-absolute top-50 start-50 translate-middle
            const $this = $(this);
            const settings = $.extend(true, {
                parent: $this,
                text: $this.data('save') || 'Saving data...',
                alertClass: 'alert bg-body-secondary border border-secondary-subtle text-center position-absolute top-50 start-50 translate-middle z-3',
                iconClass: 'fa-solid fa-spinner fa-spin me-2',
                css: {
                    width: '90%',
                    zIndex: 2
                },
                maxWidth: 600,
                show: 150
            }, options);

            // check width
            let width = settings.css.width;
            const parentWidth = settings.parent.width();
            const windowWidth = Math.floor($(window).width() * 0.9);
            if (width.endsWith('%')) {
                width = $.parseInt(width);
                width = Math.floor(parentWidth * width / 100.0);
            } else if (width.endsWith('px')) {
                width = $.parseInt(width, 10);
            }
            settings.css.width = Math.min(width, windowWidth, settings.maxWidth) + 'px';

            // build alert
            const $alert = $('<div />', {
                class: settings.alertClass,
                text: settings.text,
                css: settings.css,
                role: 'alert',
            });
            if (settings.iconClass) {
                const $icon = $('<i />', {
                    class: settings.iconClass
                });
                $alert.prepend($icon);
            }
            settings.parent.addClass('position-relative');
            $alert.appendTo($(settings.parent)).show(settings.show);

            return $this;
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
    $.validator.addMethod('greaterThanEqualValue', function (value, element, param) {
        return this.optional(element) || value >= param;
    }, 'The field must contain a greater than or equal value.');

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
    }, 'The field must contain a lesser than or equal value.');

    /*
     * check for unique value
     */
    $.validator.addMethod('unique', function (value, element, param) {
        const $fields = $(param, element.form);
        const $fieldsFirst = $fields.eq(0);
        const validator = $fieldsFirst.data('valid_unique') ? $fieldsFirst.data('valid_unique') : $.extend({}, this);

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
        if (!$(element).data('being_validated')) {
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
