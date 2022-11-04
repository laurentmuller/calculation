/**! compression tag for ftp-deployment */

/**
 * Plugin to highlight cells.
 */
(function ($) {
    'use strict';

    // ------------------------------------
    // jQuery extensions
    // ------------------------------------
    $.fn.extend({
        rowspan() {
            return parseInt($(this).attr('rowspan'), 10) || 1;
        },
        colspan() {
            return parseInt($(this).attr('colspan'), 10) || 1;
        }
    });

    // ------------------------------------
    // CellHighlight public class definition
    // ------------------------------------
    const CellHighlight = class {

        // -----------------------------
        // public functions
        // -----------------------------

        /**
         * Constructor
         *
         * @param {HTMLElement} element - the element to handle.
         * @param {Object|string} options - the plugin options.
         */
        constructor(element, options) {
            this.$element = $(element);
            this.options = $.extend(true, {}, CellHighlight.DEFAULTS, options);
            this.tableIndex = this.indexTable();
            this.enabled = false;
            this.mouseEnterProxy = (e) => this.mouseenter(e);
            this.mouseLeaveProxy = () => this.mouseleave();

            // update enablement
            const enabled = this.isUndefined(this.options.enabled) ? true : this.options.enabled;
            if (enabled) {
                this.enable();
            }
        }

        enable() {
            if (!this.enabled) {
                const selector = this.options.cellSelector;
                this.$element.on('mouseenter', selector, this.mouseEnterProxy);
                this.$element.on('mouseleave', selector, this.mouseLeaveProxy);
                this.enabled = true;
            }
        }

        disable() {
            if (this.enabled) {
                const selector = this.options.cellSelector;
                this.$element.off('mouseenter', selector, this.mouseEnterProxy);
                this.$element.off('mouseleave', selector, this.mouseLeaveProxy);
                this.enabled = false;
            }
        }

        destroy() {
            this.disable();
            this.$element.removeData(CellHighlight.NAME);
        }

        // -----------------------------
        // private functions
        // -----------------------------
        mouseenter(e) {
            const that = this;
            const $target = $(e.currentTarget);
            const rowspan = $target.rowspan();
            const colspan = $target.colspan();
            const offsetInMatrix = $target.data('cellHighlight.offsetInMatrix');
            const tableIndex = this.tableIndex;
            const options = that.options;

            // add horizontal cells
            that.horizontal = $([]);
            $.each(tableIndex.slice(offsetInMatrix[1], offsetInMatrix[1] + rowspan), function (_n, cell) {
                that.horizontal = that.horizontal.add(cell);
            });

            // add vertical cells
            that.vertical = $([]);
            $.each(tableIndex, function (_n, rowIndex) {
                that.vertical = that.vertical.add(rowIndex.slice(offsetInMatrix[0], offsetInMatrix[0] + colspan));
            });


            // add classes
            if (options.highlightHorizontal) {
                that.horizontal.addClass(options.highlightHorizontal);
            }
            if (options.highlightVertical) {
                that.vertical.addClass(options.highlightVertical);
            }

            // trigger events
            that.horizontal.trigger('cellHighlight.mouseenter-horizontal');
            that.vertical.trigger('cellHighlight.mouseenter-vertical');

            that.$element.trigger('cellHighlight.mouseenter', {
                horizontal: that.horizontal,
                vertical: that.vertical
            });
        }

        mouseleave() {
            const that = this;
            const options = that.options;
            if (!that.horizontal && !that.vertical) {
                return;
            }

            // remove classes
            if (options.highlightHorizontal) {
                that.horizontal.removeClass(options.highlightHorizontal);
            }
            if (options.highlightVertical) {
                that.vertical.removeClass(options.highlightVertical);
            }

            // trigger events
            that.horizontal.trigger('cellHighlight.mouseleave-horizontal');
            that.vertical.trigger('cellHighlight.mouseleave-vertical');

            that.$element.trigger('cellHighlight.mouseleave', {
                horizontal: that.horizontal,
                vertical: that.vertical
            });

            // clean
            that.horizontal = that.vertical = false;
        }

        getTableRows() {
            const selector = this.options.rowSelector;
            return this.$element.find(selector);
        }

        getTableMaxCellLength() {
            let maxWidth = 0;
            const that  = this;
            that.getTableRows().each(function () {
                const rowWidth = that.getRowCellLength($(this));
                if (rowWidth > maxWidth) {
                    maxWidth = rowWidth;
                }
            });
            return maxWidth;
        }

        getRowCellLength($row) {
            let width = 0;
            $row.children('td, th').each(function () {
                width += $(this).colspan();
            });
            return width;
        }

        generateTableMatrix() {
            const that = this;
            const width = that.getTableMaxCellLength();
            const height = that.getTableRows().length;
            return that.generateMatrix(width, height);
        }

        generateMatrix(width, height) {
            const matrix = [];
            for (let i = 0; i < height; i++) {
                matrix.push(new Array(width));
            }
            return matrix;
        }

        indexTable() {
            let i, j;
            let colspan, rowspan;
            const that = this;
            const rows = that.getTableRows();
            const tableIndex = that.generateTableMatrix();

            // Iterate through each hypothetical table row.
            $.each(tableIndex, function (y) {
                // Note that columns.length <= table width
                const row = rows.eq(y);
                const columns = row.children();
                let cellIndex = 0;

                // Iterate through each hypothetical table row column.
                // $.each will make a copy of the array before iterating.
                // Must use live array reference.
                $.each(tableIndex[y], function (x) {
                    let cell = tableIndex[y][x];
                    // Table matrix is iterated left to right, top to bottom.
                    // It might be that cell has been assigned a value already
                    // because previous row-cell had a 'rowspan' property,
                    // possibly together with 'colspan'.
                    if (!cell) {
                        cell = columns.eq(cellIndex++);
                        colspan = cell.colspan();
                        rowspan = cell.rowspan();

                        for (i = 0; i < rowspan; i++) {
                            for (j = 0; j < colspan; j++) {
                                tableIndex[y + i][x + j] = cell[0];
                            }
                        }
                    }

                    if (cell.data && that.isUndefined(cell.data('cellHighlight.offsetInMatrix'))) {
                        cell.data('cellHighlight.offsetInMatrix', [x, y]);
                    }
                });
            });

            return tableIndex;
        }

        isUndefined(value) {
            return typeof value === 'undefined';
        }
    };

    // -----------------------------
    // CellHighlight default options
    // -----------------------------
    CellHighlight.DEFAULTS = {
        rowSelector: 'tr',
        cellSelector: 'td, th',
        highlightVertical: null,
        highlightHorizontal: null
    };

    /**
     * The plugin name.
     */
    CellHighlight.NAME = 'cell-highlight';

    // -------------------------------
    // CellHighlight plugin definition
    // -------------------------------
    const oldCellHighlight = $.fn.cellhighlight;
    $.fn.cellhighlight = function (options) { // jslint ignore:line
        return this.each(function () {
            const $this = $(this);
            if (!$this.data(CellHighlight.NAME)) {
                const settings = typeof options === 'object' && options;
                $this.data(CellHighlight.NAME, new CellHighlight(this, settings));
            }
        });
    };
    $.fn.cellhighlight.Constructor = CellHighlight;

    // ------------------------------------
    // CellHighlight no conflict
    // ------------------------------------
    $.fn.cellhighlight.noConflict = function () {
        $.fn.cellhighlight = oldCellHighlight;
        return this;
    };

}(jQuery));
