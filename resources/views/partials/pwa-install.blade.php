{{-- "Install the app" sheet. Controlled by public/js/pwa.js, which shows exactly ONE of the three views:
       android : the browser can install it with one tap        (Chrome, Edge, Samsung Internet ...)
       ios     : Safari / iOS has no install button, so we show the 3 steps
       inapp   : opened inside Facebook / Messenger / Instagram ... which can't install apps -> "open in your browser"
     Nothing is shown when the app is already installed or the browser can't install it. --}}
<div id="pwa-install" class="pwa-sheet" role="dialog" aria-modal="true" aria-labelledby="pwa-title" hidden>
    <div class="pwa-sheet__backdrop" data-pwa-action="dismiss"></div>

    <div class="pwa-sheet__panel" tabindex="-1">
        <span class="pwa-sheet__grab" aria-hidden="true"></span>

        <button type="button" class="pwa-sheet__close" data-pwa-action="dismiss" aria-label="Close">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="pwa-sheet__head">
            <img class="pwa-sheet__icon" src="{{ asset('icons/icon-192.png') }}" alt="" width="64" height="64">
            <div class="min-w-0">
                <h2 id="pwa-title" class="pwa-sheet__title">Install ITSM</h2>
                <p class="pwa-sheet__host">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    {{ request()->getHost() }}
                </p>
            </div>
        </div>

        <ul class="pwa-sheet__perks">
            <li>
                <span class="pwa-sheet__perk-ico"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg></span>
                <span><b>One tap away</b><small>Open it from your home screen like any other app.</small></span>
            </li>
            <li>
                <span class="pwa-sheet__perk-ico"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg></span>
                <span><b>Full screen</b><small>No browser bars, so there is more room for your tickets.</small></span>
            </li>
            <li>
                <span class="pwa-sheet__perk-ico"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg></span>
                <span><b>Always up to date</b><small>Updates itself. Nothing to download from an app store.</small></span>
            </li>
        </ul>

        {{-- View 1: Android / Chrome / Edge --}}
        <div data-pwa-view="android" hidden>
            <button type="button" class="pwa-sheet__cta" data-pwa-action="install">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                Install app
            </button>
        </div>

        {{-- View 2: iPhone / iPad --}}
        <div data-pwa-view="ios" hidden>
            <ol class="pwa-sheet__steps">
                <li>
                    <span class="pwa-sheet__n">1</span>
                    <span>Tap the <b>Share</b> button
                        <svg class="pwa-sheet__inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-label="Share"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25H15M9 12l3-3m0 0l3 3m-3-3v12.75"/></svg>
                        in the browser bar.</span>
                </li>
                <li>
                    <span class="pwa-sheet__n">2</span>
                    <span>Scroll down and tap <b>Add to Home Screen</b>
                        <svg class="pwa-sheet__inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="3"/><path stroke-linecap="round" d="M12 8.5v7M8.5 12h7"/></svg>.</span>
                </li>
                <li>
                    <span class="pwa-sheet__n">3</span>
                    <span>Tap <b>Add</b>. ITSM now appears on your home screen.</span>
                </li>
            </ol>
        </div>

        {{-- View 3: inside Facebook / Messenger / Instagram ... --}}
        <div data-pwa-view="inapp" hidden>
            <div class="pwa-sheet__note">
                <b>Open this page in your browser first.</b>
                <span>Apps like Messenger and Facebook can't install websites. Tap <b>&#8943;</b> or <b>Share</b> and choose <b>Open in browser</b> (Chrome or Safari), or copy the link.</span>
            </div>
            <button type="button" class="pwa-sheet__cta" data-pwa-action="copy">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/></svg>
                <span data-pwa-copy-label>Copy link</span>
            </button>
        </div>

        <button type="button" class="pwa-sheet__later" data-pwa-action="dismiss">Not now</button>
        <p class="pwa-sheet__fine">Free &middot; Takes a few seconds &middot; Remove any time</p>
    </div>
</div>
