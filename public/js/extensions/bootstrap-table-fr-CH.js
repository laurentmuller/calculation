/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $.fn.bootstrapTable.locales['fr-CH'] = {
        formatCopyRows: function () {
            return 'Copier les lignes';
        },
        formatPrint: function formatPrint() {
            return 'Imprimer';
        },
        formatLoadingMessage: function () {
            return 'Chargement en cours';
        },
        formatRecordsPerPage: function (pageNumber) {
            pageNumber = $.formatInt(pageNumber);
            return "".concat(pageNumber, " entrées par page");
        },
        formatShowingRows: function (pageFrom, pageTo, totalRows, totalNotFiltered) {
            pageFrom = $.formatInt(pageFrom);
            pageTo = $.formatInt(pageTo);
            totalRows = $.formatInt(totalRows);
            if (typeof totalNotFiltered !== 'undefined' && totalNotFiltered > 0 && totalNotFiltered > totalRows) {
                totalNotFiltered = $.formatInt(totalNotFiltered);
                return `Entrée ${pageFrom} - ${pageTo} / ${totalRows} (${totalNotFiltered} au total)`;
            }
            return `Entrée ${pageFrom} - ${pageTo} / ${totalRows}`;
        },
        formatSRPaginationPreText: function () {
            return 'Afficher la page précédente';
        },
        formatSRPaginationPageText: function (page) {
            page = $.formatInt(page);
            return `Afficher la page ${page}`;
        },
        formatSRPaginationNextText: function () {
            return 'Afficher la page suivante';
        },
        formatDetailPagination: function (totalRows) {
            totalRows = $.formatInt(totalRows);
            return `Afficher ${totalRows} entrées`;
        },
        formatClearSearch: function () {
            return 'Effacer les critères de recherche';
        },
        formatSearch: function formatSearch() {
            return 'Rechercher';
        },
        formatNoMatches: function () {
            return 'Aucune donnée ne correspond aux critères de recherche.';
        },
        formatPaginationSwitch: function () {
            return 'Cacher/Afficher la pagination';
        },
        formatPaginationSwitchDown: function () {
            return 'Afficher la pagination';
        },
        formatPaginationSwitchUp: function () {
            return 'Cacher la pagination';
        },
        formatRefresh: function () {
            return 'Rafraichir';
        },
        formatToggle: function () {
            return 'Basculer';
        },
        formatToggleOn: function () {
            return 'Afficher la vue détaillée';
        },
        formatToggleOff: function () {
            return 'Afficher la vue tabulaire';
        },
        formatColumns: function () {
            return 'Colonnes';
        },
        formatColumnsToggleAll: function () {
            return 'Tout basculer';
        },
        formatFullscreen: function () {
            return 'Plein écran';
        },
        formatAllRows: function () {
            return 'Tout';
        },
        formatAutoRefresh: function () {
            return 'Rafraîchissement automatique';
        },
        formatExport: function () {
            return 'Exporter les données';
        },
        formatJumpTo: function () {
            return 'Aller à';
        },
        formatAdvancedSearch: function () {
            return 'Recherche avancée';
        },
        formatAdvancedCloseButton: function () {
            return 'Fermer';
        },
        formatFilterControlSwitch: function () {
            return 'Cacher/Afficher les controls';
        },
        formatFilterControlSwitchHide: function () {
            return 'Cacher les controls';
        },
        formatFilterControlSwitchShow: function () {
            return 'Afficher les controls';
        }
    };
    $.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['fr-CH']);
}(jQuery));
