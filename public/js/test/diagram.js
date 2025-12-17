/* globals Toaster, mermaid, Panzoom */

(() => {
    'use strict';

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
     * The SVG panzoom.
     * @type {Object}
     */
    let panzoom = null;

    /**
     * Gets SVG element.
     * @return {SVGSVGElement}
     */
    const getSvgDiagram = () => document.querySelector('#diagram svg');

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
     * Gets card header colors
     * @return {{textColor: string, backgroundColor: string, borderColor: string}}
     */
    const getColors = () => {
        const element = document.querySelector('.card-header');
        const style = getComputedStyle(element);
        return {
            textColor: style.color,
            backgroundColor: style.backgroundColor,
            borderColor: style.borderBottomColor,
        };
    };

    /**
     * Gets the themed color variables.
     * @return {Object.<string, string>}
     */
    const getThemeVariables = () => {
        const colors = getColors();
        return {
            primaryTextColor: colors.textColor,
            primaryColor: colors.backgroundColor,
            primaryBorderColor: colors.borderColor,
            lineColor: colors.borderColor
        };
    };

    /**
     * Update the disabled state of the given button.
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
     * Listener to zoom wheel panzoom.
     * @param {WheelEvent} e the wheel event.
     */
    const zoomWheelListener = (e) => {
        if (panzoom) {
            panzoom.zoomWithWheel(e);
        }
    };

    /**
     * Listener to zoom change event.
     */
    const zoomChangeListener = function (event) {
        const scale = event.detail.scale;
        updateState(zoomIn, scale >= MAX_SCALE);
        updateState(zoomOut, scale <= MIN_SCALE);
        updateState(reset, scale === 1 && event.detail.x === 0 && event.detail.y === 0);
        zoom.textContent = zoomFormatter.format(scale);
    };

    /**
     * Listener to zoom out panzoom.
     */
    const zoomOutListener = () => {
        if (panzoom) {
            panzoom.zoomOut();
        }
    };

    /**
     * Listener to zoom in panzoom.
     */
    const zoomInListener = () => {
        if (panzoom) {
            panzoom.zoomIn();
        }
    };

    /**
     * Listener to reset panzoom.
     */
    const resetZoomListener = () => {
        if (panzoom) {
            panzoom.reset({startScale: 1});
        }
    };

    /**
     * Remove panzoom listeners.
     * @param {SVGSVGElement} svgDiagram
     */
    const removePanzoomListeners = function (svgDiagram) {
        svgDiagram.parentElement.removeEventListener('wheel', zoomWheelListener);
        svgDiagram.removeEventListener('panzoomchange', zoomChangeListener);
        zoomOut.removeEventListener('click', zoomOutListener);
        zoomIn.removeEventListener('click', zoomInListener);
        reset.removeEventListener('click', resetZoomListener);
    };

    /**
     * Add panzoom listeners.
     * @param {SVGSVGElement} svgDiagram
     */
    const addPanzoomListeners = function (svgDiagram) {
        svgDiagram.parentElement.addEventListener('wheel', (e) => zoomWheelListener(e));
        svgDiagram.addEventListener('panzoomchange', zoomChangeListener);
        zoomOut.addEventListener('click', zoomOutListener);
        zoomIn.addEventListener('click', zoomInListener);
        reset.addEventListener('click', resetZoomListener);
    };

    /**
     * Destroy and re-create the panzoom.
     */
    const resetPanZoom = () => {
        let startScale = 1;
        const svgDiagram = getSvgDiagram();
        if (panzoom) {
            // save scale and remove listeners
            startScale = panzoom.getScale();
            removePanzoomListeners(svgDiagram);
            panzoom.destroy();
            panzoom = null;
        }
        // eslint-disable-next-line
        panzoom = Panzoom(svgDiagram, {
            step: 0.1,
            origin: '0 0',
            startScale: startScale,
            minScale: MIN_SCALE,
            maxScale: MAX_SCALE,
        });
        // add listeners
        addPanzoomListeners(svgDiagram);
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
            themeVariables: getThemeVariables(),
            class: {
                hideEmptyMembersBox: true
            }
        });
        mermaid.run({
            nodes: [diagram]
        }).then(() => {
            resetPanZoom();
        });
    };

    /**
     * Save the text content of the diagram.
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
     * Reset and load the diagram.
     */
    const reloadDiagram = () => {
        resetDiagram();
        loadDiagram();
    };

    /**
     * Push a new state to the history.
     * @param {string} name
     */
    const pushState = (name) => {
        const url = new URL(location);
        url.searchParams.set('name', name);
        window.history.pushState({'name': name}, '', url);
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
            diagram.textContent = response.diagram.content;
            saveDiagram();
            reloadDiagram();
            // update history
            pushState(response.diagram.name);
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

    // save and load diagrams.
    saveDiagram();
    loadDiagram();

    // add a listener when the theme data attribute is changing
    const observer = new MutationObserver(() => reloadDiagram());
    observer.observe(rootNode, {attributeFilter: [THEME_ATTRIBUTE]});
})();

/**
 * Ready function
 */
$(function () {
    'use strict';
    $('#diagram-modal .btn-copy').copyClipboard();
    $('#diagram-modal').on('hide.bs.modal', function () {
        $(this).find('.pre-scrollable').scrollTop(0);
    }).on('show.bs.modal', function () {
        const $diagram = $('#diagram');
        let code = $diagram[0].dataset.code;
        if (!code) {
            const name = $('#diagrams option:selected').text();
            code = $diagram.data('error').replace('%name%', name);
        }
        $('#diagram-data').text(code.trim());
    });
});
