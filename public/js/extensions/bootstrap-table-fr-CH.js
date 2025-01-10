/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
$(function () {
    'use strict';

    /**
     * Custom messages for the French Swiss locale.
     */
    $.fn.bootstrapTable.locales['fr-CH'] = {
        formatLoadingMessage: function () {
            return 'Chargement en cours';
        },
        formatShowingRows: function (pageFrom, pageTo, totalRows, totalNotFiltered) {
            const rows = Number.parseInt(totalRows);
            pageFrom = $.formatInt(pageFrom);
            pageTo = $.formatInt(pageTo);
            totalRows = $.formatInt(rows);
            if (totalNotFiltered > 0 && totalNotFiltered > rows) {
                totalNotFiltered = $.formatInt(totalNotFiltered);
                return 'Entrée {0} - {1} / {2} ({3} au total)'.format(pageFrom, pageTo, totalRows, totalNotFiltered);
            }
            return 'Entrée {0} - {1} / {2}'.format(pageFrom, pageTo, totalRows);
        },
        formatSRPaginationPreText: function () {
            return 'Afficher la page précédente';
        },
        formatSRPaginationPageText: function (page) {
            return 'Afficher la page {0}'.format($.formatInt(page));
        },
        formatSRPaginationNextText: function () {
            return 'Afficher la page suivante';
        },
        formatNoMatches: function () {
            return 'Aucune entrée ne correspond aux critères de recherche.';
        }
    };
    Object.assign($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['fr-CH'])
});
