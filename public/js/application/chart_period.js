/**
 * Update the number of months displayed.
 */
const updateMonths = () => {
    'use strict';
    const fromIndex = $('#month_range_from option:selected').index()
    const toIndex = $('#month_range_to option:selected').index();
    const months = fromIndex - toIndex + 1;
    $('#months').text($('#edit-form').data('months').replace('0', months));
}

/**
 * Ready function
 */
$(function () {
    'use strict';

    // validation
    const $form = $('#edit-form');
    $form.initValidator({
        showModification: false,
        rules: {
            'month_range[to]': {
                greaterThan: '#month_range_from'
            }
        },
        messages: {
            'month_range[to]': $form.data('error')
        },
    });

    // update inputs
    const $from = $('#month_range_from');
    const $to = $('#month_range_to');
    $from.on('input', function () {
        const fromDate = Date.parse($from.val());
        const toDate = Date.parse($to.val());
        if (fromDate < toDate) {
            updateMonths();
            return;
        }
        $($to.find('option').get().reverse()).each(function () {
            const $option = $(this);
            const value = $option.val();
            const date = Date.parse(value);
            if (date >= fromDate) {
                $to.val(value);
                updateMonths();
                return false;
            }
        });
        updateMonths();
    });
    $to.on('input', function () {
        const fromDate = Date.parse($from.val());
        const toDate = Date.parse($to.val());
        if (fromDate < toDate) {
            updateMonths();
            return;
        }
        $from.find('option').each(function () {
            const $option = $(this);
            const value = $option.val();
            const date = Date.parse(value);
            if (date <= toDate) {
                $from.val(value);
                updateMonths();
                return false;
            }
        });
        updateMonths();
    });

    // set to last 6 months
    $('.btn-default').on('click', function () {
        const $first = $to.find('option:first');
        $to.val($first.val())
        const $last = $from.find('option:lt(6):last');
        $from.val($last.val());
        updateMonths();
    });

    updateMonths();
});
