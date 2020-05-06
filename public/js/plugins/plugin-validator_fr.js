/**! compression tag for ftp-deployment */

/**
 * -------------- Validator messages --------------
 */
$(function () {
    'use strict';

    $.extend($.validator.messages, {

        /*
         * required (all)
         */
        required_fallback: "Ce champ est requis.",
        required_label: 'Le champ \"{0}\" est requis.',
        required: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.required_label, $.validator.messages.required_fallback);
        },

        /*
         * accept (file type)
         */
        accept_fallback: 'Ce champ doit contenir un type de fichier valide.',
        accept_label: 'Le champ \"{0}\" doit contenir un type de fichier valide.',
        accept: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.accept_label, $.validator.messages.accept_fallback);
        },

        /*
         * password
         */
        password_levels: ["Très faible", "Faible", "Moyen", "Fort", "Très fort"],
        password_fallback: "Ce champ doit être \"{0}\" (valeur actuelle : \"{2}\").",
        password_label: "Le champ \"{0}\" doit être \"{1}\" (valeur actuelle : \"{2}\").",
        password: function (parameter, element) {
            let current = $(element).findPasswordScore();
            current = $.validator.messages.password_levels[current];
            const level = $.validator.messages.password_levels[parameter];
            return $.validator.formatLabel(element, $.validator.messages.password_label, $.validator.messages.password_fallback, level, current);
        },

        /*
         * notUsername
         */
        notUsername_fallback: "Ce champ ne peut pas contenir le nom de l'utilisateur.",
        notUsername_label: "Le champ \"{0}\" ne peut pas contenir le nom de l'utilisateur.",
        notUsername: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.notUsername_label, $.validator.messages.notUsername_fallback);
        },

        /*
         * notEmail
         */
        notEmail_fallback: "Ce champ ne peut pas être une adresse e-mail.",
        notEmail_label: "Le champ \"{0}\" ne peut pas être une adresse e-mail.",
        notEmail: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.notEmail_label, $.validator.messages.notEmail_fallback);
        },

        /*
         * lowercase
         */
        lowercase_fallback: "Ce champ doit contenir un caractère minuscule.",
        lowercase_label: "Le champ \"{0}\" doit contenir un caractère minuscule.",
        lowercase: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.lowercase_label, $.validator.messages.lowercase_fallback);
        },

        /*
         * uppercase
         */
        uppercase_fallback: "Ce champ doit contenir un caractère majuscule.",
        uppercase_label: "Le champ \"{0}\" doit contenir un caractère majuscule.",
        uppercase: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.uppercase_label, $.validator.messages.uppercase_fallback);
        },

        /*
         * mixedcase
         */
        mixedcase_fallback: "Ce champ doit contenir des caractères minuscule et majuscule.",
        mixedcase_label: "Le champ \"{0}\" doit contenir des caractères minuscule et majuscule.",
        mixedcase: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.mixedcase_label, $.validator.messages.mixedcase_fallback);
        },

        /*
         * digit
         */
        digit_fallback: "Ce champ doit contenir un chiffre.",
        digit_label: "Le champ \"{0}\" doit contenir un chiffre.",
        digit: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.digit_label, $.validator.messages.digit_fallback);
        },

        /*
         * specialchar
         */
        specialchar_fallback: "Ce champ doit contenir un caractère spécial.",
        specialchar_label: "Le champ \"{0}\" doit contenir un caractère spécial.",
        specialchar: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.specialchar_label, $.validator.messages.specialchar_fallback);
        },

        /*
         * letter
         */
        letter_fallback: "Ce champ doit contenir un caractère alphabétique.",
        letter_label: "Le champ \"{0}\" doit contenir un caractère alphabétique.",
        letter: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.letter_label, $.validator.messages.letter_fallback);
        },

        /*
         * maximumfiles (file type)
         */
        maxfiles: "Le nombre de fichiers ne doit pas être supérieur à {0}.",
        // maxfiles_fallback: "Le nombre de fichiers sélectionnés ne doit pas
        // être supérieur à {0}.",
        // maxfiles_label: "Le champ \"{0}\" ne doit pas être supérieur à {1}.",
        // maxfiles: function (parameter, element) {
        // return $.validator.formatLabel(element,
        // $.validator.messages.maxfiles_label,
        // $.validator.messages.maxfiles_fallback, parameter);
        // },

        /*
         * maxsize (file type)
         */
        maxsize_message: "La taille de chaque fichier ne doit pas dépasser {0} {1}.",
        maxsize_units: ["Bytes", "Kb", "Mb", "Gb", "Tb"],
        maxsize: function (bytes) { // parameter
            const units = $.validator.messages.maxsize_units;
            const message = $.validator.messages.maxsize_message;
            const index = Math.floor(Math.log(bytes) / Math.log(1024));
            const text = (bytes / Math.pow(1024, index)).toFixed(2) * 1;
            return $.validator.format(message, text, units[index]);
        },

        /*
         * notEqualTo
         */
        notEqualTo_fallback: "Veuillez fournir une valeur différente, les valeurs ne doivent pas être identiques.",
        notEqualTo_label: "Le champ \"{0}\" doit être différent.",
        notEqualTo_both: "Le champ \"{0}\" doit être différent du champ \"{1}\".",
        notEqualTo: function (parameters, element) {
            const target = $(element).getLabelText();
            const source = $(parameters).getLabelText();
            if (target && source) {
                return $.validator.format($.validator.messages.notEqualTo_both, target, source);
            } else if (target) {
                return $.validator.format($.validator.messages.notEqualTo_label, target);
            } else {
                return $.validator.messages.notEqualTo_fallback;
            }
        },

        /*
         * greaterThan
         */
        greaterThan_fallback: "Veuillez fournir une valeur supérieure.",
        greaterThan_label: "Le champ \"{0}\" doit avoir une valeur supérieure.",
        greaterThan_both: "Le champ \"{0}\" doit avoir une valeur supérieure au champ \"{1}\".",
        greaterThan: function (parameters, element) {
            const target = $(element).getLabelText();
            const source = $(parameters).getLabelText();
            if (target && source) {
                return $.validator.format($.validator.messages.greaterThan_both, target, source);
            } else if (target) {
                return $.validator.format($.validator.messages.greaterThan_label, target);
            } else {
                return $.validator.messages.greaterThan_fallback;
            }
        },

        /*
         * greaterThanEqual
         */
        greaterThanEqual_fallback: "Veuillez fournir une valeur égale ou supérieure.",
        greaterThanEqual_label: "Le champ \"{0}\" doit avoir une valeur égale ou supérieure.",
        greaterThanEqual_both: "Le champ \"{0}\" doit avoir une valeur égale ou supérieure au champ \"{1}\".",
        greaterThanEqual: function (parameters, element) {
            const target = $(element).getLabelText();
            const source = $(parameters).getLabelText();
            if (target && source) {
                return $.validator.format($.validator.messages.greaterThanEqual_both, target, source);
            } else if (target) {
                return $.validator.format($.validator.messages.greaterThanEqual_label, target);
            } else {
                return $.validator.messages.greaterThanEqual_fallback;
            }
        },

        /*
         * lessThan
         */
        lessThan_fallback: "Veuillez fournir une valeur inférieure.",
        lessThan_label: "Le champ \"{0}\" doit avoir une valeur inférieure au champ \"{1}\".",
        lessThan_both: "Le champ \"{0}\" doit avoir une valeur inférieure au champ \"{1}\".",
        lessThan: function (parameters, element) {
            const target = $(element).getLabelText();
            const source = $(parameters).getLabelText();
            if (target && source) {
                return $.validator.format($.validator.messages.lessThan_both, target, source);
            } else if (target) {
                return $.validator.format($.validator.messages.lessThan_label, target);
            } else {
                return $.validator.messages.lessThan_fallback;
            }
        },

        /*
         * lessThanEqual
         */
        lessThanEqual_fallback: "Veuillez fournir une valeur égale ou inférieure.",
        lessThanEqual_label: "Le champ \"{0}\" doit avoir une valeur égale ou inférieure.",
        lessThanEqual_both: "Le champ \"{0}\" doit avoir une valeur égale ou inférieure au champ \"{1}\".",
        lessThanEqual: function (parameters, element) {
            const target = $(element).getLabelText();
            const source = $(parameters).getLabelText();
            if (target && source) {
                return $.validator.format($.validator.messages.lessThanEqual_both, target, source);
            } else if (target) {
                return $.validator.format($.validator.messages.lessThanEqual_label, target);
            } else {
                return $.validator.messages.lessThanEqual_fallback;
            }
        }
    });
});