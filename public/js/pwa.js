/*
 * PWA glue: registers the service worker and offers "install this app".
 *
 * The install sheet (resources/views/partials/pwa-install.blade.php) shows ONE of three views:
 *   android : the browser has an install prompt  -> one-tap "Install app"
 *   ios     : iPhone / iPad (no install prompt)  -> 3 short steps with Share -> Add to Home Screen
 *   inapp   : Facebook / Messenger / Instagram ... can't install -> "open in your browser" + copy link
 * It stays out of the way:
 *   - never when already installed (opened from the home screen, or installed before)
 *   - waits a few seconds after the page loads, and while the person is typing
 *   - "Not now" hides it for 7 days; a second "Not now" hides it for 30 days
 */
(function () {
    'use strict';

    var STATE_KEY   = 'pwa_install_state';     // {n: times dismissed, at: last dismissed (ms), installed: bool}
    var SNOOZE_DAYS = [7, 30];                 // after the 1st and the 2nd (and later) "Not now"
    var AUTO_DELAY  = 4000;                    // ms after load / after the browser offers the prompt
    var deferred    = null;                    // the browser's install prompt, once it offers one
    var timer       = null;

    var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    var ua    = window.navigator.userAgent || '';
    var isIos = /iphone|ipad|ipod/i.test(ua) || (/Macintosh/i.test(ua) && navigator.maxTouchPoints > 1);
    var inApp = /FBAN|FBAV|FB_IAB|Instagram|Messenger|Line\/|TikTok|Snapchat|MicroMessenger|; wv\)/i.test(ua);

    if (standalone) document.documentElement.classList.add('pwa-standalone');

    // ---- service worker -------------------------------------------------------------------------
    var cfg = window.PWA_CONFIG || {};
    if ('serviceWorker' in navigator && cfg.sw && (location.protocol === 'https:' || location.hostname === 'localhost')) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(cfg.sw).catch(function () { /* not fatal: the site still works */ });
        });
    }

    // ---- remembered state -----------------------------------------------------------------------
    function readState() {
        try { return JSON.parse(localStorage.getItem(STATE_KEY) || '{}') || {}; } catch (e) { return {}; }
    }
    function writeState(s) {
        try { localStorage.setItem(STATE_KEY, JSON.stringify(s)); } catch (e) { /* private mode: just ask again next time */ }
    }
    function snoozed() {
        var s = readState();
        if (s.installed) return true;
        if (!s.at) return false;
        var days = SNOOZE_DAYS[Math.min((s.n || 1), SNOOZE_DAYS.length) - 1];
        return (Date.now() - s.at) < days * 864e5;
    }

    // ---- what can we offer here? ----------------------------------------------------------------
    function currentView() {
        if (standalone) return null;
        if (deferred) return 'android';
        if (inApp) return 'inapp';
        if (isIos) return 'ios';
        return null;                                   // nothing installable in this browser
    }
    function canInstall() { return !standalone && (!!deferred || isIos); }

    function announce() {
        window.dispatchEvent(new CustomEvent('pwa-state', { detail: { canInstall: canInstall() } }));
    }

    // ---- the sheet ------------------------------------------------------------------------------
    function sheet() { return document.getElementById('pwa-install'); }

    function show(force) {
        var el = sheet(), view = currentView();
        if (!el || !view || (!force && snoozed())) return;
        [].forEach.call(el.querySelectorAll('[data-pwa-view]'), function (v) {
            v.hidden = v.getAttribute('data-pwa-view') !== view;
        });
        el.hidden = false;
        requestAnimationFrame(function () {
            el.classList.add('is-in');
            var panel = el.querySelector('.pwa-sheet__panel');   // focus the sheet itself: screen readers announce it, no ring on a button
            if (panel) { try { panel.focus({ preventScroll: true }); } catch (e) {} }
        });
    }

    function hide(reason) {                            // reason: 'later' | 'installed' | undefined (just close)
        var el = sheet();
        if (el) { el.classList.remove('is-in'); setTimeout(function () { el.hidden = true; }, 340); }
        if (reason === 'later') {
            var s = readState(); s.n = (s.n || 0) + 1; s.at = Date.now(); writeState(s);
        } else if (reason === 'installed') {
            var t = readState(); t.installed = true; writeState(t);
        }
    }

    // Show it a moment after load, but not while someone is typing (the login form is the first page people see).
    function scheduleAuto(tries) {
        clearTimeout(timer);
        if (standalone || snoozed() || !currentView()) return;
        timer = setTimeout(function () {
            var a = document.activeElement, typing = a && /^(INPUT|TEXTAREA|SELECT)$/.test(a.tagName);
            if (typing && (tries || 0) < 3) return scheduleAuto((tries || 0) + 1);
            show(false);
        }, AUTO_DELAY);
    }

    function install() {
        if (deferred) {
            var p = deferred;
            deferred = null;
            p.prompt();
            p.userChoice.then(function (choice) {
                hide(choice && choice.outcome === 'accepted' ? 'installed' : 'later');
                announce();
            });
        } else {
            show(true);                                // iOS / in-app: only instructions are possible
        }
    }

    function copyLink(btn) {
        var label = btn.querySelector('[data-pwa-copy-label]'), url = location.origin + '/';
        function done(ok) {
            if (!label) return;
            label.textContent = ok ? 'Link copied' : 'Press and hold the address bar to copy';
            setTimeout(function () { label.textContent = 'Copy link'; }, 2500);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function () { done(true); }, function () { done(false); });
        } else {
            try {
                var ta = document.createElement('textarea'); ta.value = url; ta.setAttribute('readonly', '');
                ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select();
                var ok = document.execCommand('copy'); document.body.removeChild(ta); done(ok);
            } catch (e) { done(false); }
        }
    }

    // ---- events ---------------------------------------------------------------------------------
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();                            // we show our own sheet instead of the browser's mini-bar
        deferred = e;
        announce();
        scheduleAuto();
    });

    window.addEventListener('appinstalled', function () {
        deferred = null;
        hide('installed');
        announce();
    });

    document.addEventListener('click', function (e) {
        var t = e.target.closest ? e.target.closest('[data-pwa-action]') : null;
        if (!t) return;
        var act = t.getAttribute('data-pwa-action');
        if (act === 'install') install();
        else if (act === 'dismiss') hide('later');
        else if (act === 'copy') copyLink(t);
    });

    document.addEventListener('keydown', function (e) {
        var el = sheet();
        if (e.key === 'Escape' && el && !el.hidden) hide('later');
    });

    document.addEventListener('DOMContentLoaded', function () {
        announce();
        if (isIos || inApp) scheduleAuto();            // these never get a beforeinstallprompt event
    });

    window.PWA = { canInstall: canInstall, install: install, isStandalone: function () { return standalone; } };
})();
