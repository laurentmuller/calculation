/**! compression tag for ftp-deployment */

/**
 * -------------- jQuery Extensions --------------
 */

jQuery.extend({
    highlight: function (node, regex, nodeName, className) {
        'use strict';
        if (node.nodeType === 3) {
            const text = node.data.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            const match = text.match(regex);
            // var match = node.data.match(re);
            if (match) {
                var highlight = document.createElement(nodeName || 'span');
                highlight.className = className || 'highlight';
                var wordNode = node.splitText(match.index);
                wordNode.splitText(match[0].length);
                var wordClone = wordNode.cloneNode(true);
                highlight.appendChild(wordClone);
                wordNode.parentNode.replaceChild(highlight, wordNode);
                return 1; // skip added node in parent
            }
        } else if (node.nodeType === 1 && node.childNodes && !/(script|style)/i.test(node.tagName) && !(node.tagName === nodeName.toUpperCase() && node.className === className)) {
            // only element nodes that have children
            // ignore script and style
            // nodes, skip if already highlighted
            for (let i = 0, len = node.childNodes.length; i < len; i++) {
                i += jQuery.highlight(node.childNodes[i], regex, nodeName, className);
            }
        }
        return 0;
    }
});

jQuery.fn.unhighlight = function (options) {
    'use strict';
    var settings = {
        className: 'highlight',
        element: 'span'
    };
    jQuery.extend(settings, options);

    return this.find(settings.element + "." + settings.className).each(function () {
        const parent = this.parentNode;
        parent.replaceChild(this.firstChild, this);
        parent.normalize();
    }).end();
};

jQuery.fn.highlight = function (words, options) {
    'use strict';
    let settings = {
        className: 'highlight',
        element: 'span',
        caseSensitive: false,
        wordsOnly: false
    };
    jQuery.extend(settings, options);

    if (words.constructor === String) {
        words = [words];
    }
    words = jQuery.grep(words, function (word) {
        return word !== '';
    });
    words = jQuery.map(words, function (word) {
        return word.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
    });
    if (words.length === 0) {
        return this;
    }

    const flag = settings.caseSensitive ? "" : "i";
    let pattern = "(" + words.join("|") + ")";
    if (settings.wordsOnly) {
        pattern = "\\b" + pattern + "\\b";
    }
    const regex = new RegExp(pattern, flag);

    return this.each(function () {
        jQuery.highlight(this, regex, settings.element, settings.className);
    });
};
