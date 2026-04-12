/**
 * Handle the chart load event.
 *
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
