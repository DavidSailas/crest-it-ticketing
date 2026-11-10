/*
 * Phones: the "Devices by type" / "Supplies by category" chips scroll sideways.
 * This scrolls the selected chip to the middle so you can always see what is filtered,
 * including after the list refreshes itself (live filtering swaps the strip in place).
 */
(function () {
    'use strict';
    var done = new WeakSet();

    function centre() {
        document.querySelectorAll('.tile-strip').forEach(function (strip) {
            if (done.has(strip)) return;                       // only a fresh strip; never fight the user's own swiping
            done.add(strip);
            var on = strip.querySelector('.type-card.is-on');
            if (!on || strip.scrollWidth <= strip.clientWidth) return;
            var shift = on.getBoundingClientRect().left - strip.getBoundingClientRect().left - (strip.clientWidth - on.offsetWidth) / 2;
            strip.scrollLeft += shift;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        centre();
        var main = document.querySelector('main');
        if (main && 'MutationObserver' in window) new MutationObserver(centre).observe(main, { childList: true, subtree: true });
    });
})();
