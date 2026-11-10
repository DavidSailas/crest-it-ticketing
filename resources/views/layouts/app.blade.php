<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="theme-color" content="#ffffff">
        <meta name="color-scheme" content="light dark">
        {{-- Pick light/dark BEFORE the page paints, so a dark-mode user never sees a white flash. --}}
        <script>
            (function () {
                try {
                    var saved = localStorage.getItem('theme');
                    var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (dark) {
                        document.documentElement.classList.add('dark');
                        document.querySelector('meta[name="theme-color"]').setAttribute('content', '#0f1419');
                    }
                } catch (e) {}
            })();
        </script>

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        @include('partials.pwa-head')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Live lists: filter / page a table without reloading the page. */
            [data-live-list] { transition: opacity .15s ease; }
            [data-live-list].live-busy { opacity: .55; pointer-events: none; }
            #live-bar { position: fixed; top: 0; left: 0; z-index: 60; height: 3px; width: 0; opacity: 0;
                background: #1a6b3c; box-shadow: 0 0 8px rgba(26, 107, 60, .5); pointer-events: none;
                transition: opacity .2s ease; }
            #live-bar.on { opacity: 1; width: 85%; transition: width 8s cubic-bezier(.1, .7, .2, 1), opacity .1s ease; }
            [data-live-scroll] { scroll-margin-top: 1rem; }
            /* A control that is currently narrowing the list. */
            .filter-active { border-color: #1a6b3c !important; background-color: #f0f9f3 !important; }
            .filter-field:has(.filter-active) > label { color: #15803d; }
        </style>
        @if(config('features.announcements'))
            @include('announcements._styles')
        @endif
        <script src="{{ asset('js/tile-strip.js') }}?v={{ @filemtime(public_path('js/tile-strip.js')) }}" defer></script>
        <script src="{{ asset('js/live-list.js') }}?v={{ @filemtime(public_path('js/live-list.js')) }}" defer></script>
        @if(config('features.announcements'))
            <script src="{{ asset('js/announcements-live.js') }}?v={{ @filemtime(public_path('js/announcements-live.js')) }}" defer></script>
        @endif
    </head>
    <body class="font-sans antialiased has-mnav">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="app-header bg-white shadow">
                    <div class="max-w-[96rem] mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            {{-- People without an email can't receive ticket updates — nudge them once, everywhere except Profile. --}}
            @auth
                @if(! request()->routeIs('profile.*') && ! auth()->user()->hasVerifiedEmail())
                    <div class="border-b border-amber-100 bg-amber-50">
                        <div class="mx-auto flex max-w-[96rem] items-center justify-between gap-3 px-4 py-2.5 text-sm text-amber-900 sm:px-6 lg:px-8">
                            @if(blank(auth()->user()->email))
                                <span>Add your email address to get updates when your tickets are resolved or someone replies.</span>
                                <a href="{{ route('profile.edit') }}#email-notifications" class="shrink-0 font-semibold underline">Add email</a>
                            @else
                                <span>Please confirm your email — we sent a link to <span class="font-medium">{{ auth()->user()->email }}</span>. Updates start once you press it.</span>
                                <a href="{{ route('profile.edit') }}#email-notifications" class="shrink-0 font-semibold underline">Resend or change</a>
                            @endif
                        </div>
                    </div>
                @endif
            @endauth

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            {{-- "What's new" pop-up + the live poll that feeds it new announcements --}}
            @if(config('features.announcements'))
                @auth
                    @include('announcements._popup')
                    @include('announcements._live')
                @endauth
            @endif
        </div>

        @include('partials.pwa-install')
    </body>
</html>
