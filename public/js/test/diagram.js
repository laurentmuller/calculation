/**! compression tag for ftp-deployment */

/* globals Toaster, mermaid, svgPanZoom */

(() => {
    'use strict';

    const DATA_CODE = 'data-code';
    const DATA_PROCESSED = 'data-processed';
    const DIAGRAM_SELECTOR = '#diagram';

    const THEME_COOKIE_KEY = 'THEME';
    const THEME_CHANNEL = 'theme';
    const THEME_EVENT_NAME = 'theme_changed';
    const THEME_LIGHT = 'light';

    const CLASS_REGEX = /classId-(.*)-\d+/;
    const REPLACE_REGEX = /([a-z])([A-Z])/g;
    const REPLACE_TARGET = '$1_$2';

    /**
     * The diagram renderer.
     * @var {jQuery<HTMLPreElement>}
     */
    const $diagram = $(DIAGRAM_SELECTOR);

    /**
     * The diagrams list.
     * @var {jQuery<HTMLSelectElement>}
     */
    const $diagrams = $('#diagrams');

    // Gets the selected theme (light or dark).
    const getTheme = () => window.Cookie.getValue(THEME_COOKIE_KEY, THEME_LIGHT);

    // buttons
    const $zoomIn = $('.btn-zoom-in');
    const $zoomOut = $('.btn-zoom-out');
    const $center = $('.btn-center');

    /**
     * Show an error message.
     * @param {string} message
     */
    const showError = (message) => {
        const title = $('.card-title').text();
        Toaster.danger(message, title);
    };

    /**
     * Handle the diagram node click.
     * @param {string} nodeId - the node identifier like: "classId-Category-0".
     */
    window.nodeCallback = (nodeId) => {
        // window.console.log('nodeCallback');
        // if (window.isMouseDown) {
        //     return;
        // }
        let found = false;
        const result = CLASS_REGEX.exec(nodeId);
        if (result && result.length >= 2) {
            const className = result[1].replace(REPLACE_REGEX, REPLACE_TARGET);
            $diagrams.find('option').each(function () {
                const value = $(this).val();
                if (className.equalsIgnoreCase(value)) {
                    $diagrams.val(value).trigger('change');
                    found = true;
                    return false;
                }
            });
        }
        if (!found) {
            showError(`Unable to find a corresponding diagram for the node "${nodeId}".`);
        }
    };

    // Gets the color variables, depending on the selected theme.
    const getThemeVariables = () => {
        if (THEME_LIGHT === getTheme()) {
            return {
                primaryColor: '#21252908',
                primaryTextColor: '#000',
                primaryBorderColor: '#6C757D',
                lineColor: '#6C757D',
                secondaryColor: '#006100',
                tertiaryColor: '#FFF'
            };
        }
        return {
            primaryColor: '#DEE2E608',
            primaryTextColor: '#FFF',
            primaryBorderColor: '#6C757D',
            lineColor: '#6C757D',
            secondaryColor: '#006100',
            tertiaryColor: '#FFF'
        };
    };

    /**
     * @param {number} value
     * @return {string}
     */
    const formatPercent = (value) => {
        return new Intl.NumberFormat('default', {
            style: 'percent',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value);
    };

    let panZoom = null;

    /**
     * @param {ShadowViewport} [panZoom]
     * @return {null}
     */
    const destroySvgPanZoom = (panZoom) => {
        if (panZoom) {
            $center.off('click');
            $zoomIn.off('click');
            $zoomOut.off('click');
            panZoom.destroy();
        }
        return null;
    };

    /**
     * @return {ShadowViewport}
     */
    const initSvgPanZoom = () => {
        const svg = $diagram.find('svg:first')[0];
        /** @param {number} zoom */
        const zoomHandler = function (zoom) {
            const sizes = this.getSizes();
            if (zoom > 1.0) {
                svg.style.height = sizes.height * zoom * 1.1;
            } else {
                svg.style.height = sizes.height;
            }
            $('#zoom').text(formatPercent(zoom));
        };
        const panZoom = svgPanZoom(svg, {
            onZoom: zoomHandler,
        });
        const sizes = panZoom.getSizes();
        svg.style.height = sizes.height;
        svg.style.maxWidth = '100%';
        svg.style.width = '100%';

        $zoomOut.on('click', () => panZoom.zoomOut());
        $zoomIn.on('click', () => panZoom.zoomIn());
        $center.on('click', () => {
            panZoom.reset();
            panZoom.center();
        });

        return panZoom;
    };

    // load the diagram.
    const reloadDiagram = () => {
        mermaid.initialize({
            theme: 'base',
            useMaxWidth: false,
            startOnLoad: false,
            securityLevel: 'loose',
            themeVariables: getThemeVariables()
        });
        mermaid.run({
            nodes: [$diagram[0]],
        }).then(() => {
            panZoom = destroySvgPanZoom(panZoom);
            panZoom = initSvgPanZoom();
        });
    };

    // Save the HTML content of the diagram.
    const saveDiagram = () => {
        $diagram.attr(DATA_CODE, $diagram.html());
    };

    // Reset processed diagram.
    const resetDiagram = () => {
        const code = $diagram.attr(DATA_CODE);
        if (code) {
            $diagram.attr(DATA_PROCESSED, null);
            $diagram.html(code);
        }
    };

    // Create and handle the theme channel.
    const channel = new window.BroadcastChannel(THEME_CHANNEL);
    channel.addEventListener('message', (e) => {
        if (e.data === THEME_EVENT_NAME) {
            resetDiagram();
            reloadDiagram();
        }
    });

    // Handle diagrams change selection.
    $diagrams.on('change', function () {
        const url = $('#diagram').data('url');
        if (!url) {
            showError('Unable to find the corresponding diagram (URL not defined).');
            return;
        }
        const data = {
            'name': $(this).val()
        };
        $.getJSON(url, data, function (response) {
            // error?
            if (!response.result) {
                showError(response.message);
                return;
            }
            // reload diagram
            $diagram.text(response.file.content);
            saveDiagram();
            resetDiagram();
            reloadDiagram();

            // update history
            const name = response.file.name;
            const url = new URL(location);
            url.searchParams.set('name', name);
            window.history.pushState({'name': name}, '', url);
        });
    }).trigger('focus');

    // Handle history pop state
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.name) {
            const name = e.state.name;
            $diagrams.val(name).trigger('change');
        }
    });

    // save and initialize diagrams.
    saveDiagram();
    reloadDiagram();
})();
