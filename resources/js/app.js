import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Light / dark theme.
 * The saved choice lives in localStorage ("theme" = "light" | "dark"). With no saved choice the
 * operating system's setting wins. The <html class="dark"> flag itself is set before first paint
 * by the small script in layouts/app.blade.php, so there is no white flash on load.
 */
window.Theme = {
    isDark: () => document.documentElement.classList.contains('dark'),

    set(mode, { save = true } = {}) {
        const root = document.documentElement;
        root.classList.add('theme-fading');
        root.classList.toggle('dark', mode === 'dark');
        window.setTimeout(() => root.classList.remove('theme-fading'), 250);

        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.setAttribute('content', mode === 'dark' ? '#0f1419' : '#ffffff');

        if (save) {
            try { localStorage.setItem('theme', mode); } catch (e) { /* private mode: just don't remember it */ }
        }
        document.dispatchEvent(new CustomEvent('themechange', { detail: { mode } }));
    },

    toggle() { this.set(this.isDark() ? 'light' : 'dark'); },
};

// Follow the OS if the person never picked a theme themselves.
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    let saved = null;
    try { saved = localStorage.getItem('theme'); } catch (err) {}
    if (!saved) window.Theme.set(e.matches ? 'dark' : 'light', { save: false });
});

// Keep several open tabs in sync.
window.addEventListener('storage', (e) => {
    if (e.key === 'theme' && e.newValue) window.Theme.set(e.newValue, { save: false });
});

Alpine.start();
