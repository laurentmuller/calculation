/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    const $type = $('#form_type');
    const $fixed = $('#form_fixed');
    const $percent = $('#form_percent');
    $('#edit-form :radio').on('click', function () {
        const isPercent = $('#form_type_percent').isChecked();

        $fixed.attr('disabled', isPercent);
        $percent.attr('disabled', !isPercent);

        if (isPercent) {
            $fixed.removeValidation();
            $type.val($percent.data('type'));
        } else {
            $percent.removeValidation();
            $type.val($fixed.data('type'));
        }
    });

    $('#form_simulated').on('input', function () {
        if ($(this).isChecked()) {
            $('#form_confirm').attr('disabled', true).removeValidation();
        } else {
            $('#form_confirm').attr('disabled', false);
        }
    });

    const $category = $('#form_category');
    const $counter = $('#count_products');
    const $rows = $('#products tbody tr');
    $('#products').on('show.bs.modal', function () {
        // same category?
        const newId = $category.val();
        const oldId = $category.data('id');
        if (newId === oldId) {
            return;
        }

        // toggle visibility
        $rows.filter(':not(.d-none)').addClass('d-none');
        const count = $rows.filter('[category="' + newId + '"]').removeClass('d-none').length;

        // update count products
        $counter.text($counter.data('text').replace('%d%', count));
        $category.data('id', newId);

    }).on('hide.bs.modal', function () {
        $('#products .table-responsive').scrollTop(0);
    }).on('hidden.bs.modal', function () {
        $category.focus();
    });

    // validation
    $('#edit-form').initValidator({
        rules: {
            'form[percent]': {
                notEqualToZero: true
            },
            'form[fixed]': {
                notEqualToZero: true
            }
        }
    });
}(jQuery));
