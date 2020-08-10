/**! compression tag for ftp-deployment */

/**
 * -------------- Validator messages --------------
 */
(function ($) {
    'use strict';

    $.extend($.validator.messages, {

        /*
         * required (all)
         */
        requiredFallback: "Ce champ est requis.",
        requiredLabel: 'Le champ \"{0}\" est requis.',
        required: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.requiredLabel, $.validator.messages.requiredFallback);
        },

        /*
         * accept (file type)
         */
        acceptFallback: 'Ce champ doit contenir un type de fichier valide.',
        acceptLabel: 'Le champ \"{0}\" doit contenir un type de fichier valide.',
        accept: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.acceptLabel, $.validator.messages.acceptFallback);
        },

        /*
         * password
         */
        passwordLevels: ["Très faible", "Faible", "Moyen", "Fort", "Très fort"],
        passwordFallback: "Ce champ doit être \"{0}\" (valeur actuelle : \"{2}\").",
        passwordLabel: "Le champ \"{0}\" doit être \"{1}\" (valeur actuelle : \"{2}\").",
        password: function (parameter, element) {
            let current = $(element).findPasswordScore();
            current = $.validator.messages.passwordLevels[current];
            const level = $.validator.messages.passwordLevels[parameter];
            return $.validator.formatLabel(element, $.validator.messages.passwordLabel, $.validator.messages.passwordFallback, level, current);
        },

        /*
         * notUsername
         */
        notUsernameFallback: "Ce champ ne peut pas contenir le nom de l'utilisateur.",
        notUsernameLabel: "Le champ \"{0}\" ne peut pas contenir le nom de l'utilisateur.",
        notUsername: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.notUsernameLabel, $.validator.messages.notUsernameFallback);
        },

        /*
         * notEmail
         */
        notEmailFallback: "Ce champ ne peut pas être une adresse e-mail.",
        notEmailLabel: "Le champ \"{0}\" ne peut pas être une adresse e-mail.",
        notEmail: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.notEmailLabel, $.validator.messages.notEmailFallback);
        },

        /*
         * lowercase
         */
        lowercaseFallback: "Ce champ doit contenir un caractère minuscule.",
        lowercaseLabel: "Le champ \"{0}\" doit contenir un caractère minuscule.",
        lowercase: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.lowercaseLabel, $.validator.messages.lowercaseFallback);
        },

        /*
         * uppercase
         */
        uppercaseFallback: "Ce champ doit contenir un caractère majuscule.",
        uppercaseLabel: "Le champ \"{0}\" doit contenir un caractère majuscule.",
        uppercase: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.uppercaseLabel, $.validator.messages.uppercaseFallback);
        },

        /*
         * mixedcase
         */
        mixedcaseFallback: "Ce champ doit contenir des caractères minuscule et majuscule.",
        mixedcaseLabel: "Le champ \"{0}\" doit contenir des caractères minuscule et majuscule.",
        mixedcase: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.mixedcaseLabel, $.validator.messages.mixedcaseFallback);
        },

        /*
         * digit
         */
        digitFallback: "Ce champ doit contenir un chiffre.",
        digitLabel: "Le champ \"{0}\" doit contenir un chiffre.",
        digit: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.digitLabel, $.validator.messages.digitFallback);
        },

        /*
         * specialchar
         */
        specialcharFallback: "Ce champ doit contenir un caractère spécial.",
        specialcharLabel: "Le champ \"{0}\" doit contenir un caractère spécial.",
        specialchar: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.specialcharLabel, $.validator.messages.specialcharFallback);
        },

        /*
         * letter
         */
        letterFallback: "Ce champ doit contenir un caractère alphabétique.",
        letterLabel: "Le champ \"{0}\" doit contenir un caractère alphabétique.",
        letter: function (parameters, element) {
            return $.validator.formatLabel(element, $.validator.messages.letterLabel, $.validator.messages.letterFallback);
        },

        /*
         * maximumfiles (file type)
         */
        maxfiles: "Le nombre de fichiers ne doit pas être supérieur à {0}.",
        // maxfilesFallback: "Le nombre de fichiers sélectionnés ne doit pas
        // être supérieur à {0}.",
        // maxfilesLabel: "Le champ \"{0}\" ne doit pas être supérieur à {1}.",
        // maxfiles: function (parameter, element) {
        // return $.validator.formatLabel(element,
        // $.validator.messages.maxfilesLabel,
        // $.validator.messages.maxfilesFallback, parameter);
        // },

        /*
         * maxsize (file type)
         */
        maxsizeMessage: "La taille de chaque fichier ne doit pas dépasser {0} {1}.",
        maxsizeUnits: ["Bytes", "Kb", "Mb", "Gb", "Tb"],
        maxsize: function (bytes) { // parameter
            const units = $.validator.messages.maxsizeUnits;
            const message = $.validator.messages.maxsizeMessage;
            const index = Math.floor(Math.log(bytes) / Math.log(1024));
            const text = (bytes / Math.pow(1024, index)).toFixed(2) * 1;
            return $.validator.format(message, text, units[index]);
        },

        /*
         * notEqualTo
         */
        notEqualToFallback: "Veuillez fournir une valeur différente, les valeurs ne doivent pas être identiques.",
        notEqualToLabel: "Le champ \"{0}\" doit être différent.",
        notEqualToBoth: "Le champ \"{0}\" doit être différent du champ \"{1}\".",
        notEqualTo: function (parameters, element) {
            const target = $(element).getLabelText();
            const source = $(parameters).getLabelText();
            if (target && source) {
                return $.validator.format($.validator.messages.notEqualToBoth, target, source);
            } else if (target) {
                return $.validator.format($.validator.messages.notEqualToLabel, target);
            } else {
                return $.validator.messages.notEqualToFallback;
            }
        },

        /*
         * greaterThan
         */
        greaterThanFallback: "Veuillez fournir une valeur supérieure.",
        greaterThanLabel: "Le champ \"{0}\" doit avoir une valeur supérieure.",
        greaterThanBoth: "Le champ \"{0}\" doit avoir une valeur supérieure au champ \"{1}\".",
        greaterThan: function (parameters, element) {
            const target = $(element).getLabelText();
            const source = $(parameters).getLabelText();
            if (target && source) {
                return $.validator.format($.validator.messages.greaterThanBoth, target, source);
            } else if (target) {
                return $.validator.format($.validator.messages.greaterThanLabel, target);
            } else {
                return $.validator.messages.greaterThanFallback;
            }
        },

        /*
         * greaterThanEqual
         */
        greaterThanEqualFallback: "Veuillez fournir une valeur égale ou supérieure.",
        greaterThanEqualLabel: "Le champ \"{0}\" doit avoir une valeur égale ou supérieure.",
        greaterThanEqualBoth: "Le champ \"{0}\" doit avoir une valeur égale ou supérieure au champ \"{1}\".",
        greaterThanEqual: function (parameters, element) {
            const target = $(element).getLabelText();
            const source = $(parameters).getLabelText();
            if (target && source) {
                return $.validator.format($.validator.messages.greaterThanEqualBoth, target, source);
            } else if (target) {
                return $.validator.format($.validator.messages.greaterThanEqualLabel, target);
            } else {
                return $.validator.messages.greaterThanEqualFallback;
            }
        },

        /*
         * lessThan
         */
        lessThanFallback: "Veuillez fournir une valeur inférieure.",
        lessThanLabel: "Le champ \"{0}\" doit avoir une valeur inférieure au champ \"{1}\".",
        lessThanBoth: "Le champ \"{0}\" doit avoir une valeur inférieure au champ \"{1}\".",
        lessThan: function (parameters, element) {
            const target = $(element).getLabelText();
            const source = $(parameters).getLabelText();
            if (target && source) {
                return $.validator.format($.validator.messages.lessThanBoth, target, source);
            } else if (target) {
                return $.validator.format($.validator.messages.lessThanLabel, target);
            } else {
                return $.validator.messages.lessThanFallback;
            }
        },

        /*
         * lessThanEqual
         */
        lessThanEqualFallback: "Veuillez fournir une valeur égale ou inférieure.",
        lessThanEqualLabel: "Le champ \"{0}\" doit avoir une valeur égale ou inférieure.",
        lessThanEqualBoth: "Le champ \"{0}\" doit avoir une valeur égale ou inférieure au champ \"{1}\".",
        lessThanEqual: function (parameters, element) {
            const target = $(element).getLabelText();
            const source = $(parameters).getLabelText();
            if (target && source) {
                return $.validator.format($.validator.messages.lessThanEqualBoth, target, source);
            } else if (target) {
                return $.validator.format($.validator.messages.lessThanEqualLabel, target);
            } else {
                return $.validator.messages.lessThanEqualFallback;
            }
        }
    });
}(jQuery));
