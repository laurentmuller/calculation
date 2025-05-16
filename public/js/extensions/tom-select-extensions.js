/**
 * Ready function
 */
$(function () {
    'use strict';

    /**
     * Find the next index for the given character.
     *
     * @param {string} key the character to search for.
     * @param {number} oldIndex the old selection index.
     * @param {number} newIndex the current selection index.
     * @param {number} lastIndex the last option index
     * @param {Array.<string>} options the array of options.
     */
    function findNextIndex(key, oldIndex, newIndex, lastIndex, options) {
        // find from after the index to end
        if (newIndex === -1) {
            for (let i = oldIndex + 1; i <= lastIndex; i++) {
                if (options[i].startsWithIgnoreCase(key)) {
                    newIndex = i;
                    break;
                }
            }
        }
        // find from start to before index
        if (newIndex === -1) {
            for (let i = 0; i < oldIndex; i++) {
                if (options[i].startsWithIgnoreCase(key)) {
                    newIndex = i;
                    break;
                }
            }
        }
        return newIndex;
    }

    /**
     * -------------- jQuery functions extensions --------------
     */
    $.fn.extend({

        /**
         * Update selection on the key press event when selection is not open.
         */
        handleTomSelectKeyDown: function (tomsSelect) {
            const $this = $(this);
            if ($this.hasAttr('multiple')) {
                return;
            }

            tomsSelect.control.addEventListener('keydown', function (e) {
                // console.log(e);
                if (e.ctrlKey || e.altKey || e.shiftKey) {
                    return;
                }

                let newIndex = -1;
                const options = $this.find('option:enabled').toArray();
                const values = options.map(option => option.value);
                const texts = options.map(option => option.text);

                const selectValue = $this.val();
                const oldIndex = values.indexOf(selectValue);
                const lastIndex = values.length - 1;
                console.log('Old selection', selectValue, texts[selectValue]);

                switch (e.key) {
                    case 'PageUp':
                        Math.max(oldIndex - 10, 0);
                        break;
                    case 'PageDown':
                        newIndex = Math.min(oldIndex + 10, lastIndex);
                        break;
                    case 'End':
                        newIndex = lastIndex;
                        break;
                    case 'Home':
                        newIndex = 0;
                        break;
                    case 'ArrowLeft':
                    case 'ArrowUp':
                        //newIndex = tomsSelect.getAdjacent(tomsSelect.activeOption, -1);
                        newIndex = Math.max(oldIndex - 1, 0);
                        break;
                    // case 'ArrowDown':
                    case 'ArrowRight':
                        newIndex = Math.min(oldIndex + 1, lastIndex);
                        break;
                    default:
                        newIndex = findNextIndex(e.key, oldIndex, newIndex, lastIndex, texts);
                        break;
                }
                if (newIndex < 0 || newIndex > lastIndex || newIndex === oldIndex) {
                    return;
                }

                e.preventDefault();
                const value = values[newIndex];
                console.log('New selection', value, texts[newIndex]);
                // $this.val(value).trigger('change').trigger('input');
                tomsSelect.setValue(value, true);
            });
        },

        /**
         * Initialize tom select
         *
         * @param {Object} options
         *
         * @return {TomSelect}
         */
        initTomSelect: function (options) {
            const settings = $.extend(true, {
                maxOptions: null,
                plugins: ['dropdown_input'],
                // openOnFocus: false,
                onDropdownOpen: function () {
                    if (this.activeOption) {
                        this.activeOption.scrollIntoView({
                            behavior: 'instant'
                        });
                    }
                },
                // onFocus: function () {
                //     console.log('onFocus');
                // },
                // onBlur: function () {
                //     console.log('onBlur');
                // }
            }, options);

            const tomSelect = new TomSelect(this, settings);
            $(this).handleTomSelectKeyDown(tomSelect);

            return tomSelect;
        }
    });
});
