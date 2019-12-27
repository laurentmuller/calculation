/**! compression tag for ftp-deployment */

/* globals triggerClick */

/**
 * Ready function
 */
$(function () {
    'use strict';

    $.contextMenu({
        selector: 'tr.selection',
        callback: function (key, opt, e) {
            triggerClick(e, '.' + key);
        },
        events: {
            show: function (options) {
                const items = options.items;
                console.log(items);
                //$.each(items, function (key, item) {
                    // console.log(item);
                    // $(item.$node).addClass('context-menu-disabled');
                //});
            }
        },
        items: {
            'btn-table-show': {
                name: 'Afficher',
                icon: 'fas fa-tv fa-fw'
            },
            "btn-table-edit": {
                name: "Modifier",
                icon: "fas fa-pencil-alt fa-fw"
            },
            "btn-table-delete": {
                name: "Supprimer",
                icon: "fas fa-times fa-fw"
            },
            separator: "-----",
            "btn-table-add": {
                name: "Nouveau",
                icon: "far fa-file fa-fw"
            }
        },
        classNames: {
            hover: 'bg-primary text-white'
        }
    });
});