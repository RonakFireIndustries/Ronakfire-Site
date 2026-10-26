/* Ronak Fire store — print helper */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    onReady(function () {
        var buttons = document.querySelectorAll('[data-print]');
        Array.prototype.forEach.call(buttons, function (button) {
            button.addEventListener('click', function () {
                window.print();
            });
        });

        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'p') {
                event.preventDefault();
                window.print();
            }
        });
    });
})();
