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
            pageFrom = $.formatInt(pageFrom);
            pageTo = $.formatInt(pageTo);
            const formattedRows = $.formatInt(totalRows);
            if (totalNotFiltered > 0 && totalNotFiltered > totalRows) {
                totalNotFiltered = $.formatInt(totalNotFiltered);
                return 'Entrée {0} - {1} / {2} ({3} au total)'.format(pageFrom, pageTo, formattedRows, totalNotFiltered);
            }
            return 'Entrée {0} - {1} / {2}'.format(pageFrom, pageTo, formattedRows);
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
    Object.assign($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['fr-CH']);
});
