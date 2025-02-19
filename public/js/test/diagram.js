/* globals Toaster, mermaid, Panzoom */

(() => {
    'use strict';

    const THEME_DARK = 'dark';
    const THEME_ATTRIBUTE = 'data-bs-theme';
    const DATA_PROCESSED = 'data-processed';
    const CLASS_REGEX = /classId-(.*)-\d+/;
    const REPLACE_REGEX = /([a-z])([A-Z])/g;
    const REPLACE_TARGET = '$1_$2';
    const MIN_SCALE = 0.5;
    const MAX_SCALE = 2.5;

    /**
     * The diagram renderer.
     * @type {HTMLDivElement}
     */
    const diagram = document.getElementById('diagram');

    /**
     * The diagrams list.
     * @type {HTMLSelectElement}
     */
    const diagrams = document.getElementById('diagrams');

    /**
     * The root node of the document.
     * @type {HTMLElement}
     */
    const rootNode = document.documentElement;

    /**
     * The zoom-out button.
     * @type {HTMLButtonElement}
     */
    const zoomOut = document.querySelector('.btn-zoom-out');

    /**
     * The zoom-in button
     * @type {HTMLButtonElement}
     */
    const zoomIn = document.querySelector('.btn-zoom-in');

    /**
     * The reset button.
     * @type {HTMLButtonElement}
     */
    const reset = document.querySelector('.btn-reset');

    /**
     * The zoom label.
     * @type {HTMLSpanElement}
     */
    const zoom = document.getElementById('zoom');

    /**
     * The SVG pan zoom.
     */
    let panzoom = null;

    /**
     * Gets SVG element.
     * @return {SVGSVGElement}
     */
    const getSvgDiagram = () => document.querySelector('#diagram svg');

    /**
     * Returns if the dark theme is selected.
     * @return {boolean}
     */
    const isDarkTheme = () => rootNode.getAttribute(THEME_ATTRIBUTE) === THEME_DARK;

    /**
     * The number to format zoom.
     */
    const zoomFormatter = new Intl.NumberFormat('default', {
        style: 'percent',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });

    /**
     * Show an error message.
     * @param {string} message
     */
    const showError = (message) => {
        const title = document.querySelector('.card-title').textContent;
        Toaster.danger(message, title);
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
            for (const option of diagrams.options) {
                if (className.equalsIgnoreCase(option.value)) {
                    diagrams.value = option.value;
                    diagrams.dispatchEvent(new Event('change'));
                    found = true;
                    break;
                }
            }
        }
        if (!found) {
            showError(diagram.dataset.error.replace('%name%', nodeId));
        }
    };

    /**
     * Gets the color variables, depending on the selected theme.
     * @return {object}
     */
    const getThemeVariables = () => {
        if (isDarkTheme()) {
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
     * @param {HTMLButtonElement}  button
     * @param {boolean} disabled
     */
    const updateState = function (button, disabled) {
        if (disabled) {
            button.setAttribute('disabled', 'disabled');
        } else {
            button.removeAttribute('disabled');
        }
    };

    /**
     * Handle the pan zoom change event.
     */
    const zoomHandler = function (event) {
        const scale = event.detail.scale;
        updateState(zoomIn, scale >= MAX_SCALE);
        updateState(zoomOut, scale <= MIN_SCALE);
        updateState(reset, scale === 1 && event.detail.x === 0 && event.detail.y === 0);
        zoom.textContent = zoomFormatter.format(scale);
    };

    /**
     * Destroy the SVG pan zoom.
     */
    const destroyPanzoom = (panzoom) => {
        if (panzoom) {
            const svgDiagram = getSvgDiagram();
            svgDiagram.parentElement.removeEventListener('wheel', panzoom.zoomWithWheel);
            svgDiagram.removeEventListener('panzoomchange', zoomHandler);
            zoomOut.removeEventListener('click', panzoom.zoomOut);
            zoomIn.removeEventListener('click', panzoom.zoomIn);
            reset.removeEventListener('click', panzoom.reset);
            panzoom.destroy();
        }
        return null;
    };

    /**
     * Create the SVG pan zoom.
     */
    const createPanZoom = () => {
        // initialize
        const svgDiagram = getSvgDiagram();
        // eslint-disable-next-line
        const panzoom = Panzoom(svgDiagram, {
            minScale: MIN_SCALE,
            maxScale: MAX_SCALE,
        });

        // set handlers
        reset.addEventListener('click', panzoom.reset);
        zoomIn.addEventListener('click', panzoom.zoomIn);
        zoomOut.addEventListener('click', panzoom.zoomOut);
        svgDiagram.addEventListener('panzoomchange', zoomHandler);
        svgDiagram.parentElement.addEventListener('wheel', panzoom.zoomWithWheel);

        return panzoom;
    };

    /**
     * load the diagram.
     */
    const loadDiagram = () => {
        mermaid.initialize({
            theme: 'base',
            startOnLoad: false,
            useMaxWidth: false,
            securityLevel: 'loose',
            themeVariables: getThemeVariables()
        });
        mermaid.run({
            nodes: [diagram]
        }).then(() => {
            panzoom = destroyPanzoom(panzoom);
            panzoom = createPanZoom();
        });
    };

    /**
     * Save the HTML content of the diagram.
     */
    const saveDiagram = () => {
        diagram.dataset.code = diagram.textContent;
    };

    /**
     * Reset processed diagram.
     */
    const resetDiagram = () => {
        const code = diagram.dataset.code;
        if (code) {
            diagram.removeAttribute(DATA_PROCESSED);
            diagram.textContent = code;
        }
    };

    /**
     * Add a listener for the theme attribute.
     */
    const addThemeObserver = () => {
        const callback = (mutationsList) => {
            for (const mutation of mutationsList) {
                if (mutation.attributeName === THEME_ATTRIBUTE) {
                    resetDiagram();
                    loadDiagram();
                    break;
                }
            }
        };
        const observer = new MutationObserver(callback);
        observer.observe(rootNode, {attributes: true});
    };

    /**
     * Handle diagrams change selection.
     */
    diagrams.addEventListener('change', function () {
        const url = diagram.dataset.url;
        const data = {
            'name': diagrams.value
        };
        $.getJSON(url, data, function (response) {
            // focus
            diagrams.focus();

            // error?
            if (!response.result) {
                showError(response.message);
                return;
            }

            // reload diagram
            diagram.textContent = response.file.content;
            saveDiagram();
            resetDiagram();
            loadDiagram();

            // update history
            const name = response.file.name;
            const url = new URL(location);
            url.searchParams.set('name', name);
            window.history.pushState({'name': name}, '', url);
        });
    });

    /**
     * Handle history pop state.
     */
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.name) {
            diagrams.value = e.state.name;
            diagrams.dispatchEvent(new Event('change'));
        }
    });

    // save and initialize diagrams.
    saveDiagram();
    loadDiagram();

    // add a listener for the theme attribute
    addThemeObserver();
})();
