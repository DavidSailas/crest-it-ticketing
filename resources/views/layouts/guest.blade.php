@php
    // Drop the boss's office / server-room photo at public/images/login-bg.jpg and it is used automatically.
    // Until then, a CSS-drawn stand-in (bright office on the left, server rack on the right) is shown.
    $hasBgPhoto = file_exists(public_path('images/login-bg.jpg'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'IT Service Management System') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }

        /* Hide the browser's built-in "reveal password" eye (Edge / IE) — the form has its own toggle. */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear { display: none; }

        :root {
            --itsm-green-900: #0b3d27;
            --itsm-green-800: #0d4a2e;
            --itsm-green-700: #0f5f3a;
            --itsm-green-600: #1a6b3c;
            --itsm-green-100: #e8f3ed;
            --itsm-ink: #1d2b27;
            --itsm-muted: #5d6b66;
            --itsm-line: #d3dbd7;
        }

        .itsm-stage {
            position: relative;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: 'Poppins', 'Figtree', ui-sans-serif, system-ui, sans-serif;
            color: var(--itsm-ink);
            overflow: hidden;
            isolation: isolate;
        }

        /* ---------- Background ---------- */
        .itsm-bg { position: absolute; inset: 0; z-index: -1; }
        .itsm-bg--photo {
            background: #eaf3f0 url('{{ asset('images/login-bg.jpg') }}') center bottom / cover no-repeat;
        }
        .itsm-bg--fallback {
            background:
                radial-gradient(45% 40% at 18% 28%, rgba(255,255,255,.95), rgba(255,255,255,0) 70%),
                radial-gradient(30% 35% at 55% 55%, rgba(205,230,222,.75), rgba(205,230,222,0) 70%),
                linear-gradient(100deg, #f6faf9 0%, #e3f0eb 55%, #12363a 85%, #06181d 100%);
        }
        
        /* ---------- Page grid ---------- */
        .itsm-grid { display: grid; grid-template-columns: minmax(0, 1fr); min-height: inherit; }
        @media (min-width: 1024px) {
            .itsm-grid { grid-template-columns: minmax(0, 1fr) clamp(380px, 34vw, 540px); }
        }

        /* ---------- Left brand panel ---------- */
        .itsm-brand {
            display: none;
            position: relative;
            flex-direction: column;
            padding: clamp(1rem, 2.2vw, 2rem) 0 0 clamp(2rem, 5vw, 5rem);
        }
        @media (min-width: 1024px) { .itsm-brand { display: flex; } }

        .itsm-brand__logo { width: clamp(150px, 13vw, 230px); height: auto; margin-left: -0.25rem; }

        .itsm-brand__title {
            margin: clamp(.5rem, 1.2vw, 1.25rem) 0 0;
            font-size: clamp(1.6rem, 2.5vw, 2.6rem);
            line-height: 1.12;
            font-weight: 700;
            letter-spacing: -.015em;
            color: var(--itsm-green-800);
        }
        .itsm-brand__title span { display: block; font-weight: 400; }

        .itsm-brand__tagline {
            margin: clamp(.5rem, .9vw, .9rem) 0 0;
            display: flex; flex-wrap: wrap; align-items: center; gap: .25rem clamp(.9rem, 1.6vw, 1.6rem);
            font-size: clamp(.85rem, 1vw, 1.05rem);
            color: #243630;
        }
        .itsm-brand__tagline i { width: 2px; height: 1.1em; background: var(--itsm-green-600); opacity: .8; }

        .itsm-features {
            margin: clamp(1rem, 1.8vw, 1.75rem) 0 0;
            padding: 0; list-style: none;
            display: flex; flex-wrap: wrap; gap: .5rem;
            max-width: 46rem;
        }
        .itsm-features li {
            display: inline-flex; align-items: center; gap: .55rem;
            padding: .35rem .95rem .35rem .35rem;
            border-radius: 9999px;
            background: rgba(255,255,255,.93);
            box-shadow: 0 4px 14px -6px rgba(11,61,39,.35), 0 0 0 1px rgba(15,95,58,.1);
            font-size: clamp(.74rem, .85vw, .86rem);
            font-weight: 600; line-height: 1.2; white-space: nowrap;
            color: var(--itsm-green-900);
        }
        .itsm-features__icon {
            flex: none; width: 1.9rem; height: 1.9rem;
            border-radius: 9999px; background: var(--itsm-green-100);
            display: flex; align-items: center; justify-content: center;
            color: var(--itsm-green-700);
        }
        .itsm-features__icon svg { width: 58%; height: 58%; }

        /* bottom green swoosh with the partner statement */
        .itsm-partner {
            position: relative;
            margin-top: auto;
            margin-left: calc(-1 * clamp(2rem, 5vw, 5rem));
            margin-right: -3rem;
            padding: clamp(3.5rem, 7vw, 6rem) 2rem clamp(1rem, 2.2vw, 2rem) clamp(2rem, 5vw, 5rem);
            color: #fff;
        }
        /* the background photo already has its own green swoosh; the CSS wave is only used when there is no photo */
        .itsm-partner__wave { position: absolute; inset: 0; width: 100%; height: 100%; z-index: 0;
            -webkit-mask-image: linear-gradient(90deg, #000 0, #000 80%, transparent 100%); mask-image: linear-gradient(90deg, #000 0, #000 80%, transparent 100%); }
        .itsm-stage--photo .itsm-partner__wave { display: none; }

        /* Photo mode: the photo carries the green wave; a soft green backing keeps the text readable at any crop */
        .itsm-stage--photo .itsm-partner {
            background: linear-gradient(to top, rgba(11,61,39,.94) 0%, rgba(11,61,39,.72) 60%, rgba(11,61,39,0) 100%);
            -webkit-mask-image: linear-gradient(90deg, #000 0, #000 78%, transparent 100%); mask-image: linear-gradient(90deg, #000 0, #000 78%, transparent 100%);
        }

        .itsm-partner__body { position: relative; z-index: 1; display: flex; gap: 1.1rem; align-items: flex-start; max-width: 30rem; }
        .itsm-partner__shield { width: clamp(2rem, 2.6vw, 2.6rem); flex: none; margin-top: .15rem; }
        .itsm-partner h2 { margin: 0; font-size: clamp(.9rem, 1.1vw, 1.1rem); font-weight: 600; }
        .itsm-partner p { margin: .35rem 0 0; font-size: clamp(.75rem, .85vw, .9rem); line-height: 1.5; color: rgba(255,255,255,.9); }

        /* ---------- Watermark / credit ---------- */
        .itsm-credit {
            position: absolute; z-index: 2;
            right: clamp(1rem, 3.5vw, 4rem); bottom: clamp(.6rem, 1.4vw, 1.1rem);
            display: inline-flex; align-items: center; gap: .4rem;
            font-size: .72rem; letter-spacing: .02em;
            color: rgba(255,255,255,.82);
            text-shadow: 0 1px 2px rgba(5,35,30,.45);
            user-select: none; pointer-events: none;
        }
        .itsm-credit svg { width: .95rem; height: .95rem; opacity: .9; }
        .itsm-credit b { font-weight: 600; color: #fff; }
        @media (max-width: 1023px) {
            .itsm-credit { right: 0; left: 0; justify-content: center; color: rgba(255,255,255,.9); }
        }

        /* ---------- Right panel + card ---------- */
        .itsm-panel {
            display: flex; align-items: center; justify-content: center;
            padding: clamp(1rem, 3vw, 2rem) clamp(1rem, 3.5vw, 4rem) clamp(1rem, 3vw, 2rem) clamp(1rem, 2.5vw, 2rem);
        }
        .itsm-card {
            width: 100%;
            max-width: 29rem;
            background: #fff;
            border-radius: 1.5rem;
            padding: clamp(1.4rem, 2.2vw, 2rem) clamp(1.4rem, 2.4vw, 2.25rem) clamp(1rem, 1.4vw, 1.25rem);
            box-shadow: 0 40px 80px -24px rgba(5,35,30,.55), 0 8px 24px -8px rgba(5,35,30,.25), 0 0 0 1px rgba(255,255,255,.7);
        }
        .itsm-card__logo { display: block; width: clamp(110px, 8.5vw, 140px); height: auto; margin: 0 auto clamp(.25rem, .6vw, .5rem); }
        .itsm-card__foot {
            margin: clamp(.9rem, 1.4vw, 1.25rem) 0 0;
            text-align: center; font-size: .72rem; color: var(--itsm-muted);
        }
        .itsm-card__foot b { font-weight: 400; margin: 0 .5rem; color: var(--itsm-line); }

        /* shared brand-green focus state (also used by the other auth pages) */
        .itsm-card input:not([type="checkbox"]):focus {
            border-color: var(--itsm-green-600) !important;
            box-shadow: 0 0 0 3px rgba(26,107,60,.18) !important;
            outline: none !important;
        }
        .itsm-card input[type="checkbox"] { color: var(--itsm-green-700); }
        .itsm-card input[type="checkbox"]:focus-visible { box-shadow: 0 0 0 3px rgba(26,107,60,.25) !important; outline: none !important; }

        /* the other auth pages (forgot / reset / confirm) reuse the card: give their primary button the brand colour */
        .itsm-card .itsm-plain-slot button[type="submit"] { background-color: var(--itsm-green-700); }
        .itsm-card .itsm-plain-slot button[type="submit"]:hover { background-color: var(--itsm-green-900); }

        @media (max-width: 1023px) {
            .itsm-bg--fallback { background: radial-gradient(70% 45% at 50% 0%, #fff, rgba(255,255,255,0) 70%), linear-gradient(180deg, #e4f1ec 0%, #cfe4dc 100%); }
                        .itsm-panel { padding: 1rem; }
        }
    </style>
</head>
<body class="font-sans antialiased" style="margin:0;">
    <div class="itsm-stage {{ $hasBgPhoto ? 'itsm-stage--photo' : '' }}">
        <div class="itsm-bg {{ $hasBgPhoto ? 'itsm-bg--photo' : 'itsm-bg--fallback' }}" aria-hidden="true"></div>

        <div class="itsm-grid">

            {{-- LEFT: branding --}}
            <section class="itsm-brand" aria-label="About this system">
                <img class="itsm-brand__logo" src="{{ asset('images/logo-crest.png') }}" alt="Crest Forwarder Inc.">

                <h1 class="itsm-brand__title">IT Service Management <span>System</span></h1>

                <p class="itsm-brand__tagline">
                    <span>Better Service</span><i></i><span>Smarter Support</span><i></i><span>Stronger Together</span>
                </p>

                <ul class="itsm-features">
                    <li>
                        <span class="itsm-features__icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V7a2 2 0 012-2h1.5M15.5 5H17a2 2 0 012 2v12a2 2 0 01-2 2M9 3h6a1 1 0 011 1v2a1 1 0 01-1 1H9a1 1 0 01-1-1V4a1 1 0 011-1z"/></svg>
                        </span>
                        <span>Submit Tickets</span>
                    </li>
                    <li>
                        <span class="itsm-features__icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </span>
                        <span>Track Progress</span>
                    </li>
                    <li>
                        <span class="itsm-features__icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <span>Get Solutions</span>
                    </li>
                    <li>
                        <span class="itsm-features__icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25M6.75 11.25l3-3 2.25 2.25 4.5-4.5"/></svg>
                        </span>
                        <span>Improve IT Services</span>
                    </li>
                </ul>

                <div class="itsm-partner">
                    <svg class="itsm-partner__wave" viewBox="0 0 900 300" preserveAspectRatio="none" aria-hidden="true">
                        <defs>
                            <linearGradient id="itsmWave" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#0b3d27"/>
                                <stop offset="1" stop-color="#0f5f3a"/>
                            </linearGradient>
                        </defs>
                        <path d="M0 70 C 230 150 560 175 900 18 L900 300 L0 300 Z" fill="url(#itsmWave)"/>
                        <path d="M0 70 C 230 150 560 175 900 18" fill="none" stroke="#6fd05a" stroke-width="5" vector-effect="non-scaling-stroke"/>
                    </svg>

                    <div class="itsm-partner__body">
                        <svg class="itsm-partner__shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2.25c2.4 1.9 5.2 2.9 8.25 3 .2 1 .3 2 .3 3 0 5.6-3.3 10.2-8.55 12.75C6.75 18.45 3.45 13.85 3.45 8.25c0-1 .1-2 .3-3 3.05-.1 5.85-1.1 8.25-3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.5 11.9l2.5 2.5 4.6-5"/></svg>
                        <div>
                            <h2>IT Support</h2>
                            <p>We are committed to providing reliable IT support and innovative solutions to keep your business running.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- RIGHT: form card --}}
            <main class="itsm-panel">
                <div class="itsm-card">
                    <img class="itsm-card__logo" src="{{ asset('images/logo-crest.png') }}" alt="Crest Forwarder Inc.">

                    <div class="itsm-plain-slot">
                        {{ $slot }}
                    </div>

                    <p class="itsm-card__foot">ITSM v1.0<b>|</b>Crest Forwarder Inc.</p>
                </div>
            </main>

        </div>
        <div class="itsm-credit" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/></svg>
            <span>Developed by <b>JMSoneIT</b></span>
        </div>
    </div>
</body>
</html>
