/*
 * Live announcements — a new announcement appears for everyone who is signed in
 * within a few seconds, without reloading the page.
 *
 * Every POLL_MS the browser asks /announcements/poll "has anything changed since
 * <sig>?". Nothing changed -> a tiny {changed:false}. Something changed -> we
 *   1. update the unread badges (bell + phone menu),
 *   2. hand any new pop-ups to the pop-up component (event "ann-push"),
 *   3. refresh every [data-ann-live][id] region on the page (dashboard banner,
 *      announcements feed) by re-fetching the current page and swapping them.
 *
 * It sleeps while the tab is hidden and catches up the moment the tab is shown
 * again. Plain JavaScript, no build step, works on shared hosting.
 */
(function () {
    'use strict';

    if (window.__annLive) return;
    window.__annLive = true;

    var POLL_MS = 10000;      // how often an open, visible tab checks
    var MAX_MS = 60000;       // back-off ceiling while the server is unreachable

    var cfg = null, sig = '', delay = POLL_MS, timer = null, busy = false, stopped = false;
    var lastHtml = {};        // region id -> the server HTML we last put there

    function qsa(sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); }

    /* ---------- Badges ---------- */

    function setBadges(n) {
        n = parseInt(n, 10) || 0;
        qsa('[data-ann-badge]').forEach(function (el) {
            el.textContent = (el.getAttribute('data-ann-badge') === 'cap' && n > 9) ? '9+' : String(n);
            el.style.display = n > 0 ? '' : 'none';   // inline style beats the utility classes
        });
        qsa('[data-ann-bell]').forEach(function (el) {
            el.setAttribute('aria-label', 'Announcements' + (n > 0 ? ' (' + n + ' unread)' : ''));
        });
    }

    /* ---------- Refresh the on-page regions ---------- */

    function refreshRegions() {
        if (!qsa('[data-ann-live][id]').length) return;

        fetch(location.pathname + location.search, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin',
            cache: 'no-store'
        }).then(function (res) {
            // Session ended or we were redirected somewhere else: leave the page alone.
            if (!res.ok || new URL(res.url).pathname !== location.pathname) return null;
            return res.text();
        }).then(function (html) {
            if (!html) return;
            var doc = new DOMParser().parseFromString(html, 'text/html');
            qsa('[data-ann-live][id]').forEach(function (el) {
                var fresh = doc.getElementById(el.id);
                if (!fresh || lastHtml[el.id] === fresh.innerHTML) return;
                lastHtml[el.id] = fresh.innerHTML;
                el.innerHTML = fresh.innerHTML;   // Alpine wakes the new nodes up by itself
            });
        }).catch(function () { /* try again on the next change */ });
    }

    /* ---------- Apply a "something changed" answer ---------- */

    function apply(data) {
        sig = data.sig || '';
        setBadges(data.unread);

        if (data.popups && data.popups.length) {
            window.dispatchEvent(new CustomEvent('ann-push', { detail: data.popups }));
        }
        refreshRegions();
    }

    /* ---------- Polling loop ---------- */

    function schedule(ms) {
        clearTimeout(timer);
        if (stopped) return;
        timer = setTimeout(function () {
            if (!document.hidden) poll();   // hidden tabs wake up via "visibilitychange"
        }, ms + Math.random() * 1000);      // jitter, so every browser doesn't hit at the same second
    }

    function poll() {
        if (busy || stopped) return;
        busy = true;

        fetch(cfg.url + (cfg.url.indexOf('?') > -1 ? '&' : '?') + 'sig=' + encodeURIComponent(sig), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-store'
        }).then(function (res) {
            if (res.status === 401 || res.status === 419) { stopped = true; return null; }   // signed out
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        }).then(function (data) {
            if (data && data.changed) apply(data);
            delay = POLL_MS;
        }).catch(function () {
            delay = Math.min(delay * 2, MAX_MS);   // offline / server hiccup: slow down, keep trying
        }).then(function () {
            busy = false;
            schedule(delay);
        });
    }

    function wake() {
        if (stopped || document.hidden) return;
        clearTimeout(timer);
        poll();
    }

    /* ---------- Init ---------- */

    function init() {
        var el = document.getElementById('ann-live');
        if (!el || !el.getAttribute('data-url')) return;   // not a signed-in page

        cfg = { url: el.getAttribute('data-url') };
        sig = el.getAttribute('data-sig') || '';

        document.addEventListener('visibilitychange', wake);   // back on the tab: check right away
        window.addEventListener('online', wake);
        window.addEventListener('pageshow', function (e) { if (e.persisted) wake(); });

        schedule(POLL_MS);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
