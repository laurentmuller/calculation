/**
 * @typedef {Object} LegendItem
 * @property {boolean} visible
 * @property {number} index
 */

/**
 * @typedef {Event} ItemClickedEvent
 * @property {LegendItem} legendItem
 */

/**
 * Handle the chart load event.
 * @param {Event} e
 */
window.chartLoaded = function (e) {
    'use strict';
    const chart = e.target;
    $('#data tbody tr.row-data').on('mouseenter', function () {
        $(this).showTooltip(chart);
    }).on('mouseleave', function () {
        $(this).hideTooltip(chart);
    });
};

/**
 * Handle the legend item click event. Used only for the state chart.
 * @param {ItemClickedEvent} e
 */
window.itemClicked = function (e) {
    'use strict';
    const index = e.legendItem.index + 1;
    $(`#data tbody tr:nth-child(${index}) td`)
        .toggleClass('text-decoration-line-through text-secondary');
    $(`#data tbody tr:nth-child(${index}) .state-color`)
        .toggleClass('bg-secondary');
};

(function ($) {
    'use strict';

    $.fn.extend({
        /**
         * Show the chart tooltip.
         * @param {Object} chart
         */
        showTooltip: function (chart) {
            const index = $(this).index();
            const points = chart.series.map(e => e.points[index]);
            chart.tooltip.refresh(points);
        },

        /**
         * Hide the chart tooltip.
         * @param {Object} chart
         */
        hideTooltip: function (chart) {
            chart.tooltip.hide();
        }
    });

    $(function () {
        $('#data').tooltip({
            customClass: 'tooltip-danger',
            selector: '.has-tooltip',
            html: true
        });
    });
}(jQuery));
