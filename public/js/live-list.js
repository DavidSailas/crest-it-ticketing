/*
 * Live lists — filter and page a table without reloading the whole page.
 *
 * Markup contract (all optional, progressive enhancement):
 *   <form data-live-filter>        a GET filter form. Typing / choosing refreshes the results.
 *   <div  data-live-list id="x">   a region that is swapped with the same id from the server response.
 *   <a    data-live-link>          a link outside a region that should also refresh in place.
 *   <a    data-live-clear>         "Clear" control; shown only while a filter is active.
 *   <a    data-follow-query>       an export link whose query string follows the current filters.
 *   <a    data-follow-filters>     a tab link that keeps its own ?tab= but carries the current filters.
 *   [data-live-scroll]             where to scroll to when the page number changes.
 *
 * Without JavaScript everything still works as ordinary page loads.
 */
(function () {
    'use strict';

    if (window.__liveList) return;
    window.__liveList = true;

    var DEBOUNCE_MS = 350;
    var SCROLL_TTL_MS = 30 * 60 * 1000;

    var controller = null;
    var timer = null;
    var seq = 0;

    function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
    function regions() { return qsa('[data-live-list][id]'); }
    function forms() { return qsa('form[data-live-filter]'); }

    /* ---------- URL helpers ---------- */

    function formUrl(form) {
        var url = new URL(form.getAttribute('action') || location.pathname, location.origin);
        var params = new URLSearchParams();
        new FormData(form).forEach(function (value, key) {
            if (typeof value === 'string' && value.trim() !== '') params.append(key, value.trim());
        });
        url.search = params.toString();
        return url;
    }

    function samePage(a, b) {
        return a.pathname === b.pathname && a.search === b.search;
    }

    function isFilterControl(el) {
        return el.name && el.type !== 'hidden' && el.type !== 'submit' && el.type !== 'button' && el.type !== 'file';
    }

    /* ---------- Form state ---------- */

    function hasActiveFilter(form) {
        return qsa('[name]', form).some(function (el) {
            return isFilterControl(el) && String(el.value).trim() !== '';
        });
    }

    function refreshFormState(form) {
        qsa('[name]', form).forEach(function (el) {
            if (!isFilterControl(el)) return;
            el.classList.toggle('filter-active', String(el.value).trim() !== '');
        });
        // Date ranges (x_from / x_to): keep each end within the other.
        qsa('input[type="date"][name$="_from"]', form).forEach(function (from) {
            var to = form.querySelector('input[name="' + from.name.replace(/_from$/, '_to') + '"]');
            if (!to) return;
            to.min = from.value || '';
            from.max = to.value || '';
        });
        var active = hasActiveFilter(form);
        qsa('[data-live-clear]').forEach(function (el) {
            el.style.display = active ? '' : 'none';
        });
    }

    // After the URL changes by link / back button, make the controls match it.
    function syncForm(form, url) {
        qsa('[name]', form).forEach(function (el) {
            if (!isFilterControl(el) || el === document.activeElement) return;
            el.value = url.searchParams.get(el.name) || '';
        });
        refreshFormState(form);
    }

    function followQuery(url) {
        qsa('a[data-follow-query]').forEach(function (a) {
            var target = new URL(a.getAttribute('href'), location.origin);
            var params = new URLSearchParams(url.search);
            params.delete('page');
            target.search = params.toString();
            a.setAttribute('href', target.pathname + target.search);
        });

        // Tab links keep their own "tab" but carry the current search / filters along.
        qsa('a[data-follow-filters]').forEach(function (a) {
            var target = new URL(a.getAttribute('href'), location.origin);
            var tab = target.searchParams.get('tab');
            var params = new URLSearchParams(url.search);
            params.delete('page');
            if (tab !== null) params.set('tab', tab);
            target.search = params.toString();
            a.setAttribute('href', target.pathname + target.search);
        });
    }

    /* ---------- Loading state ---------- */

    function bar() {
        var el = document.getElementById('live-bar');
        if (!el) {
            el = document.createElement('div');
            el.id = 'live-bar';
            document.body.appendChild(el);
        }
        return el;
    }

    function busy(on) {
        regions().forEach(function (el) {
            el.classList.toggle('live-busy', on);
            el.setAttribute('aria-busy', on ? 'true' : 'false');
        });
        bar().classList.toggle('on', on);
    }

    /* ---------- Loading + swapping ---------- */

    function load(url, opts) {
        opts = opts || {};
        if (controller) controller.abort();
        controller = new AbortController();
        var mine = ++seq;
        var current = new URL(location.href);
        var pageChanged = (url.searchParams.get('page') || '1') !== (current.searchParams.get('page') || '1');

        busy(true);

        return fetch(url.pathname + url.search, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin',
            signal: controller.signal
        }).then(function (res) {
            if (mine !== seq) return null;
            // Session expired or redirected elsewhere: do a normal navigation.
            if (!res.ok || new URL(res.url).pathname !== url.pathname) {
                location.href = url.href;
                return null;
            }
            return res.text();
        }).then(function (html) {
            if (html === null || mine !== seq) return;

            var doc = new DOMParser().parseFromString(html, 'text/html');
            var swapped = 0;
            regions().forEach(function (el) {
                var fresh = doc.getElementById(el.id);
                if (fresh) {
                    el.innerHTML = fresh.innerHTML;
                    swapped++;
                }
            });
            if (!swapped) { location.href = url.href; return; }

            if (opts.history === 'push' && !samePage(url, current)) {
                history.pushState({ live: true }, '', url.pathname + url.search);
            } else if (opts.history === 'replace') {
                history.replaceState({ live: true }, '', url.pathname + url.search);
            }

            forms().forEach(function (form) {
                if (opts.sync) syncForm(form, url); else refreshFormState(form);
            });
            followQuery(url);

            if (pageChanged) {
                var anchor = document.querySelector('[data-live-scroll]');
                if (anchor) anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }).catch(function (err) {
            if (err && err.name === 'AbortError') return;
            location.href = url.href; // network hiccup: fall back to a normal load
        }).then(function () {
            if (mine === seq) busy(false);
        });
    }

    /* ---------- Filter forms ---------- */

    function onFormInput(e) {
        var form = e.target.closest && e.target.closest('form[data-live-filter]');
        if (!form || !isFilterControl(e.target)) return;

        refreshFormState(form);
        clearTimeout(timer);

        var isText = e.target.tagName === 'INPUT' && /^(text|search|email|tel|url)$/.test(e.target.type || 'text');
        if (e.type === 'input' && !isText) return;   // selects / dates wait for "change"
        if (e.type === 'change' && isText) return;   // text already handled while typing

        var run = function () { load(formUrl(form), { history: isText ? 'replace' : 'push' }); };
        if (isText) timer = setTimeout(run, DEBOUNCE_MS); else run();
    }

    document.addEventListener('input', onFormInput);
    document.addEventListener('change', onFormInput);

    // Enter in a field applies immediately instead of submitting the page.
    document.addEventListener('submit', function (e) {
        var form = e.target.closest && e.target.closest('form[data-live-filter]');
        if (!form) return;
        e.preventDefault();
        clearTimeout(timer);
        load(formUrl(form), { history: 'push' });
    });

    /* ---------- Links (pagination, quick filters, clear) ---------- */

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || a.target || a.hasAttribute('download') || a.hasAttribute('data-no-live')) return;

        var inRegion = a.closest('[data-live-list]') !== null;
        if (!inRegion && !a.hasAttribute('data-live-link') && !a.hasAttribute('data-live-clear')) {
            rememberScroll();   // leaving for another page (edit, view...) — remember where we were
            return;
        }

        var url = new URL(a.href, location.href);
        if (url.origin !== location.origin || url.pathname !== location.pathname) {
            rememberScroll();
            return;
        }
        e.preventDefault();
        load(url, { history: 'push', sync: true });
    });

    window.addEventListener('popstate', function () {
        if (!regions().length) return;   // not a list page (e.g. an in-page #anchor)
        load(new URL(location.href), { history: 'none', sync: true });
    });

    /* ---------- Keep scroll position across edit / row actions ---------- */

    function scrollKey() { return 'live-scroll:' + location.pathname + location.search; }

    function rememberScroll() {
        if (!regions().length) return;   // only list pages need this
        try {
            sessionStorage.setItem(scrollKey(), JSON.stringify({ y: window.scrollY, t: Date.now() }));
        } catch (e) { /* storage unavailable — not critical */ }
    }

    function restoreScroll() {
        try {
            var raw = sessionStorage.getItem(scrollKey());
            if (!raw) return;
            sessionStorage.removeItem(scrollKey());
            var saved = JSON.parse(raw);
            if (saved && Date.now() - saved.t < SCROLL_TTL_MS) {
                window.scrollTo(0, saved.y);
            }
        } catch (e) { /* ignore */ }
    }

    // Row actions (VIP toggle, suspend, delete...) post and redirect back: keep the scroll spot.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        // Confirm dialogs are teleported out of the list, so key off "this page has a live list".
        if (form.matches && !form.matches('form[data-live-filter]') && document.querySelector('[data-live-list]')) {
            rememberScroll();
        }
    }, true);

    /* ---------- Init ---------- */

    function init() {
        forms().forEach(refreshFormState);
        restoreScroll();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();

    // Back/forward cache: re-sync when the page is restored from memory.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) forms().forEach(refreshFormState);
    });
})();
