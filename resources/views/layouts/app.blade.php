<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

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
        <script src="{{ asset('js/live-list.js') }}?v={{ @filemtime(public_path('js/live-list.js')) }}" defer></script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-[96rem] mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
