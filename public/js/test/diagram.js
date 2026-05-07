/* globals Toaster, mermaid, Panzoom, bootstrap */

(() => {
    'use strict';

    const THEME_ATTRIBUTE = 'data-bs-theme';
    const DATA_PROCESSED = 'data-processed';
    const DEFAULT_ZOOM = 1.0;
    const ZOOM_STEP = 0.05;
    const MIN_ZOOM = 0.5;
    const MAX_ZOOM = 2.0;

    /**
     * The diagram renderer.
     * @type {HTMLDivElement}
     */
    const diagram = document.getElementById('diagram');

    /**
     * The diagram list (toolbar).
     * @type {HTMLSelectElement}
     */
    const diagrams = document.getElementById('diagrams');

    /**
     * The reset button (toolbar).
     * @type {HTMLButtonElement}
     */
    const buttonReset = document.querySelector('.btn-reset');

    /**
     * The zoom button (toolbar).
     * @type {HTMLButtonElement}
     */
    const buttonZoom = document.querySelector('.btn-zoom');

    /**
     * The zoom-in button (toolbar).
     * @type {HTMLButtonElement}
     */
    const buttonZoomIn = document.querySelector('.btn-zoom-in');

    /**
     * The zoom-out button (toolbar).
     * @type {HTMLButtonElement}
     */
    const buttonZoomOut = document.querySelector('.btn-zoom-out');

    /**
     * The reset zoom button (drop-down).
     * @type {HTMLButtonElement}
     */
    const buttonResetZoom = document.querySelector('.btn-reset-zoom');

    /**
     * The zoom range input (drop-down).
     * @type {HTMLInputElement}
     */
    const rangeZoom = document.getElementById('rangeZoom');

    /**
     * The SVG panzoom.
     * @type {Object}
     */
    let panzoom = null;

    /**
     * The themed colors.
     * @type {Object.<string, string>}
     */
    let themeVariables = null;

    /**
     * Gets the SVG element.
     * @return {SVGSVGElement}
     */
    const getSvgDiagram = () => document.querySelector('#diagram svg');

    /**
     * The number to format zoom.
     *
     * eslint new-cap: ["error", { "newIsCap": false }]
     */
    const zoomFormatter = Intl.NumberFormat('de-DE', {
        style: 'percent',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });

    /**
     * Round the zoom value to the nearest 0.05.
     * @param {number} zoom
     * @return {number}
     */
    const roundZoom = (zoom) => {
        return Math.round(zoom / ZOOM_STEP) * ZOOM_STEP;
    };

    /**
     * Show the given error message.
     * @param {string} message
     */
    const showError = (message) => {
        const title = document.querySelector('.card-title').textContent;
        Toaster.danger(message, title);
    };

    /**
     * Gets the zoom value.
     * @return {number}
     */
    const getZoom = () => {
        return panzoom ? roundZoom(panzoom.getScale()) : DEFAULT_ZOOM;
    };

    /**
     * Sets the zoom value.
     * @param {number} zoom
     */
    const setZoom = (zoom) => {
        zoom = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, roundZoom(zoom)));
        if (panzoom && getZoom() !== zoom) {
            panzoom.zoom(zoom);
        }
    };

    /**
     * Hide the drop-down zoom.
     */
    const hideZoomDropDown = () => {
        bootstrap.Dropdown.getOrCreateInstance(buttonZoom).hide();
    };

    /**
     * Hide the diagram tooltip (if any).
     */
    const hideTooltip = () => {
        const tooltip = document.querySelector('.mermaidTooltip');
        if (tooltip) {
            tooltip.style.opacity = 0;
        }
    };

    /**
     * Handle the diagram node click.
     * @param {string} nodeId - the node identifier like: "AbstractCodeEntity".
     */
    window.nodeCallback = (nodeId) => {
        hideTooltip();
        let found = false;
        const name = nodeId.snakeCase();
        for (const option of diagrams.options) {
            if (name.equalsIgnoreCase(option.value)) {
                diagrams.value = option.value;
                diagrams.dispatchEvent(new Event('change'));
                found = true;
                break;
            }
        }
        if (!found) {
            showError(diagram.dataset.error.replace('%name%', nodeId));
        }
    };

    /**
     * Gets the themed color variables.
     * @return {Object.<string, string>}
     */
    const getThemeVariables = () => {
        if (!themeVariables) {
            const header = document.querySelector('.card-header');
            const computedStyle = window.getComputedStyle(header);
            themeVariables = {
                primaryColor: computedStyle.backgroundColor,
                primaryTextColor: computedStyle.color,
                primaryBorderColor: computedStyle.borderBottomColor,
                lineColor: computedStyle.borderBottomColor
            };
        }
        return themeVariables;
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
     * Push or replace a new state to the history.
     * @param {String} name the diagram name
     * @param {Boolean} replace true to replace, false to push
     */
    const pushState = (name, replace = false) => {
        const url = new URL(location);
        url.searchParams.set('name', name);
        url.searchParams.set('zoom', String(getZoom()));
        if (replace) {
            window.history.replaceState({'name': name}, '', url);
        } else {
            window.history.pushState({'name': name}, '', url);
        }
    };

    /**
     * Listener to the zoom wheel panzoom.
     * @param {WheelEvent} e the wheel event.
     */
    const zoomWheelListener = (e) => {
        hideZoomDropDown();
        if (panzoom) {
            const delta = e.deltaY === 0 && e.deltaX ? e.deltaX : e.deltaY;
            const step = delta < 0 ? ZOOM_STEP : -ZOOM_STEP;
            const toScale = roundZoom(panzoom.getScale() + step);
            panzoom.zoomToPoint(toScale, e, null, e);
        }
    };

    /**
     * Listener to zoom-in.
     */
    const zoomInListener = () => {
        setZoom(getZoom() + ZOOM_STEP);
    };

    /**
     * Listener to zoom-out.
     */
    const zoomOutListener = () => {
        setZoom(getZoom() - ZOOM_STEP);
    };


    /**
     * Listener to zoom change event.
     */
    const zoomChangeListener = function (event) {
        const scale = event.detail.scale;
        updateState(buttonReset, scale === DEFAULT_ZOOM && event.detail.x === 0 && event.detail.y === 0);
        updateState(buttonZoomIn, scale >= MAX_ZOOM);
        updateState(buttonZoomOut, scale <= MIN_ZOOM);
        buttonZoom.textContent = zoomFormatter.format(scale);
        pushState(diagrams.value, true);
    };

    /**
     * Listener to reset panzoom.
     */
    const resetZoomListener = () => {
        if (panzoom) {
            panzoom.reset({startScale: DEFAULT_ZOOM});
        }
    };

    /**
     * Remove the panzoom listener.
     * @param {SVGSVGElement} svgDiagram
     */
    const removePanzoomListeners = function (svgDiagram) {
        svgDiagram.parentElement.removeEventListener('wheel', zoomWheelListener);
        svgDiagram.removeEventListener('panzoomchange', zoomChangeListener);
        buttonReset.removeEventListener('click', resetZoomListener);
        buttonZoomIn.removeEventListener('click', zoomInListener);
        buttonZoomOut.removeEventListener('click', zoomOutListener);
    };

    /**
     * Add panzoom listeners.
     * @param {SVGSVGElement} svgDiagram
     */
    const addPanzoomListeners = function (svgDiagram) {
        svgDiagram.parentElement.addEventListener('wheel', zoomWheelListener);
        svgDiagram.addEventListener('panzoomchange', zoomChangeListener);
        buttonReset.addEventListener('click', resetZoomListener);
        buttonZoomIn.addEventListener('click', zoomInListener);
        buttonZoomOut.addEventListener('click', zoomOutListener);
    };

    /**
     * Destroy and re-create the panzoom.
     * @param {number} zoom the zoom factor
     */
    const resetPanZoom = (zoom = DEFAULT_ZOOM) => {
        const svgDiagram = getSvgDiagram();
        removePanzoomListeners(svgDiagram);
        if (panzoom) {
            panzoom.destroy();
            panzoom = null;
        }
        // eslint-disable-next-line
        panzoom = Panzoom(svgDiagram, {
            origin: '0 0',
            startScale: zoom,
            minScale: MIN_ZOOM,
            maxScale: MAX_ZOOM,
        });
        addPanzoomListeners(svgDiagram);
    };

    /**
     * load the diagram.
     * @param {number} zoom the zoom factor
     */
    const loadDiagram = (zoom = DEFAULT_ZOOM) => {
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
            resetPanZoom(zoom);
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
     * @param {boolean} resetTheme true to reset the themed colors.
     * @param {number} zoom the zoom factor
     */
    const reloadDiagram = (resetTheme, zoom = DEFAULT_ZOOM) => {
        if (resetTheme) {
            themeVariables = null;
        }
        hideTooltip();
        resetDiagram();
        loadDiagram(zoom);
    };

    const initZoomDropDown = () => {
        rangeZoom.min = String(MIN_ZOOM);
        rangeZoom.max = String(MAX_ZOOM);
        rangeZoom.step = String(ZOOM_STEP);
        rangeZoom.addEventListener('input', () => {
            const scale = Number.parseFloat(rangeZoom.value);
            updateState(buttonResetZoom, scale === DEFAULT_ZOOM);
            setZoom(scale);
        });
        rangeZoom.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                buttonZoom.focus();
            }
        });
        buttonZoom.addEventListener('show.bs.dropdown', () => {
            if (panzoom) {
                const scale = getZoom();
                updateState(buttonResetZoom, scale === DEFAULT_ZOOM);
                rangeZoom.value = String(scale);
            }
        });
        buttonZoom.addEventListener('shown.bs.dropdown', () => {
            rangeZoom.focus();
        });
        buttonZoom.addEventListener('hidden.bs.dropdown', () => {
            buttonZoom.focus();
        });
        buttonResetZoom.addEventListener('click', () => {
            setZoom(DEFAULT_ZOOM);
        });
    };

    /**
     * Handle diagrams change selection.
     */
    diagrams.addEventListener('change', function () {
        const url = diagram.dataset.url;
        const data = {
            'name': diagrams.value,
            'zoom': getZoom(),
        };
        $.getJSON(url, data, function (response) {
            if (!response.result) {
                showError(response.message);
                return;
            }
            diagram.textContent = response.diagram.content;
            saveDiagram();
            reloadDiagram(false, response.zoom || DEFAULT_ZOOM);
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

    // initialize the zoom drop-down
    initZoomDropDown();

    // add a listener when the theme data attribute is changing
    const documentElement = document.documentElement;
    const observer = new MutationObserver(() => reloadDiagram(true, getZoom()));
    observer.observe(documentElement, {attributeFilter: [THEME_ATTRIBUTE]});

    // get zoom
    const zoom = Number.parseFloat(diagram.dataset.zoom || DEFAULT_ZOOM);

    // save and load diagrams.
    saveDiagram();
    loadDiagram(zoom);
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
        $('#diagram-data').text(code);
    });
});
