/**! compression tag for ftp-deployment */

/* globals Toaster, mermaid, Panzoom */

(() => {
    'use strict';

    const THEME_DARK = 'dark';
    const THEME_ATTRIBUTE = 'data-bs-theme';

    const DATA_CODE = 'data-code';
    const DATA_PROCESSED = 'data-processed';

    const DIAGRAM_SELECTOR = '#diagram';
    const SVG_SELECTOR = '#diagram svg';

    const CLASS_REGEX = /classId-(.*)-\d+/;
    const REPLACE_REGEX = /([a-z])([A-Z])/g;
    const REPLACE_TARGET = '$1_$2';

    const MIN_SCALE = 0.5;
    const MAX_SCALE = 3.0;

    /**
     * The diagram renderer.
     * @var {jQuery<HTMLElement>}
     */
    const $diagram = $(DIAGRAM_SELECTOR);

    /**
     * The diagrams list.
     * @var {jQuery<HTMLSelectElement>}
     */
    const $diagrams = $('#diagrams');

    // the HTML document element
    const targetNode = document.documentElement;

    /**
     * The zoom-in button
     */
    const $zoomIn = $('.btn-zoom-in');

    /**
     * The zoom-out button.
     */
    const $zoomOut = $('.btn-zoom-out');

    /**
     * The reset button.
     */
    const $reset = $('.btn-reset');

    /**
     * The zoom label.
     */
    const $zoom = $('#zoom');

    /**
     * The SVG pan zoom.
     */
    let panzoom = null;

    /**
     * Gets SVG element.
     * @return {SVGSVGElement}
     */
    const getSvgDiagram = () => document.querySelector(SVG_SELECTOR);

    /**
     * Returns if the dark theme is selected.
     * @return {boolean}
     */
    const isDarkTheme = () => targetNode.getAttribute(THEME_ATTRIBUTE) === THEME_DARK;

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
            const message = $diagram.data('error').replace('%name%', nodeId);
            showError(message);
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
     * Handle the pan zoom change event.
     */
    const changeHandler = function (event) {
        const scale = event.detail.scale;
        const initial = scale === 1 && event.detail.x === 0 && event.detail.y === 0;
        $zoomOut.attr('disabled', scale <= MIN_SCALE ? 'disabled' : null);
        $zoomIn.attr('disabled', scale >= MAX_SCALE ? 'disabled' : null);
        $reset.attr('disabled', initial ? 'disabled' : null);
        $zoom.text(zoomFormatter.format(scale));
    };


    /**
     * Destroy the SVG pan zoom.
     */
    const destroyPanzoom = (panzoom) => {
        if (panzoom) {
            const svgDiagram = getSvgDiagram();
            svgDiagram.parentElement.removeEventListener('wheel', panzoom.zoomWithWheel);
            svgDiagram.removeEventListener('panzoomchange', changeHandler)
            $zoomOut.off('click');
            $zoomIn.off('click');
            $reset.off('click');
            panzoom.destroy();
        }
        return null;
    }

    /**
     * Create the SVG pan zoom.
     */
    const createPanZoom = () => {
        // initialize
        const svgDiagram = getSvgDiagram();
        const panzoom = Panzoom(svgDiagram, {
            minScale: MIN_SCALE, maxScale: MAX_SCALE,
        });

        // set handlers
        $reset.on('click', () => panzoom.reset());
        $zoomIn.on('click', () => panzoom.zoomIn());
        $zoomOut.on('click', () => panzoom.zoomOut());
        svgDiagram.addEventListener('panzoomchange', changeHandler)
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
            querySelector: DIAGRAM_SELECTOR,
        }).then(() => {
            panzoom = destroyPanzoom(panzoom);
            panzoom = createPanZoom();
        });
    };

    /**
     * Save the HTML content of the diagram.
     */
    const saveDiagram = () => {
        $diagram.attr(DATA_CODE, $diagram.html());
    };

    /**
     * Reset processed diagram.
     */
    const resetDiagram = () => {
        const code = $diagram.attr(DATA_CODE);
        if (code) {
            $diagram.attr(DATA_PROCESSED, null);
            $diagram.html(code);
        }
    };

    /**
     * Add a listener for the theme attribute.
     */
    const addThemeObserver = () => {
        const mutationCallback = (mutationsList) => {
            for (const mutation of mutationsList) {
                if (mutation.attributeName === THEME_ATTRIBUTE) {
                    resetDiagram();
                    loadDiagram();
                    break;
                }
            }
        };
        const observer = new MutationObserver(mutationCallback);
        observer.observe(targetNode, {attributes: true});
    };

    /**
     * Handle diagrams change selection.
     */
    $diagrams.on('change', function () {
        const url = $('#diagram').data('url');
        if (!url) {
            showError('Unable to find the corresponding diagram (URL not defined).');
            return;
        }
        const data = {
            'name': $diagrams.val()
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

    /**
     * Handle history pop state.
     */
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.name) {
            const name = e.state.name;
            $diagrams.val(name).trigger('change');
        }
    });

    // save and initialize diagrams.
    saveDiagram();
    loadDiagram();

    // add a listener for the theme attribute
    addThemeObserver();
})();
