/**
 * Warn if length of input value exists data-maxlength attribute.
 * Add style '.warn' to the input element.
 * See ../stylesheets/admin-maxlength.css
 */

(function() {
    function check(input) {
        if (input.value.length > input.getAttribute('data-maxlength')) {
            input.classList.add('warn');
        } else if (input.classList.contains('warn')) {
            input.classList.remove('warn');
        }
    }

    function init() {
        Array.prototype.forEach.call(document.querySelectorAll('input[data-maxlength]'), function(input) {
            input.addEventListener('input', function() {
                check(input);
            });
            check(input);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
