/**! compression tag for ftp-deployment */

/* globals Toaster, mermaid, svgPanZoom */

/**
 * @typedef {Object} ShadowViewport
 * @property {function} destroy
 */

/**
 * @typedef {Object} SvgPoint
 * @property {number} x
 * @property {number} y
 */

/**
 * @typedef {Object} SvgViewBox
 * @property {number} x
 * @property {number} y
 * @property {number} width
 * @property {number} height
 */

/**
 * @typedef {Object} SvgSizes
 * @property {number} width
 * @property {number} height
 * @property {number} realZoom
 * @property {SvgViewBox} viewBox
 */

(() => {
    'use strict';

    const DATA_CODE = 'data-code';
    const DATA_PROCESSED = 'data-processed';
    const DIAGRAM_SELECTOR = '#diagram';

    const THEME_COOKIE_KEY = 'THEME';
    const THEME_CHANNEL = 'theme';
    const THEME_EVENT_NAME = 'theme_changed';
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';

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
    const $zoom = $('#zoom');
    /** @type {ShadowViewport|null} */
    let panZoom = null;

    /**
     * Show an error message.
     * @param {string} message
     */
    const showError = (message) => {
        Toaster.danger(message, $('.card-title').text());
    };

    /**
     * Handle the diagram node click.
     * @param {string} nodeId - the node identifier like: "classId-Category-0".
     */
    window.nodeCallback = (nodeId) => {
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
        if (THEME_DARK === getTheme()) {
            return {
                primaryColor: '#DEE2E608',
                primaryTextColor: '#FFF',
                primaryBorderColor: '#6C757D',
                lineColor: '#6C757D',
                secondaryColor: '#006100',
                tertiaryColor: '#FFF'
            };
        }
        return {
            primaryColor: '#21252908',
            primaryTextColor: '#000',
            primaryBorderColor: '#6C757D',
            lineColor: '#6C757D',
            secondaryColor: '#006100',
            tertiaryColor: '#FFF'
        };
    };

    /**
     * @param {number} value
     */
    const formatZoom = (value) => {
        const text = new Intl.NumberFormat('default', {
            style: 'percent',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
        $zoom.text(text);
    };

    /** @return {SVGSVGElement} */
    const getDiagramSVG = () => $diagram.find('svg:first')[0];

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


    /** @param {number} zoom */
    const zoomHandler = function (zoom) {
        // const svg = getDiagramSVG();
        // const sizes = this.getSizes();
        // if (zoom > 1.0) {
        //     svg.style.height = String(sizes.height * zoom);
        // } else {
        //     svg.style.height = String(sizes.height);
        // }
        formatZoom(zoom);
    };

    /**
     * @param  {SvgPoint} oldPane
     * @param  {SvgPoint} newPan
     * @return {SvgPoint|null}
     */
    const beforePanHandler = function (oldPane, newPan) {
        const margin = 100;
        const svg = getDiagramSVG();
        /** @type {SvgSizes} */
        const sizes = this.getSizes();
        const width = Math.max(sizes.width, svg.clientWidth);
        const height = Math.max(sizes.height, svg.clientHeight);
        const left = -((sizes.viewBox.x + sizes.viewBox.width) * sizes.realZoom) + margin;
        const right = width - margin - (sizes.viewBox.x * sizes.realZoom);
        const top = -((sizes.viewBox.y + height) * sizes.realZoom) + margin;
        const bottom = height - margin - (sizes.viewBox.y * sizes.realZoom);

        return {
            x: Math.max(left, Math.min(right, newPan.x)),
            y: Math.max(top, Math.min(bottom, newPan.y))
        };
    };

    /**
     * @return {ShadowViewport}
     */
    const createSvgPanZoom = () => {
        const svg = getDiagramSVG();
        const panZoom = svgPanZoom(svg, {
            onZoom: zoomHandler, beforePan: beforePanHandler,
        });
        /** @type {SvgSizes} */
        const sizes = panZoom.getSizes();
        formatZoom(sizes.realZoom);
        //svg.style.height = String(sizes.height);
        svg.style.height = '100%';
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
    const loadDiagram = () => {
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
            panZoom = createSvgPanZoom();
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
            loadDiagram();

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

    window.addEventListener('DOMContentLoaded', () => {
        // create and handle the theme channel.
        const channel = new window.BroadcastChannel(THEME_CHANNEL);
        channel.addEventListener('message', (e) => {
            if (e.data === THEME_EVENT_NAME) {
                resetDiagram();
                loadDiagram();
            }
        });
    });

    // save and initialize diagrams.
    saveDiagram();
    loadDiagram();
})();
