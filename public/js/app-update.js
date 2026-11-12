/*
 * Auto-update for the installed app and the browser.
 *
 * Every minute (and whenever the app comes back to the front) this asks /app-version for a fingerprint of the
 * site's views and compiled files. When that fingerprint changes (you deployed something) the page reloads
 * itself, so employees always run the latest version without reinstalling or pressing refresh.
 *
 * Safety: if someone is typing or has text in a form, nothing is reloaded under them. A small
 * "A new version is ready. Update now" bar appears instead. Never reloads more than once per 30 seconds.
 */
(function () {
    var url = window.APP_UPDATE_URL;
    if (!url || !window.fetch) return;

    var INTERVAL = 60000;          // check once a minute while the app is open
    var LOCK_KEY = 'itsm-update-reload';
    var known = null;              // fingerprint of the version this page belongs to
    var checking = false;
    var bar = null;

    function hasUnsavedText() {
        var el = document.activeElement;
        if (el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName))) return true;
        var fields = document.querySelectorAll(
            'textarea, input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]):not([type=search])'
        );
        for (var i = 0; i < fields.length; i++) {
            if (fields[i].offsetParent !== null && String(fields[i].value || '').trim() !== '') return true;
        }
        return false;
    }

    function reloadNow() {
        try { sessionStorage.setItem(LOCK_KEY, String(Date.now())); } catch (e) {}
        window.location.reload();
    }

    function reloadedJustNow() {
        try { return Date.now() - Number(sessionStorage.getItem(LOCK_KEY) || 0) < 30000; } catch (e) { return false; }
    }

    function showBar() {
        if (bar || !document.body) return;
        bar = document.createElement('div');
        bar.setAttribute('role', 'status');
        bar.style.cssText = 'position:fixed;left:50%;transform:translateX(-50%);z-index:95;display:flex;align-items:center;gap:.75rem;' +
            'bottom:calc(env(safe-area-inset-bottom,0px) + 4.75rem);max-width:calc(100vw - 1.5rem);padding:.6rem .75rem .6rem 1rem;' +
            'border-radius:9999px;background:#1a6b3c;color:#fff;font:600 .875rem/1.2 inherit;box-shadow:0 10px 25px -8px rgba(0,0,0,.5);';
        var text = document.createElement('span');
        text.textContent = 'A new version is ready.';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Update now';
        btn.style.cssText = 'border:0;border-radius:9999px;background:#fff;color:#14532d;padding:.4rem .85rem;font:inherit;cursor:pointer;';
        btn.addEventListener('click', reloadNow);
        bar.appendChild(text);
        bar.appendChild(btn);
        document.body.appendChild(bar);
    }

    function onNewVersion() {
        if (reloadedJustNow()) return;              // never loop
        if (hasUnsavedText()) showBar(); else reloadNow();
    }

    function check() {
        if (checking || document.hidden) return;
        checking = true;
        fetch(url, { cache: 'no-store', credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || !data.v) return;
                if (known === null) { known = data.v; return; }      // first answer = the version we are showing
                if (data.v !== known) { known = data.v; onNewVersion(); }
            })
            .catch(function () { /* offline or a blip: try again next time */ })
            .then(function () { checking = false; });
    }

    document.addEventListener('visibilitychange', function () { if (!document.hidden) check(); });
    window.addEventListener('focus', check);
    window.addEventListener('pageshow', function (e) { if (e.persisted) check(); });
    setInterval(check, INTERVAL);
    check();
})();
