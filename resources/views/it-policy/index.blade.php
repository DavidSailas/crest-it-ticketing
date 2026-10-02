@php
    // Heroicons (outline) path data, keyed by the "icon" names used in config/it_policy.php
    $icons = [
        'lock'     => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
        'key'      => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
        'shield'   => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'box'      => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'mail'     => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'alert'    => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'download' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
        'printer'  => 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z',
        'search'   => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
        'check'    => 'M5 13l4 4L19 7',
    ];

    // Soft, low-saturation accents (easy on the eyes). Sections cycle through
    // them; each "Key rule" card reuses the colour of the section it links to.
    // Inline styles are used so no Tailwind rebuild is needed.
    $palette = [
        ['accent' => '#1a6b3c', 'tint' => '#e8f3ec'], // brand green
        ['accent' => '#2b6cb0', 'tint' => '#e8f0fa'], // calm blue
        ['accent' => '#0f766e', 'tint' => '#e3f3f1'], // teal
        ['accent' => '#6b5bb5', 'tint' => '#eeebf8'], // soft violet
        ['accent' => '#b7791f', 'tint' => '#fbf1de'], // warm amber
        ['accent' => '#b4546a', 'tint' => '#fbebee'], // muted rose
    ];
    $tone = fn ($n) => $palette[(((int) $n) - 1) % count($palette)];

    // Lightweight search index: one lowercase string per section.
    $searchIndex = collect($policy['sections'])->map(function ($s) {
        $parts = [$s['number'], $s['title'], $s['body'] ?? ''];
        foreach ($s['items'] ?? [] as $item) {
            $parts[] = $item;
        }
        foreach ($s['groups'] ?? [] as $group) {
            $parts[] = $group['label'];
            foreach ($group['items'] as $item) {
                $parts[] = $item;
            }
        }

        return [
            'id'   => 'section-'.$s['number'],
            'text' => \Illuminate\Support\Str::lower(implode(' ', $parts)),
        ];
    })->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">IT Policy</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $policy['company'] }} &mdash; {{ $policy['title'] }}</p>
            </div>
            <div class="flex items-center gap-2 print:hidden">
                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['printer'] }}"/></svg>
                    Print
                </button>
                <a href="{{ route('it-policy.download') }}"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-white text-sm font-semibold shadow-sm hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-green-700 transition"
                    style="background-color:#1a6b3c;">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['download'] }}"/></svg>
                    Download PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="itPolicy({{ \Illuminate\Support\Js::from($searchIndex) }})" x-init="init()">

        <div class="lg:flex lg:items-start lg:gap-8">

            {{-- Sidebar: search + contents --}}
            <aside class="lg:w-64 lg:shrink-0 mb-6 lg:mb-0 print:hidden">
                <div class="lg:sticky lg:top-6 space-y-3">
                    <div class="relative">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['search'] }}"/></svg>
                        <input type="search" x-model="query" @keydown.escape="query = ''" placeholder="Search the policy"
                            aria-label="Search the IT Policy"
                            class="w-full pl-9 rounded-lg border-gray-300 bg-white text-sm focus:border-green-700 focus:ring-green-700">
                    </div>

                    {{-- Small screens: jump-to menu --}}
                    <select class="lg:hidden w-full rounded-lg border-gray-300 bg-white text-sm focus:border-green-700 focus:ring-green-700"
                            aria-label="Jump to a section"
                            @change="go($event.target.value); $event.target.selectedIndex = 0">
                        <option value="" selected disabled>Jump to a section</option>
                        @foreach($policy['sections'] as $section)
                            <option value="section-{{ $section['number'] }}">{{ $section['number'] }}. {{ $section['title'] }}</option>
                        @endforeach
                    </select>

                    {{-- Large screens: contents list that follows your reading position --}}
                    <nav class="hidden lg:block bg-white rounded-xl border border-gray-100 shadow-sm p-2 max-h-[75vh] overflow-y-auto" aria-label="Policy contents">
                        <p class="px-3 pt-2 pb-1 text-sm font-semibold text-gray-700">Contents</p>
                        <ul class="space-y-0.5">
                            @foreach($policy['sections'] as $section)
                                <li x-show="matches('section-{{ $section['number'] }}')">
                                    <a href="#section-{{ $section['number'] }}"
                                       @click.prevent="go('section-{{ $section['number'] }}')"
                                       :aria-current="active === 'section-{{ $section['number'] }}' ? 'true' : null"
                                       :class="active === 'section-{{ $section['number'] }}' ? 'bg-green-50 text-green-800 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'"
                                       class="flex items-baseline gap-2 px-3 py-1.5 rounded-lg text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700">
                                        <span class="w-5 shrink-0 text-xs font-semibold tabular-nums" style="color:{{ $tone($section['number'])['accent'] }};">{{ $section['number'] }}</span>
                                        <span class="truncate">{{ $section['title'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </div>
            </aside>

            {{-- Main content --}}
            <div class="flex-1 min-w-0 space-y-6">

                {{-- Document details --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6" style="border-top:4px solid #1a6b3c;" x-show="!query.trim()">
                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4">
                        @foreach($policy['meta'] as $label => $value)
                            <div class="min-w-0">
                                <dt class="text-xs text-gray-500">{{ $label }}</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-800 break-words">
                                    @if(filled($value))
                                        {{ $value }}
                                    @else
                                        <span class="font-normal italic text-gray-400">Not set yet</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Key rules at a glance --}}
                <div x-show="!query.trim()">
                    <h3 class="text-base font-semibold text-gray-800">Key rules at a glance</h3>
                    <p class="text-sm text-gray-500 mt-0.5 mb-3">The essentials every employee should know. Select a card to read the full section.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                        @foreach($policy['highlights'] as $item)
                            @php $c = $tone($item['section']); @endphp
                            <a href="#section-{{ $item['section'] }}" @click.prevent="go('section-{{ $item['section'] }}')"
                               style="border-left:4px solid {{ $c['accent'] }};"
                               class="group flex items-start gap-3 bg-white rounded-xl border border-gray-100 shadow-sm p-4 hover:shadow-md transition focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700">
                                <span class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center" style="color:{{ $c['accent'] }}; background-color:{{ $c['tint'] }};">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] }}"/></svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-gray-800">{{ $item['title'] }}</span>
                                    <span class="block text-sm text-gray-500 mt-0.5">{{ $item['text'] }}</span>
                                    <span class="block text-xs font-medium mt-1.5 group-hover:underline" style="color:{{ $c['accent'] }};">Section {{ $item['section'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Search status --}}
                <p x-cloak x-show="query.trim()" class="text-sm text-gray-500" aria-live="polite">
                    <span x-text="visibleCount"></span>
                    <span x-text="visibleCount === 1 ? 'section matches' : 'sections match'"></span>
                    &ldquo;<span class="font-medium text-gray-700" x-text="query.trim()"></span>&rdquo;
                </p>

                {{-- Policy sections --}}
                @foreach($policy['sections'] as $section)
                    @php $c = $tone($section['number']); @endphp
                    <section id="section-{{ $section['number'] }}" data-policy-section
                             x-show="matches('section-{{ $section['number'] }}')"
                             style="border-left:5px solid {{ $c['accent'] }};"
                             class="scroll-mt-6 bg-white rounded-xl border border-gray-100 shadow-sm p-6 print:shadow-none print:break-inside-avoid">
                        <div class="flex items-start gap-4">
                            <span class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center text-sm font-semibold text-white tabular-nums" style="background-color:{{ $c['accent'] }};">{{ $section['number'] }}</span>

                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-semibold leading-9" style="color:{{ $c['accent'] }};">{{ $section['title'] }}</h3>

                                @if($section['type'] === 'list')
                                    <ul class="mt-2 space-y-2.5">
                                        @foreach($section['items'] as $item)
                                            <li class="flex items-start gap-3 text-sm leading-relaxed text-gray-600">
                                                <svg class="w-4 h-4 mt-0.5 shrink-0" style="color:{{ $c['accent'] }};" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['check'] }}"/></svg>
                                                <span>{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @elseif($section['type'] === 'groups')
                                    <div class="mt-2 space-y-4">
                                        @foreach($section['groups'] as $group)
                                            <div>
                                                <p class="text-sm font-medium text-gray-600">{{ $group['label'] }}</p>
                                                <ul class="mt-2 flex flex-wrap gap-2">
                                                    @foreach($group['items'] as $item)
                                                        <li class="px-3 py-1 rounded-full text-sm" style="background-color:{{ $c['tint'] }}; color:{{ $c['accent'] }};">{{ $item }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-2 text-sm leading-relaxed text-gray-600 max-w-prose">{{ $section['body'] }}</p>
                                @endif

                                @if(!empty($section['cta']))
                                    <a href="{{ route('tickets.create') }}"
                                       class="mt-4 inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border text-sm font-medium text-green-800 border-green-200 bg-green-50 hover:bg-green-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700 transition print:hidden">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['alert'] }}"/></svg>
                                        Report an issue to IT
                                    </a>
                                @endif
                            </div>
                        </div>
                    </section>
                @endforeach

                {{-- No results --}}
                <div x-cloak x-show="query.trim() && visibleCount === 0" class="bg-white rounded-xl border border-gray-100 shadow-sm p-10 text-center">
                    <p class="text-sm font-medium text-gray-700">Nothing in the policy matches your search.</p>
                    <p class="text-sm text-gray-500 mt-1">Try a different word, such as &ldquo;password&rdquo;, &ldquo;backup&rdquo; or &ldquo;software&rdquo;.</p>
                    <button type="button" @click="query = ''"
                        class="mt-4 px-3.5 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700 transition">
                        Clear search
                    </button>
                </div>

                <div x-show="!query.trim()" class="space-y-6">
                    {{-- Summary --}}
                    <div class="rounded-xl border border-green-100 p-6" style="background-color:#eef6f1; border-left:5px solid #1a6b3c;">
                        <h3 class="text-base font-semibold text-gray-800">Summary</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-700 max-w-prose">{{ $policy['summary'] }}</p>
                    </div>

                    {{-- Acknowledgement --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 print:break-inside-avoid" style="border-left:5px solid #b7791f;">
                        <h3 class="text-base font-semibold" style="color:#b7791f;">Employee acknowledgement</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 max-w-prose">{{ $policy['acknowledgement']['intro'] }}</p>

                        <ul class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2.5">
                            @foreach($policy['acknowledgement']['items'] as $item)
                                <li class="flex items-start gap-3 text-sm leading-relaxed text-gray-600">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0" style="color:#1a6b3c;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['check'] }}"/></svg>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-5 pt-5 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center gap-3 print:hidden">
                            <a href="{{ route('it-policy.download') }}"
                               class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-lg text-white text-sm font-semibold shadow-sm hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-green-700 transition"
                               style="background-color:#1a6b3c;">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['download'] }}"/></svg>
                                Download PDF with the form
                            </a>
                            <p class="text-sm text-gray-500">Print the form, sign it, and give it to IT / HR.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function itPolicy(index) {
            return {
                query: '',
                active: index.length ? index[0].id : '',
                index,

                // True when the section should be shown for the current search.
                matches(id) {
                    const q = this.query.trim().toLowerCase();
                    if (!q) return true;
                    const entry = this.index.find(i => i.id === id);
                    return !!entry && entry.text.includes(q);
                },

                get visibleCount() {
                    return this.index.filter(i => this.matches(i.id)).length;
                },

                go(id) {
                    if (!id) return;
                    const el = document.getElementById(id);
                    if (!el) return;
                    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    el.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
                    this.active = id;
                },

                // Highlights the contents entry for the section being read.
                init() {
                    if (!('IntersectionObserver' in window)) return;
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) this.active = entry.target.id;
                        });
                    }, { rootMargin: '-15% 0px -75% 0px' });
                    document.querySelectorAll('[data-policy-section]').forEach(el => observer.observe(el));
                },
            };
        }
    </script>
</x-app-layout>
