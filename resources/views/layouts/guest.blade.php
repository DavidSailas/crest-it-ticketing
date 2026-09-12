<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'IT Service Ticketing') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex">

        {{-- LEFT: branded info panel --}}
        <div class="hidden lg:flex lg:w-[45%] relative overflow-hidden flex-col justify-between p-12 text-white"
             style="background: linear-gradient(160deg, #0c3320 0%, #1a6b3c 55%, #237a45 100%);">

            {{-- decorative dot grid --}}
            <div class="absolute inset-0 opacity-[0.08]"
                 style="background-image: radial-gradient(circle, #ffffff 1px, transparent 1px); background-size: 22px 22px;"></div>

            {{-- decorative glow circles --}}
            <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full" style="background: radial-gradient(circle, rgba(255,255,255,0.12), transparent 70%);"></div>
            <div class="absolute bottom-0 -left-16 w-64 h-64 rounded-full" style="background: radial-gradient(circle, rgba(255,255,255,0.08), transparent 70%);"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-14">
                    <div class="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h.01M15 12h.01M9 16h.01M15 16h.01M4 7h16a1 1 0 011 1v2a2 2 0 000 4v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2a2 2 0 000-4V8a1 1 0 011-1z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold tracking-wide">CREST FORWARDER INC.</span>
                </div>

                <h1 class="text-4xl font-extrabold leading-tight mb-4">IT Service<br>Ticketing System</h1>
                <p class="text-green-100/90 text-base max-w-sm">A single place for the team to raise, track, and resolve IT support requests — fast.</p>
            </div>

            <div class="relative z-10 space-y-5">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 shrink-0 rounded-md bg-white/15 flex items-center justify-center mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Submit a request</p>
                        <p class="text-green-100/70 text-xs mt-0.5">Log an issue in seconds, from anywhere.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 shrink-0 rounded-md bg-white/15 flex items-center justify-center mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Track progress</p>
                        <p class="text-green-100/70 text-xs mt-0.5">See status updates in real time.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 shrink-0 rounded-md bg-white/15 flex items-center justify-center mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Fast resolution</p>
                        <p class="text-green-100/70 text-xs mt-0.5">IT support picks it up and closes the loop.</p>
                    </div>
                </div>
            </div>

            <p class="relative z-10 text-xs text-green-100/50">&copy; {{ date('Y') }} Crest Forwarder Inc.</p>
        </div>

        {{-- RIGHT: form --}}
        <div class="flex-1 flex items-center justify-center p-6" style="background-color:#f7f8f9;">
            <div class="w-full max-w-sm">

                <div class="lg:hidden text-center mb-8">
                    <p class="text-xs font-semibold tracking-widest uppercase" style="color:#1a6b3c;">Crest Forwarder Inc.</p>
                    <h1 class="text-2xl font-bold text-gray-800 mt-1">IT Service Ticketing</h1>
                </div>

                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 px-8 py-10">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-5" style="background-color:#e7f3ec;">
                        <svg class="w-5 h-5" style="color:#1a6b3c;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    {{ $slot }}
                </div>

                <p class="text-center text-xs text-gray-400 mt-6 lg:hidden">
                    &copy; {{ date('Y') }} Crest Forwarder Inc. Internal use only.
                </p>
            </div>
        </div>

    </div>
</body>
</html>
