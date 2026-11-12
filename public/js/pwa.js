/*
 * PWA glue: registers the service worker and offers "install this app".
 *
 * The install sheet (resources/views/partials/pwa-install.blade.php) shows ONE of three views:
 *   android : the browser has an install prompt  -> one-tap "Install app"
 *   manual  : Android, but the browser gave us no install prompt (after ~6s) -> "menu -> Install app" steps
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
    var MANUAL_WAIT = 6000;                    // Android: wait this long for the browser's prompt before showing manual steps
    var INSTALLED_DAYS = 60;                   // trust "installed" for this long after we last saw the app running
    var deferred    = null;                    // the browser's install prompt, once it offers one
    var timer       = null;

    var fullscreenApp = window.matchMedia('(display-mode: fullscreen)').matches;     // installed Android app in full screen
    var standalone = window.matchMedia('(display-mode: standalone)').matches || fullscreenApp || window.navigator.standalone === true;
    var ua    = window.navigator.userAgent || '';
    var isIos = /iphone|ipad|ipod/i.test(ua) || (/Macintosh/i.test(ua) && navigator.maxTouchPoints > 1);
    var isAndroid = /android/i.test(ua);
    var manualReady = false;                   // true once we have waited MANUAL_WAIT without a browser prompt
    var gotPrompt = false;                     // did the browser ever fire beforeinstallprompt?
    var inApp = /FBAN|FBAV|FB_IAB|Instagram|Messenger|Line\/|TikTok|Snapchat|MicroMessenger|; wv\)/i.test(ua);

    if (standalone) document.documentElement.classList.add('pwa-standalone');

    // ---- browser bars follow light / dark ---------------------------------------------------------------
    // The page picks light/dark before it paints, but the browser's top bar colour used to be set only then, so it
    // stayed wrong after the user flipped the toggle. Keep it in step with the <html class="dark"> switch.
    function syncThemeColor() {
        var meta = document.querySelector('meta[name="theme-color"]');
        if (!meta || !document.querySelector('.app-topbar')) return;     // only inside the app (the login page keeps its brand green)
        meta.setAttribute('content', document.documentElement.classList.contains('dark') ? '#0f1419' : '#ffffff');
    }
    document.addEventListener('DOMContentLoaded', syncThemeColor);
    if (window.MutationObserver) {
        new MutationObserver(syncThemeColor).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    }

    // ---- full-screen hint -------------------------------------------------------------------------------
    // The installed Android app hides the system bars. Tell people ONCE how to bring them back, so nobody feels stuck.
    function fullscreenHint() {
        var KEY = 'pwa_fs_hint_seen';
        try { if (localStorage.getItem(KEY)) return; } catch (e) { return; }
        var t = document.createElement('div');
        t.className = 'pwa-toast'; t.setAttribute('role', 'status');
        t.innerHTML = '<span><b>Full-screen mode.</b> Swipe from the top or bottom edge of the screen to show the clock and the Back / Home buttons.</span><button type="button">Got it</button>';
        function done() { try { localStorage.setItem(KEY, '1'); } catch (e) {} t.classList.remove('is-in'); setTimeout(function () { t.remove(); }, 300); }
        t.querySelector('button').addEventListener('click', done);
        document.body.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('is-in'); });
        setTimeout(done, 12000);
    }
    if (fullscreenApp && isAndroid) document.addEventListener('DOMContentLoaded', function () { setTimeout(fullscreenHint, 1500); });

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
        if (s.installed && (Date.now() - (s.seen || 0)) < INSTALLED_DAYS * 864e5) return true;
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
        if (isAndroid && manualReady) return 'manual';  // the browser stayed silent: show the menu steps instead
        return null;                                   // desktop browser without an install prompt: nothing to offer
    }
    function canInstall() { return !standalone && (!!deferred || isIos || (isAndroid && !inApp)); }

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
            var t = readState(); t.installed = true; t.seen = Date.now(); writeState(t);
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
        gotPrompt = true;
        if (sheet() && !sheet().hidden) show(true);    // the manual steps were up: upgrade to the one-tap button
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
        if (standalone) {                              // running as the installed app: remember it (shared with the browser tab)
            var s = readState(); s.installed = true; s.seen = Date.now(); writeState(s);
        }
        announce();
        if (isIos || inApp) scheduleAuto();            // these never get a beforeinstallprompt event
        if (isAndroid && !inApp && !standalone) {      // give the browser a few seconds to offer its prompt, then fall back
            setTimeout(function () { manualReady = true; if (!deferred) scheduleAuto(); }, MANUAL_WAIT - AUTO_DELAY > 0 ? MANUAL_WAIT - AUTO_DELAY : 0);
        }
        if (/[?&]pwa-debug\b/.test(location.search)) debugPanel();
    });

    // ?pwa-debug  ->  a small panel that says what this phone / browser is doing (for testing; nothing changes for normal visitors)
    function debugPanel() {
        var box = document.createElement('div');
        box.style.cssText = 'position:fixed;left:8px;right:8px;top:8px;z-index:2147483647;padding:10px 12px;border-radius:10px;background:#111;color:#9ef0c0;font:12px/1.5 monospace;white-space:pre-wrap;word-break:break-all;max-height:60vh;overflow:auto';
        document.body.appendChild(box);
        var lines = {};
        function paint() { box.textContent = 'PWA debug (tap to close)\n' + Object.keys(lines).map(function (k) { return k + ': ' + lines[k]; }).join('\n'); }
        box.addEventListener('click', function () { box.remove(); });
        function set(k, v) { lines[k] = v; paint(); }
        set('https', String(location.protocol === 'https:'));
        set('browser', ua.replace(/\)\s.*/, ')').slice(0, 90));
        set('android/ios/in-app', [isAndroid, isIos, inApp].join(' / '));
        set('opened as installed app', String(standalone));
        set('saved state', localStorage.getItem(STATE_KEY) || '(none)');
        set('snoozed/installed', String(snoozed()));
        set('browser install prompt', 'waiting...');
        setTimeout(function () { set('browser install prompt', gotPrompt ? 'YES (one-tap install works)' : 'NOT received (manual steps will show)'); set('sheet view', String(currentView())); }, 7000);
        if ('serviceWorker' in navigator) navigator.serviceWorker.getRegistrations().then(function (r) { set('service worker', r.length ? 'registered: ' + (r[0].active ? r[0].active.state : 'installing') : 'NOT registered'); });
        var link = document.querySelector('link[rel="manifest"]');
        if (!link) { set('manifest', 'NO <link rel=manifest> in page'); return; }
        fetch(link.href, { credentials: 'include' }).then(function (res) {
            var type = res.headers.get('content-type') || '';
            return res.text().then(function (t) {
                var ok = false, info = '';
                try { var j = JSON.parse(t); ok = true; info = ' start_url=' + j.start_url + ' icons=' + (j.icons || []).length; } catch (e) { info = ' NOT JSON: ' + t.slice(0, 60).replace(/\s+/g, ' '); }
                set('manifest', res.status + ' ' + type.split(';')[0] + (ok ? ' OK' : '') + info);
            });
        }).catch(function (e) { set('manifest', 'FAILED to load: ' + e); });
    }

    window.PWA = { canInstall: canInstall, install: install, isStandalone: function () { return standalone; } };
})();
