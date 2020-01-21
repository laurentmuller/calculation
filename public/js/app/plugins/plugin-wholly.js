/**! compression tag for ftp-deployment */

/**
 * @version 2.0.0
 * @link https://github.com/gajus/wholly for the canonical source repository
 */
(function ($) {
    'use strict';
    
    // ------------------------------------
    // JQuery extensions
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
    // Wholly public class definition
    // ------------------------------------
    var Wholly = function (element, options) {
        this.$element = $(element);
        this.options = $.extend(true, {}, Wholly.DEFAULTS, options);
        this.tableIndex = this.indexTable();
        this.enabled = false;
        
        // bind events
        const enabled = this.options.enabled === undefined ? true : this.options.enabled;
        if (enabled ) {
            this.enable();    
        }
    };

    
    Wholly.DEFAULTS = {
        rowSelector: 'tr',
        cellSelector: 'td, th',
        highlightVertical: null,
        highlightHorizontal: null        
    };
    
    Wholly.prototype = {
        // -----------------------------
        // public functions
        // -----------------------------
        constructor: Wholly,
            
        enable: function () {
            if (!this.enabled) {
                const selector = this.options.cellSelector;
                this.$element.on('mouseenter', selector, $.proxy(this.mouseenter, this));
                this.$element.on('mouseleave', selector, $.proxy(this.mouseleave, this));
                this.enabled = true;
            }
        },
        
        disable: function () {
            if (this.enabled) {
                const selector = this.options.cellSelector;
                this.$element.off('mouseenter', selector, $.proxy(this.mouseenter, this));
                this.$element.off('mouseleave', selector, $.proxy(this.mouseleave, this));
                this.enabled = false;
            }
        },
        
        destroy: function () {
            this.disable();
            this.$element.removeData("wholly");
        },

        // -----------------------------
        // private functions
        // -----------------------------
        mouseenter: function (e) {
            const that = this;
            const $target = $(e.currentTarget);
            const rowspan = $target.rowspan();
            const colspan = $target.colspan();
            const offsetInMatrix = $target.data('wholly.offsetInMatrix');
            const tableIndex = that.tableIndex;
            const options = that.options;

            // add horizontal cells
            that.horizontal = $([]);
            $.each(tableIndex.slice(offsetInMatrix[1], offsetInMatrix[1] + rowspan), function (n, cell) {
                that.horizontal = that.horizontal.add(cell);
            });

            // add vertical cells
            that.vertical = $([]);
            $.each(tableIndex, function (n, rowIndex) {
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
            that.horizontal.trigger('wholly.mouseenter-horizontal');
            that.vertical.trigger('wholly.mouseenter-vertical');
            
            that.$element.trigger('wholly.mouseenter', {
                horizontal: that.horizontal,
                vertical: that.vertical
            });
        },

        mouseleave: function () {
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
            that.horizontal.trigger('wholly.mouseleave-horizontal');
            that.vertical.trigger('wholly.mouseleave-vertical');
            
            that.$element.trigger('wholly.mouseleave', {
                horizontal: that.horizontal,
                vertical: that.vertical
            });

            // clean
            that.horizontal = that.vertical = false;
        },

        getTableRows: function() {
            const selector = this.options.rowSelector;
            return this.$element.find(selector);
        },
        
        getTableMaxCellLength: function () {
            let maxWidth = 0;
            const that  = this;            
            that.getTableRows().each(function () {
                const rowWidth = that.getRowCellLength($(this));
                if (rowWidth > maxWidth) {
                    maxWidth = rowWidth;
                }
            });
            return maxWidth;
        },

        getRowCellLength: function ($row) {
            let width = 0;
            $row.children('td, th').each(function () {
                width += $(this).colspan();
            });
            return width;
        },

        generateTableMatrix: function () {
            const width = this.getTableMaxCellLength();
            const height = this.getTableRows().length;
            return this.generateMatrix(width, height);
        },

        generateMatrix: function (width, height) {
            let matrix = [];
            while (height--) {
                matrix.push(new Array(width));
            }
            return matrix;
        },

        indexTable: function () {
            let i, j;
            let colspan, rowspan;
            
            const rows = this.getTableRows();
            let tableIndex = this.generateTableMatrix();            

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
                    // because previous row-cell had a "rowspan" property,
                    // possibly together with "colspan".
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

                    if (cell.data && cell.data('wholly.offsetInMatrix') === undefined) {
                        cell.data('wholly.offsetInMatrix', [x, y]);
                    }
                });
            });

            return tableIndex;
        }
    };

    // -----------------------------
    // Wholly plugin definition
    // -----------------------------
    const oldWholly = $.fn.wholly;
    
    $.fn.wholly = function (option) {
        return this.each(function () {
            const $this = $(this);
            let data = $this.data("wholly");
            const options = typeof option === "object" && option;
            if (!data) {
                $this.data("wholly", data = new Wholly(this, options));
            }
        });
    };
    
    $.fn.wholly.Constructor = Wholly;

    // ------------------------------------
    // Wholly no conflict
    // ------------------------------------
    $.fn.wholly.noConflict = function () {
        $.fn.wholly = oldWholly;
        return this;
    };
    
}(jQuery));
