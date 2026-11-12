{{-- Makes the site installable (Add to Home Screen / Install app). Included in both layouts. --}}
<link rel="manifest" href="{{ route('pwa.manifest') }}" crossorigin="use-credentials">
<meta name="application-name" content="ITSM">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="ITSM">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="format-detection" content="telephone=no">
<link rel="stylesheet" href="{{ asset('css/mobile-shell.css') }}?v={{ @filemtime(public_path('css/mobile-shell.css')) }}">
<script>window.PWA_CONFIG = { sw: @json(url('sw.js')) };</script>
<script src="{{ asset('js/pwa.js') }}?v={{ @filemtime(public_path('js/pwa.js')) }}" defer></script>
{{-- Refreshes an open app by itself after a new version is deployed (see public/js/app-update.js and the /app-version route). --}}
<script>window.APP_UPDATE_URL = @json(route('app.version', [], false));</script>
<script src="{{ asset('js/app-update.js') }}?v={{ @filemtime(public_path('js/app-update.js')) }}" defer></script>
