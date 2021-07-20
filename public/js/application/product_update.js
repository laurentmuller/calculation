/**! compression tag for ftp-deployment */

/**
 * Ready function
 */
(function ($) {
    'use strict';

    // bind events
    $('#edit-form :radio').on('click', function () {
        const $type = $('#form_type');
        const $fixed = $('#form_fixed');
        const $percent = $('#form_percent');
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

    $('#products').on('show.bs.modal', function () {
        // filter
        const newId = $('#form_category').val();
        const oldId = $('#form_category').data('id');
        if (newId === oldId) {
            return;
        }
        $('#products tbody tr').each(function () {
            const $this = $(this);
            $this.toggleClass('d-none', newId !== $this.attr('category'));
        });

        // count products
        const $counter = $('#count_products');
        const count = $('#products tbody tr:not(.d-none)').length;
        $counter.text($counter.data('text').replace('%d%', count));
        $('#form_category').data('id', newId);

    }).on('hide.bs.modal', function () {
        $('#products .table-responsive').scrollTop(0);

    }).on('hidden.bs.modal', function () {
        $('#form_category').focus();

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
