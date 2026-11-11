/*
 * ITSM service worker.
 *
 * Deliberately small and safe for a login-protected app:
 *  - pages are NEVER cached (they hold private ticket data and CSRF tokens) — they always come from the network;
 *  - if the network is down, a friendly offline page is shown instead of the browser's dino;
 *  - static files (compiled CSS/JS, logos, icons) are cached so the app opens fast, and refreshed in the background.
 * Bump VERSION to force every device to drop its old cache.
 */
const VERSION = 'itsm-v2';
const STATIC_CACHE = VERSION + '-static';
const SCOPE = self.registration.scope;                       // e.g. https://itsm.example.com/
const OFFLINE_URL = new URL('offline.html', SCOPE).href;
const PRECACHE = [
    OFFLINE_URL,
    new URL('icons/icon-192.png', SCOPE).href,
    new URL('images/logo-light.png', SCOPE).href,
    new URL('images/logo-dark.png', SCOPE).href,
];
const STATIC_PREFIXES = ['build/', 'images/', 'icons/', 'js/', 'css/'].map((p) => new URL(p, SCOPE).pathname);

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k.startsWith('itsm-') && k !== STATIC_CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;                          // form posts, ticket replies, polling POSTs: untouched

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;           // fonts, CDNs, the Vite dev server: untouched

    // Page loads: network first, offline page as the fallback.
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    // Static assets: serve the cached copy at once, refresh it in the background.
    if (STATIC_PREFIXES.some((p) => url.pathname.startsWith(p))) {
        event.respondWith(
            caches.open(STATIC_CACHE).then((cache) =>
                cache.match(req).then((cached) => {
                    const refresh = fetch(req).then((res) => {
                        if (res && res.ok) cache.put(req, res.clone());
                        return res;
                    }).catch(() => cached);
                    return cached || refresh;
                })
            )
        );
    }
    // Everything else (JSON polling, downloads, avatars…) goes straight to the network.
});
