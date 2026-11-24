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
    /** @type {EventTarget&Chart} */
    const chart = e.target;
    const selector = 'tr.row-data:not(.row-data-hidden)';
    $('#data tbody').on('mouseenter', selector, function () {
        $(this).showTooltip(chart);
    }).on('mouseleave', selector, function () {
        $(this).hideTooltip(chart);
    });
};

/**
 * Gets the tooltip template.
 * @return {string}
 */
window.getTooltipTemplate = function () {
    if (!window.tooltipTemplate) {
        window.tooltipTemplate = $('#tooltip').html().replace(/\s*\n\s*/g, '');
    }
    return window.tooltipTemplate;
}

/**
 * Render the HTML tooltip.
 * @param {Object.<string, string>} options
 */
window.renderTooltip = function (options) {
    'use strict';
    let html = window.getTooltipTemplate();
    Object.entries(options).forEach(([key, value]) => {
        html = html.replaceAll(`%${key}%`, value);
    });
    return html;
};

/**
 * Handle the legend item click event. Used only for the state chart.
 * @param {ItemClickedEvent} e
 */
window.itemClicked = function (e) {
    'use strict';
    /** @type {Chart} */
    const chart = e.target.chart;
    const index = e.legendItem.index + 1;
    const selector = `#data tbody tr:nth-child(${index})`;
    $(selector).toggleClass('row-data-hidden')
        .hideTooltip(chart);
};

(function ($) {
    'use strict';

    $.fn.extend({
        /**
         * Show the chart tooltip.
         * @param {Chart} chart
         */
        showTooltip: function (chart) {
            const index = $(this).index();
            const points = chart.series.map(e => e.points[index]);
            chart.tooltip.refresh(points);
        },

        /**
         * Hide the chart tooltip.
         * @param {Chart} chart
         */
        hideTooltip: function (chart) {
            chart.tooltip.hide();
            chart.series.forEach(series => {
                series.points.forEach(point => point.setState(''));
                series.setState('');
            });
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
