/**
 * Ready function
 */
(() => {
    'use strict';
    const icons = document.querySelectorAll('i.fa-rotate-by');
    icons.forEach((icon) => {
        icon.style.setProperty('--fa-rotate-angle', icon.dataset.rotate);
    });
})();
