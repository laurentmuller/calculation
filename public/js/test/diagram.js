/**! compression tag for ftp-deployment */

/* globals Toaster, mermaid */

(() => {
    'use strict';

    // window.callback = () => {
    //    window.console.log('callback');
    // };

    const MERMAID_SELECTOR = '.mermaid';
    const DATA_PROCESSED = 'data-processed';
    const DATA_CODE = 'data-code';

    const THEME_COOKIE_KEY = 'THEME';
    const THEME_CHANNEL = 'theme';
    const THEME_EVENT_NAME = 'theme_changed';
    const THEME_LIGHT = 'light';

    const getTheme = () => window.Cookie.getValue(THEME_COOKIE_KEY, THEME_LIGHT);
    const getElements = () => document.querySelectorAll(MERMAID_SELECTOR);
    const getOptions = function () {
        return {
            theme: getTheme()
        };
    };

    const loadMermaid = function () {
        const options = getOptions();
        mermaid.initialize(options);
        mermaid.init(options, getElements());
    };

    const saveOriginalData = () => {
        getElements().forEach((element) => {
            element.setAttribute(DATA_CODE, element.innerHTML);
        });
    };

    const resetProcessed = () => {
        getElements().forEach(element => {
            const code = element.getAttribute(DATA_CODE);
            if (code !== null) {
                element.removeAttribute(DATA_PROCESSED);
                element.innerHTML = code;
            }
        });
    };

    const channel = new window.BroadcastChannel(THEME_CHANNEL);
    channel.addEventListener('message', (e) => {
        if (e.data === THEME_EVENT_NAME) {
            resetProcessed();
            loadMermaid();
        }
    });

    saveOriginalData();
    mermaid.initialize(getOptions());

    $('#diagrams').on('change', function () {
        const url = $('#diagram').data('url');
        if (!url) {
            const title = $('.card-title').text();
            Toaster.danger('Unable to find the corresponding diagram (URL not defined).', title);
            return;
        }
        const data = {
            'name': $(this).val()
        };
        $.getJSON(url, data, function (response) {
            if (!response.result) {
                const title = $('.card-title').text();
                Toaster.danger(response.message, title);
                return;
            }
            $('#diagram').text(response.file.content);
            saveOriginalData();
            resetProcessed();
            loadMermaid();

            const url = `?name=${response.file.name}`;
            //window.history.pushState({}, null, url);
            window.history.replaceState({}, null, url);
        });
    }).trigger('focus');

})();
