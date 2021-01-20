/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    $.fn.bootstrapTable.locales['fr-CH'] = {
        formatCopyRows: function formatCopyRows() {
            return 'Copier les lignes';
        },
        formatPrint: function formatPrint() {
            return 'Imprimer';
        },
        formatLoadingMessage: function formatLoadingMessage() {
            return 'Chargement en cours';
        },
        formatRecordsPerPage: function formatRecordsPerPage(pageNumber) {
            return "".concat(pageNumber, " entrées par page");
        },
        formatShowingRows: function formatShowingRows(pageFrom, pageTo, totalRows, totalNotFiltered) {
            if (typeof totalNotFiltered !== 'undefined' && totalNotFiltered > 0 && totalNotFiltered > totalRows) {
                return "Entrée ".concat(pageFrom, " - ").concat(pageTo, " / ").concat(totalRows, " (").concat(totalNotFiltered, " au total)");
            }
            return "Entrée ".concat(pageFrom, " - ").concat(pageTo, " / ").concat(totalRows);
        },
        formatSRPaginationPreText: function formatSRPaginationPreText() {
            return 'Page précédente';
        },
        formatSRPaginationPageText: function formatSRPaginationPageText(page) {
            return "Afficher la page ".concat(page);
        },
        formatSRPaginationNextText: function () {
            return 'Page suivante';
        },
        formatDetailPagination: function formatDetailPagination(totalRows) {
            return "Afficher ".concat(totalRows, " entrées");
        },
        formatClearSearch: function formatClearSearch() {
            return 'Effacer les critères de recherche';
        },
        formatSearch: function formatSearch() {
            return 'Rechercher';
        },
        formatNoMatches: function formatNoMatches() {
            return 'Aucune entrée ne correspond à la recherche.';
        },
        formatPaginationSwitch: function formatPaginationSwitch() {
            return 'Cacher/Afficher la pagination';
        },
        formatPaginationSwitchDown: function formatPaginationSwitchDown() {
            return 'Afficher la pagination';
        },
        formatPaginationSwitchUp: function formatPaginationSwitchUp() {
            return 'Cacher la pagination';
        },
        formatRefresh: function formatRefresh() {
            return 'Rafraichir';
        },
        formatToggle: function formatToggle() {
            return 'Basculer';
        },
        formatToggleOn: function formatToggleOn() {
            return 'Afficher la vue détaillée';
        },
        formatToggleOff: function formatToggleOff() {
            return 'Afficher la vue tabulaire';
        },
        formatColumns: function formatColumns() {
            return 'Colonnes';
        },
        formatColumnsToggleAll: function formatColumnsToggleAll() {
            return 'Tout basculer';
        },
        formatFullscreen: function formatFullscreen() {
            return 'Plein écran';
        },
        formatAllRows: function formatAllRows() {
            return 'Tout';
        },
        formatAutoRefresh: function formatAutoRefresh() {
            return 'Rafraîchissement automatique';
        },
        formatExport: function formatExport() {
            return 'Exporter les données';
        },
        formatJumpTo: function formatJumpTo() {
            return 'Aller à';
        },
        formatAdvancedSearch: function formatAdvancedSearch() {
            return 'Recherche avancée';
        },
        formatAdvancedCloseButton: function formatAdvancedCloseButton() {
            return 'Fermer';
        },
        formatFilterControlSwitch: function formatFilterControlSwitch() {
            return 'Cacher/Afficher les controls';
        },
        formatFilterControlSwitchHide: function formatFilterControlSwitchHide() {
            return 'Cacher les controls';
        },
        formatFilterControlSwitchShow: function formatFilterControlSwitchShow() {
            return 'Afficher les controls';
        }
    };
    $.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['fr-CH']);
}(jQuery));
