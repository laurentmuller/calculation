/**! compression tag for ftp-deployment */

/* globals mermaid */

(() => {
    'use strict';

    // window.callback = () => {
    //     window.console.log('callback');
    //     //window.console.log(arguments);
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

    const loadMermaid = function () {
        const theme = getTheme();
        mermaid.initialize({theme});
        mermaid.init({theme}, getElements());
    };

    const saveOriginalData = () => {
        getElements().forEach(element => {
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
    const theme = getTheme();
    mermaid.initialize({
        securityLevel: 'loose',
        theme: theme
    });

    $('#diagrams').on('change', function () {
        const $this = $(this);
        const value = $this.val();
        const search = `name=${value}`;
        if (window.location.search !== search) {
            location.search = search;
        }
    });

})();
