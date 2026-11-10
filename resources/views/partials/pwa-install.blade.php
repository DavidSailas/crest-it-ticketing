{{-- "Install this app" banner. Shown by public/js/pwa.js only when the browser can install the app. --}}
<div id="pwa-install" class="pwa-install" role="dialog" aria-label="Install ITSM" hidden>
    <img class="pwa-install__icon" src="{{ asset('icons/icon-192.png') }}" alt="" width="44" height="44">

    <div class="pwa-install__text">
        <p class="pwa-install__title">Install ITSM</p>

        {{-- Android, Chrome, Edge: the browser can install it straight away --}}
        <p class="pwa-install__sub" data-pwa-android hidden>Open it from your home screen, like an app.</p>

        {{-- iPhone / iPad: Safari only installs through the Share menu --}}
        <p class="pwa-install__sub" data-pwa-ios hidden>
            Tap
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-label="Share"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25H15M9 12l3-3m0 0l3 3m-3-3v12.75"/></svg>
            <b>Share</b>, then <b>Add to Home Screen</b>.
        </p>
    </div>

    <button type="button" class="pwa-install__btn" data-pwa-action="install" data-pwa-android hidden>Install</button>

    <button type="button" class="pwa-install__close" data-pwa-action="dismiss" aria-label="Not now">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
