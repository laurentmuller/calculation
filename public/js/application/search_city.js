/**
 * Ready function
 */
$(function () {
    'use strict';
    // submit form when units change
    const $form = $("#edit-form");
    const $unit = $('#form_units');
    const $query = $('#form_query');
    $unit.data('value', $unit.val()).on('input', function () {
        $unit.createTimer(function () {
            $unit.removeTimer();
            const newValue = $unit.val();
            const oldValue = $unit.data('value');
            if (newValue !== oldValue && String($query.val()).trim().length >= 2) {
                $form.trigger('submit');
            }
        }, 500);
    });

    // validation
    $form.initValidator();
});
