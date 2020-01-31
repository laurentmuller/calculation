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
        required_default: "Ce champ est requis.",
        required_label: 'Le champ \"{0}\" est requis.',
        required: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.required_label, $.validator.messages.required_default);
        },

        /*
         * accept (file type)
         */
        accept_default: 'Ce champ doit contenir un type de fichier valide.',
        accept_label: 'Le champ \"{0}\" doit contenir un type de fichier valide.',
        accept: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.accept_label, $.validator.messages.accept_default);
        },

        /*
         * password
         */
        password_levels: ["Très faible", "Faible", "Moyen", "Fort", "Très fort"],
        password_default: "Ce champ doit être \"{0}\" (valeur actuelle : \"{2}\").",
        password_label: "Le champ \"{0}\" doit être \"{1}\" (valeur actuelle : \"{2}\").",
        password: function (parameter, element) {
            let current = $(element).findPasswordScore();
            current = $.validator.messages.password_levels[current];
            const level = $.validator.messages.password_levels[parameter];
            return $.validator.formatLabel(element, $.validator.messages.password_label, $.validator.messages.password_default, level, current);
        },

        /*
         * not contains field
         */
        notUsername_default: "Ce champ ne peut pas contenir le nom de l'utilisateur.",
        notUsername_label: "Le champ \"{0}\" ne peut pas contenir le nom de l'utilisateur.",
        notUsername: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.notUsername_label, $.validator.messages.notUsername_default);
        },

        /*
         * not email
         */
        notEmail_default: "Ce champ ne peut pas être une adresse e-mail.",
        notEmail_label: "Le champ \"{0}\" ne peut pas être une adresse e-mail.",
        notEmail: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.notEmail_label, $.validator.messages.notEmail_default);
        },

        /*
         * lower case character
         */
        lowercase_default: "Ce champ doit contenir un caractère minuscule.",
        lowercase_label: "Le champ \"{0}\" doit contenir un caractère minuscule.",
        lowercase: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.lowercase_label, $.validator.messages.lowercase_default);
        },

        /*
         * upper case character
         */
        uppercase_default: "Ce champ doit contenir un caractère majuscule.",
        uppercase_label: "Le champ \"{0}\" doit contenir un caractère majuscule.",
        uppercase: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.uppercase_label, $.validator.messages.uppercase_default);
        },

        /*
         * mixed case characters
         */
        mixedcase_default: "Ce champ doit contenir des caractères minuscule et majuscule.",
        mixedcase_label: "Le champ \"{0}\" doit contenir des caractères minuscule et majuscule.",
        mixedcase: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.mixedcase_label, $.validator.messages.mixedcase_default);
        },

        /*
         * digit character
         */
        digit_default: "Ce champ doit contenir un chiffre.",
        digit_label: "Le champ \"{0}\" doit contenir un chiffre.",
        digit: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.digit_label, $.validator.messages.digit_default);
        },

        /*
         * special character
         */
        specialchar_default: "Ce champ doit contenir un caractère spécial.",
        specialchar_label: "Le champ \"{0}\" doit contenir un caractère spécial.",
        specialchar: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.specialchar_label, $.validator.messages.specialchar_default);
        },

        /*
         * letter character
         */
        letter_default: "Ce champ doit contenir un caractère alphabétique.",
        letter_label: "Le champ \"{0}\" doit contenir un caractère alphabétique.",
        letter: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.letter_label, $.validator.messages.letter_default);
        },

        /*
         * maximum files (file type)
         */
        maxfiles: "Le nombre de fichiers ne doit pas être supérieur à {0}.",
        // maxfiles_default: "Le nombre de fichiers sélectionnés ne doit pas être supérieur à {0}.",
        // maxfiles_label: "Le champ \"{0}\" ne doit pas être supérieur à {1}.",
        // maxfiles: function (parameter, element) {
        // return $.validator.formatLabel(element, $.validator.messages.maxfiles_label, $.validator.messages.maxfiles_default, parameter);
        // },

        /*
         * maximum size (file type)
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
         * not equal to
         */
        notEqualTo_default: "Veuillez fournir une valeur différente, les valeurs ne doivent pas être identiques.",
        notEqualTo_Label: "Le champ \"{0}\" doit être différent du champ \"{1}\".",
        notEqualTo: function (parameters, element) {
            const target = $(element).getLabelText();
            const source = $(parameters).getLabelText();
            if (target && source) {
                return $.validator.format($.validator.messages.notEqualTo_Label, target, source);
            }
            return $.validator.messages.notEqualTo_default;
        }
    });
});