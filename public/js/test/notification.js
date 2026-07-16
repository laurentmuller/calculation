/* globals Toaster, THEME */

/**
 * Display a notification.
 *
 * @param {string} type - the message type.
 * @param {string} title - the message title.
 * @param {Object} options - the custom options.
 */
function notify(type, title, options) {
    'use strict';
    // get random text
    const $position = $('#position');
    const url = $position.data('random');
    const data = {
        'maxNbChars': $('#maxNbChars').intVal()
    };
    $.getJSON(url, data, function (response) {
        if (response.result) {
            const message = '<p class="m-0 p-0">{0}</p>'.format(response.content);
            Toaster.notify(type, message, title, options);
        } else {
            const message = $position.data('failure');
            Toaster.danger(message, $('.card-title').text(), options);
        }
    }).fail(function () {
        const message = $position.data('failure');
        Toaster.danger(message, $('.card-title').text(), options);
    });
}

/**
 * Display a random notification.
 */
function random() {
    'use strict';
    const button = $('.btn-notify').toArray().randomElement();
    $(button).trigger('click');
}

/**
 * Gets a value indicating if start view transition is supported.
 * @return {boolean} true if supported.
 */
function supportTransition() {
    'use strict';
    return document.startViewTransition && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/**
 * Gets the preferred theme.
 * @return {string} the preferred theme.
 */
function getPreferredTheme() {
    'use strict';
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? THEME.DARK : THEME.LIGHT;
}

/**
 * Handle theme input change.
 */
function initThemeInput() {
    'use strict';
    $('input[name=radio-theme]').on('change', function () {
        const path = document.body.dataset.cookiePath || '/';
        const oldTheme = window.Cookie.getValue(THEME.KEY, THEME.AUTO);
        const $selection = $('input[name=radio-theme]:checked');
        let theme = $selection.val();
        if (theme === THEME.AUTO) {
            theme = getPreferredTheme();
            window.Cookie.clearValue(THEME.KEY, path);
        } else {
            window.Cookie.setValue(THEME.KEY, theme, path);
        }
        if (oldTheme !== theme) {
            const callback = () => {
                document.documentElement.setAttribute(THEME.ATTRIBUTE, theme);
            };
            if (supportTransition()) {
                document.startViewTransition(callback);
            } else {
                callback();
            }
        }
        const title = $('button.rounded-end-pill').data('title');
        const message = $selection.data('success');
        Toaster.success(message, title);
    });
}

/**
 * Handle theme changed event.
 */
function initThemeChanged() {
    'use strict';
    $('body').on(THEME.EVENT, '.theme-switcher', function (e, theme) {
        $('input[name=radio-theme]').each(function () {
            const $this = $(this);
            $this.setChecked($this.val() === theme);
        });
    });
}

/**
 * Document ready function
 */
$(function () {
    'use strict';
    // default options
    const options = {
        dataset: '#flashes',
        onHide: function (settings) {
            window.console.log(JSON.stringify(settings, null, 4));
        }
    };

    // handle notify button click
    $('.btn-notify').on('click', function () {
        // update options
        options.icon = $('#icon').isChecked();
        options.title = $('#title').isChecked();
        options.position = $('#position').val();
        options.timeout = $('#timeout').intVal();
        options.progress = $('#progress').intVal();
        options.autohide = $('#autohide').isChecked();
        options.displayClose = $('#close').isChecked();
        options.displaySubtitle = $('#subtitle').isChecked();

        // notify
        const type = $(this).data('type');
        const title = options.title ? $(this).text() : null;
        notify(type, title, options);

        // update class
        const attribute = 'form-check form-check-inline form-switch form-check-option form-' + type;
        $('.form-check-option').attr('class', attribute);
    });

    // set default values
    $('.btn-default').on('click', function () {
        let changed = false;
        $('.card-body [data-default]').each(function () {
            const $this = $(this);
            const value = $this.data('default');
            if ($this.is(':checkbox')) {
                if ($this.isChecked() !== value) {
                    $this.setChecked(value);
                    changed = true;
                }
            } else {
                if ($this.val() !== String(value)) {
                    $this.val(value);
                    changed = true;
                }
            }
        });
        if (changed) {
            random();
        }
    });

    // display a notification when a value changes
    $('#position, #timeout, #progress, #maxNbChars, .form-check-input-option').on('input', function () {
        random();
        const $this = $(this);
        if ($this.is(('#autohide'))) {
            $('#close').toggleDisabled(!$this.isChecked());
        }
    });

    // popover
    $('button[data-type][data-bs-toggle="popover"]').popover();

    // first notification
    random();

    // theme
    initThemeInput();
    initThemeChanged();
});
