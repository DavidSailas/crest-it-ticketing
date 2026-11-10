{{-- Makes the site installable (Add to Home Screen / Install app). Included in both layouts. --}}
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="application-name" content="ITSM">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="ITSM">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="format-detection" content="telephone=no">
<link rel="stylesheet" href="{{ asset('css/mobile-shell.css') }}?v={{ @filemtime(public_path('css/mobile-shell.css')) }}">
<script>window.PWA_CONFIG = { sw: @json(url('sw.js')) };</script>
<script src="{{ asset('js/pwa.js') }}?v={{ @filemtime(public_path('js/pwa.js')) }}" defer></script>
