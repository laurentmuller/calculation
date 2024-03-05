/**! compression tag for ftp-deployment */

/* globals Toaster, mermaid */

(() => {
    'use strict';

    const DIAGRAM_SELECTOR = '.mermaid';
    const DATA_PROCESSED = 'data-processed';
    const DATA_CODE = 'data-code';

    const THEME_COOKIE_KEY = 'THEME';
    const THEME_CHANNEL = 'theme';
    const THEME_EVENT_NAME = 'theme_changed';
    const THEME_LIGHT = 'light';

    const CLASS_REGEX = /classId-(.*)-\d+/;

    /**
     * Handle the node click.
     * @param {string} nodeId - the node identifier like: "classId-Category-0".
     */
    window.nodeCallback = (nodeId) => {
        const result = CLASS_REGEX.exec(nodeId);
        if (!result || result.length < 2) {
            showError(`Unable to find a corresponding diagram for the node "${nodeId}".`);
            return;
        }

        const className = result[1].replace(/([a-z])([A-Z])/, '$1_$2');
        // window.console.log('Found class: ' + className);
        let found = false;
        $('#diagrams option').each(function () {
            const value = $(this).val();
            if (className.equalsIgnoreCase(value)) {
                $('#diagrams').val(value).trigger('change');
                found = true;
                return false;
            }
        });
        if (!found) {
            showError(`Unable to find a corresponding diagram for the node "${nodeId}".`);
        }
    };


    /**
     * Gets the selected theme (light or dark).
     * @return {string}
     */
    const getTheme = () => window.Cookie.getValue(THEME_COOKIE_KEY, THEME_LIGHT);

    /**
     * Gets the diagram elements.
     *
     * @return {NodeListOf<Element>}
     */
    const getElements = () => document.querySelectorAll(DIAGRAM_SELECTOR);

    /**
     * Gets the diagram options.
     * @return {{theme: string, securityLevel: string}}
     */
    const getOptions = function () {
        return {
            theme: getTheme(), securityLevel: 'loose'
        };
    };

    /**
     * Show an error message.
     * @param {string} message
     */
    const showError = (message) => {
        const title = $('.card-title').text();
        Toaster.danger(message, title);
    };

    /**
     * Reload diagrams.
     */
    const reloadDiagrams = function () {
        const options = getOptions();
        mermaid.initialize(options);
        mermaid.init(options, getElements());
    };

    /**
     * Save inner content of diagrams.
     */
    const saveDiagrams = () => {
        getElements().forEach((element) => {
            element.setAttribute(DATA_CODE, element.innerHTML);
        });
    };

    /**
     * Reset processed diagrams.
     */
    const resetDiagrams = () => {
        getElements().forEach(element => {
            const code = element.getAttribute(DATA_CODE);
            if (code !== null) {
                element.removeAttribute(DATA_PROCESSED);
                element.innerHTML = code;
            }
        });
    };

    /**
     * Create and handle theme channel.
     */
    const channel = new window.BroadcastChannel(THEME_CHANNEL);
    channel.addEventListener('message', (e) => {
        if (e.data === THEME_EVENT_NAME) {
            resetDiagrams();
            reloadDiagrams();
        }
    });

    /**
     * Handle diagram selection.
     */
    $('#diagrams').on('change', function () {
        const url = $('#diagram').data('url');
        if (!url) {
            showError('Unable to find the corresponding diagram (URL not defined).');
            return;
        }
        const data = {
            'name': $(this).val()
        };
        $.getJSON(url, data, function (response) {
            if (!response.result) {
                showError(response.message);
                return;
            }
            $('#diagram').text(response.file.content);
            saveDiagrams();
            resetDiagrams();
            reloadDiagrams();

            const name = response.file.name;
            const url = `?name=${name}`;
            window.history.pushState({'name': name}, null, url);
            // location.replace(`https://example.com/#${location.pathname}`);
            // window.history.replaceState({'name': response.file.name}, null, url);
        });
    }).trigger('focus');

    /**
     * Handle history pop state.
     */
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.name) {
            const name = e.state.name;
            $('#diagrams').val(name).trigger('change');
        }
    });

    // save and initialize diagrams.
    saveDiagrams();
    mermaid.initialize(getOptions());
})();
