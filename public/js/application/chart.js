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
    $('#data tbody tr.row-data').on('mouseenter', function () {
        const $this = $(this);
        if (!$this.hasClass('row-data-hidden')) {
            $this.showTooltip(chart);
        }
    }).on('mouseleave', function () {
        const $this = $(this);
        if (!$this.hasClass('row-data-hidden')) {
            $(this).hideTooltip(chart);
        }
    });
};

/**
 * Render the HTML tooltip.
 * @param {Object.<string, string>} options
 */
window.renderTooltip = function (options) {
    'use strict';
    /** @type {string} */
    let html = $('#tooltip').html();
    Object.entries(options).forEach(([key, value]) => {
        html = html.replace(`{${key}}`, value);
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
