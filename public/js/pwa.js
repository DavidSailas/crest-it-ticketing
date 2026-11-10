/*
 * PWA glue: registers the service worker and offers "install this app".
 *  - Android / Chrome / Edge: shows the install banner using the browser's own install prompt.
 *  - iPhone / iPad (Safari has no install prompt): shows a short "Share → Add to Home Screen" hint.
 *  - Already installed (opened from the home screen): shows nothing.
 * The banner markup lives in resources/views/partials/pwa-install.blade.php.
 */
(function () {
    'use strict';

    var DISMISS_KEY = 'pwa_install_dismissed_at';
    var DISMISS_DAYS = 7;
    var deferred = null;          // the browser's install prompt, once it offers one

    var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    var ua = window.navigator.userAgent || '';
    var isIos = /iphone|ipad|ipod/i.test(ua) || (/Macintosh/i.test(ua) && navigator.maxTouchPoints > 1);

    if (standalone) document.documentElement.classList.add('pwa-standalone');

    // ---- service worker -------------------------------------------------------------------------
    var cfg = window.PWA_CONFIG || {};
    if ('serviceWorker' in navigator && cfg.sw && (location.protocol === 'https:' || location.hostname === 'localhost')) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(cfg.sw).catch(function () { /* not fatal: the site still works */ });
        });
    }

    // ---- helpers --------------------------------------------------------------------------------
    function recentlyDismissed() {
        try {
            var t = parseInt(localStorage.getItem(DISMISS_KEY) || '0', 10);
            return t && (Date.now() - t) < DISMISS_DAYS * 864e5;
        } catch (e) { return false; }
    }

    function canInstall() { return !standalone && (!!deferred || isIos); }

    function announce() {
        window.dispatchEvent(new CustomEvent('pwa-state', { detail: { canInstall: canInstall() } }));
    }

    function banner() { return document.getElementById('pwa-install'); }

    function show(force) {
        var el = banner();
        if (!el || standalone || (!force && recentlyDismissed())) return;
        el.querySelector('[data-pwa-android]').hidden = !deferred;
        el.querySelector('[data-pwa-ios]').hidden = !!deferred || !isIos;
        if (!deferred && !isIos) return;                       // nothing we can offer in this browser
        el.hidden = false;
        requestAnimationFrame(function () { el.classList.add('is-in'); });
    }

    function hide(remember) {
        var el = banner();
        if (el) { el.classList.remove('is-in'); setTimeout(function () { el.hidden = true; }, 220); }
        if (remember) { try { localStorage.setItem(DISMISS_KEY, String(Date.now())); } catch (e) {} }
    }

    function install() {
        if (deferred) {
            var p = deferred;
            deferred = null;
            p.prompt();
            p.userChoice.then(function (choice) {
                hide(choice && choice.outcome !== 'accepted');
                announce();
            });
        } else if (isIos) {
            show(true);                                        // iOS: only instructions are possible
        }
    }

    // ---- events ---------------------------------------------------------------------------------
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();                                    // we show our own banner instead of the mini-bar
        deferred = e;
        announce();
        show(false);
    });

    window.addEventListener('appinstalled', function () {
        deferred = null;
        hide(false);
        announce();
    });

    document.addEventListener('click', function (e) {
        var t = e.target.closest ? e.target.closest('[data-pwa-action]') : null;
        if (!t) return;
        var act = t.getAttribute('data-pwa-action');
        if (act === 'install') install();
        if (act === 'dismiss') hide(true);
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (isIos && !standalone) setTimeout(function () { show(false); }, 1200);
        announce();
    });

    window.PWA = { canInstall: canInstall, install: install, isStandalone: function () { return standalone; } };
})();
