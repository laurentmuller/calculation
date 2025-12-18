/* globals Toaster, mermaid, Panzoom */

(() => {
    'use strict';

    const THEME_ATTRIBUTE = 'data-bs-theme';
    const DATA_PROCESSED = 'data-processed';
    const CLASS_REGEX = /classId-(.*)-\d+/;
    const REPLACE_REGEX = /([a-z])([A-Z])/g;
    const REPLACE_TARGET = '$1_$2';
    const MIN_SCALE = 0.5;
    const MAX_SCALE = 2.0;
    const ZOOM_STEP = 0.05;

    /**
     * The diagram renderer.
     * @type {HTMLDivElement}
     */
    const diagram = document.getElementById('diagram');

    /**
     * The diagrams list (toolbar).
     * @type {HTMLSelectElement}
     */
    const diagrams = document.getElementById('diagrams');

    /**
     * The root node of the document.
     * @type {HTMLElement}
     */
    const rootNode = document.documentElement;

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
     * The zoom label (drop-down).
     * @type {HTMLLabelElement}
     */
    const rangeLabel = document.getElementById('rangeLabel');

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
     * Gets SVG element.
     * @return {SVGSVGElement}
     */
    const getSvgDiagram = () => document.querySelector('#diagram svg');

    /**
     * The number to format zoom.
     */
    const zoomFormatter = new Intl.NumberFormat('en', {
        style: 'percent',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });

    /**
     * Round the zoom value to the nearest 0.05.
     * @param {number} value
     * @return {number}
     */
    const roundZoom = (value) => {
        return Math.ceil(value * 20) / 20;
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
     * Sets the zoom value.
     * @param {number} zoom the zoom to set.
     */
    const setZoom = (zoom) => {
        if (!panzoom) {
            return;
        }
        zoom = Math.max(MIN_SCALE, Math.min(MAX_SCALE, roundZoom(zoom)));
        if (panzoom.getScale() === zoom) {
            return;
        }
        panzoom.zoom(zoom);
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
     * Gets the themed color variables.
     * @return {Object.<string, string>}
     */
    const getThemeVariables = () => {
        if (!themeVariables) {
            const element = document.querySelector('.card-header');
            const style = getComputedStyle(element);
            themeVariables = {
                primaryTextColor: style.color,
                primaryColor: style.backgroundColor,
                primaryBorderColor: style.borderBottomColor,
                lineColor: style.borderBottomColor
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
        updateState(buttonReset, scale === 1 && event.detail.x === 0 && event.detail.y === 0);
        buttonZoom.textContent = zoomFormatter.format(scale);
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
        buttonReset.removeEventListener('click', resetZoomListener);
    };

    /**
     * Add panzoom listeners.
     * @param {SVGSVGElement} svgDiagram
     */
    const addPanzoomListeners = function (svgDiagram) {
        svgDiagram.parentElement.addEventListener('wheel', (e) => zoomWheelListener(e));
        svgDiagram.addEventListener('panzoomchange', zoomChangeListener);
        buttonReset.addEventListener('click', resetZoomListener);
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
            setTransform: (_, {scale, x, y}) => {
                let newScale = roundZoom(scale);
                if (newScale !== scale) {
                    if (newScale === startScale) {
                        newScale = startScale - ZOOM_STEP;
                    }
                }
                panzoom.setStyle('transform', `scale(${newScale}) translate(${x}px, ${y}px)`);
                startScale = newScale;
                setZoom(newScale);
            }
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
     * @param {boolean} resetTheme true to reset the themed colors.
     */
    const reloadDiagram = (resetTheme) => {
        if (resetTheme) {
            themeVariables = null;
        }
        resetDiagram();
        loadDiagram();
    };

    const initZoomDropDown = () => {
        rangeZoom.min = String(MIN_SCALE);
        rangeZoom.max = String(MAX_SCALE);
        rangeZoom.step = String(ZOOM_STEP);
        rangeZoom.style.minWidth = '15rem';
        rangeZoom.addEventListener('input', () => {
            const scale = Number.parseFloat(rangeZoom.value);
            rangeLabel.textContent = zoomFormatter.format(scale);
            setZoom(scale);
        });
        rangeZoom.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                buttonZoom.focus();
            }
        });
        buttonZoom.addEventListener('show.bs.dropdown', () => {
            if (panzoom) {
                const scale = panzoom.getScale();
                rangeLabel.textContent = zoomFormatter.format(scale);
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
            setZoom(1);
        });
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
            reloadDiagram(false);
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

    // initialize the zoom drop-down
    initZoomDropDown();

    // add a listener when the theme data attribute is changing
    const observer = new MutationObserver(() => reloadDiagram(true));
    observer.observe(rootNode, {attributeFilter: [THEME_ATTRIBUTE]});

    // save and load diagrams.
    saveDiagram();
    loadDiagram();
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
