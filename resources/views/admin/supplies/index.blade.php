@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    // IT Support can add, edit and adjust stock. Deleting stays admin-only. Staff just view.
    $canManage = $isAdmin || $user->isItSupport();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <div class="min-w-0">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">IT Supplies</h2>
                <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">Ink, batteries, mice, keyboards, USB drives and other consumables</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('supplies.requests') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ $canManage ? 'Requests' : 'My requests' }}
                    @if($openRequests > 0)
                        <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-sky-100 px-1.5 text-[11px] font-semibold text-sky-700">{{ $openRequests }}</span>
                    @endif
                </a>
                @if($canManage)
                    <a href="{{ route('supplies.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm font-semibold shadow-sm" style="background-color:#1a6b3c;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        New Item
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <style>
        [x-cloak] { display: none !important; }

        /* Filter bar: highlight any control that is currently narrowing the list. */
        .filter-active { border-color: #1a6b3c !important; background-color: #f0f9f3 !important; }
        .filter-chip { display: inline-flex; align-items: center; gap: .375rem; padding: .25rem .5rem .25rem .75rem;
            border-radius: 9999px; background: #ecf7f0; color: #14532d; font-size: .75rem; font-weight: 500;
            box-shadow: inset 0 0 0 1px #bfe3cc; transition: background-color .15s ease; }
        .filter-chip:hover { background: #d8f0e1; }
        .filter-chip svg { width: .875rem; height: .875rem; opacity: .7; }

        /* Overview cards & category tiles — same look as the Asset Inventory page. */
        .stat-card, .type-card { position: relative; display: block; background: #fff; border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 4px 12px -6px rgba(15, 23, 42, .08);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
        .stat-card { border-radius: 1rem; padding: 1.25rem; overflow: hidden; }
        .type-card { border-radius: .75rem; padding: .875rem; }
        .stat-card:hover, .type-card:hover { transform: translateY(-3px); box-shadow: 0 12px 24px -10px rgba(15, 23, 42, .25); }
        .stat-card:not(.tone-hero)::before { content: ''; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: var(--accent); }
        .tone-green { --accent: #1a6b3c; --accent2: #34a265; --tint1: #dff2e6; --tint2: #c4e6d0; background: linear-gradient(160deg, #f1faf4 0%, #fff 60%); }
        .tone-gold  { --accent: #b7791f; --accent2: #e0a93a; --tint1: #fbefd2; --tint2: #f5dfa6; background: linear-gradient(160deg, #fdf6e5 0%, #fff 60%); }
        .tone-orange { --accent: #c2410c; --accent2: #fb923c; --tint1: #ffedd5; --tint2: #fed7aa; background: linear-gradient(160deg, #fff7ed 0%, #fff 60%); }
        .tone-rose  { --accent: #be123c; --accent2: #f43f5e; --tint1: #ffe4e6; --tint2: #fecdd3; background: linear-gradient(160deg, #fff1f2 0%, #fff 60%); }
        .stat-card.is-on { border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 20%, transparent), 0 12px 24px -10px rgba(15, 23, 42, .25); }

        .stat-icon, .type-chip { display: flex; align-items: center; justify-content: center; color: #1a6b3c;
            background: linear-gradient(135deg, #dff2e6, #c4e6d0); transition: transform .18s ease; }
        .stat-icon { width: 2.5rem; height: 2.5rem; border-radius: .875rem; }
        .stat-card:hover .stat-icon, .type-card:hover .type-chip { transform: scale(1.1) rotate(-4deg); }
        .tone-green .stat-icon, .tone-gold .stat-icon, .tone-orange .stat-icon, .tone-rose .stat-icon { color: var(--accent); background: linear-gradient(135deg, var(--tint1), var(--tint2)); }

        .stat-bar { height: 6px; border-radius: 9999px; background: rgba(15, 23, 42, .07); overflow: hidden; }
        .stat-bar > div { height: 100%; border-radius: 9999px; background: linear-gradient(90deg, var(--accent), var(--accent2)); }

        .stat-card.tone-hero { border-color: transparent; color: #fff;
            background: linear-gradient(145deg, #0c3320 0%, #1a6b3c 62%, #237a45 100%);
            box-shadow: 0 10px 24px -10px rgba(12, 51, 32, .6); }
        .stat-card.tone-hero::before { content: ''; position: absolute; inset: 0; opacity: .1; pointer-events: none;
            background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 20px 20px; }
        .stat-card.tone-hero::after { content: ''; position: absolute; top: -48px; right: -48px; width: 150px; height: 150px; border-radius: 9999px;
            background: radial-gradient(circle, rgba(255, 255, 255, .22), transparent 70%); pointer-events: none; }
        .stat-card.tone-hero > * { position: relative; z-index: 1; }
        .stat-card.tone-hero .lbl { color: #bfe3cc !important; }
        .stat-card.tone-hero .val { color: #fff !important; }
        .stat-card.tone-hero .sub { color: #a7d4b8 !important; }
        .stat-card.tone-hero .stat-icon { background: rgba(255, 255, 255, .16); color: #fff; }
        .stat-card.tone-hero.is-on { box-shadow: 0 0 0 3px rgba(26, 107, 60, .35), 0 10px 24px -10px rgba(12, 51, 32, .6); }

        .type-card:hover { border-color: #bfe3cc; background: #f6fbf8; }
        .type-chip { width: 2.5rem; height: 2.5rem; border-radius: .75rem; flex-shrink: 0; }
        .type-chip.is-empty { background: #f3f4f6; color: #9ca3af; }
        .type-card.is-on { border-color: #1a6b3c; background: #f0f9f3; box-shadow: 0 0 0 3px rgba(26, 107, 60, .14); }
        .type-card.is-on .type-chip { background: linear-gradient(135deg, #1a6b3c, #2f9e5f); color: #fff; box-shadow: 0 4px 10px -3px rgba(26, 107, 60, .55); }

        /* Table */
        .asset-thead { background: linear-gradient(90deg, #e6f3eb 0%, #f4faf6 100%); border-bottom: 2px solid #cfe8d8 !important; }
        .asset-row { transition: background-color .12s ease, box-shadow .12s ease; }
        .asset-row:hover { background: #f3faf5; box-shadow: inset 3px 0 0 #1a6b3c; }
        .soft-green { background: #ecf7f0; color: #1a6b3c; }
        .soft-green:hover { background: #d8f0e1; }
    </style>

    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-100">{{ session('error') }}</div>
        @endif

        @php
            // Click-to-filter links: keeps the current search/branch, swaps one filter,
            // and clicking an active one again turns it off.
            $toggle = function (string $key, ?string $value) {
                $params = request()->except('page');
                if ($value === null || request($key) === $value) {
                    unset($params[$key]);
                } else {
                    $params[$key] = $value;
                }
                return route('supplies.index', array_filter($params, fn ($v) => $v !== null && $v !== ''));
            };
            $resetFacets = route('supplies.index', array_filter(request()->only('search', 'location')));

            $totalForPct = max($summary['total'], 1);
            $pct = fn (int $n) => (int) round($n / $totalForPct * 100);
            $scoped = request()->filled('search') || request()->filled('location');

            $statCards = [
                ['tone' => 'hero',  'label' => 'Total items',  'value' => $summary['total'], 'href' => $resetFacets,
                 'on' => ! request()->hasAny(['category', 'status']),
                 'sub' => $scoped ? 'In current search' : 'Across all branches', 'pct' => null,
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />'],
                ['tone' => 'green', 'label' => 'In stock',     'value' => $summary['ok'],  'href' => $toggle('status', 'ok'),  'on' => request('status') === 'ok',
                 'sub' => 'Comfortably stocked · '.$pct($summary['ok']).'%', 'pct' => $pct($summary['ok']),
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                ['tone' => 'gold',  'label' => 'Low stock',    'value' => $summary['low'], 'href' => $toggle('status', 'low'), 'on' => request('status') === 'low',
                 'sub' => 'Running low — plan a reorder', 'pct' => $pct($summary['low']),
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />'],
                ['tone' => 'orange', 'label' => 'Critical',    'value' => $summary['critical'], 'href' => $toggle('status', 'critical'), 'on' => request('status') === 'critical',
                 'sub' => 'Critically low — reorder now', 'pct' => $pct($summary['critical']),
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />'],
                ['tone' => 'rose',  'label' => 'Out of stock', 'value' => $summary['out'], 'href' => $toggle('status', 'out'), 'on' => request('status') === 'out',
                 'sub' => 'Needs restocking now', 'pct' => $pct($summary['out']),
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />'],
            ];
        @endphp

        {{-- At-a-glance overview --}}
        <div id="supply-overview" data-live-list class="space-y-6">
            {{-- Heads-up for the people who restock: anything critical or out is urgent. --}}
            @if($canManage && ($summary['critical'] + $summary['out']) > 0)
                <a href="{{ $toggle('status', $summary['critical'] > 0 ? 'critical' : 'out') }}"
                   class="flex items-center gap-3 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-900 hover:bg-orange-100/70 transition">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-orange-100 text-orange-600 shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    </span>
                    <p class="min-w-0">
                        <span class="font-semibold">{{ $summary['critical'] + $summary['out'] }} item{{ $summary['critical'] + $summary['out'] === 1 ? '' : 's' }} to reorder now</span>
                        <span class="text-orange-800/80">— {{ $summary['critical'] }} critical, {{ $summary['out'] }} out of stock</span>
                    </p>
                    <span class="ml-auto text-xs font-semibold hidden sm:block">Review →</span>
                </a>
            @endif

            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($statCards as $card)
                    <a href="{{ $card['href'] }}" class="stat-card tone-{{ $card['tone'] }} {{ $card['on'] ? 'is-on' : '' }} {{ $card['tone'] === 'hero' ? 'col-span-2 lg:col-span-1' : '' }}">
                        <div class="flex items-start justify-between">
                            <p class="lbl text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $card['label'] }}</p>
                            <span class="stat-icon">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">{!! $card['icon'] !!}</svg>
                            </span>
                        </div>
                        <p class="val mt-2 text-4xl font-bold text-gray-900 tabular-nums">{{ number_format($card['value']) }}</p>
                        <p class="sub mt-1 text-xs text-gray-500">{{ $card['sub'] }}</p>
                        @if($card['pct'] !== null)
                            <div class="stat-bar mt-3"><div style="width: {{ $card['pct'] }}%"></div></div>
                        @endif
                    </a>
                @endforeach
            </div>

            {{-- Breakdown by category --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-4 sm:p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                        <span class="w-1.5 h-4 rounded-full" style="background-color:#1a6b3c;"></span>
                        Supplies by category
                    </h3>
                    <p class="text-xs text-gray-400 hidden sm:block">Click a category to filter the list</p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach($summary['byCategory'] as $code => $row)
                        @php
                            $active = request('category') === $code;
                            $attention = $row['low'] + $row['critical'] + $row['out'];
                        @endphp
                        <a href="{{ $toggle('category', $code) }}" class="type-card {{ $active ? 'is-on' : '' }}">
                            <div class="flex items-center gap-3">
                                <span class="type-chip {{ $row['total'] > 0 || $active ? '' : 'is-empty' }}">
                                    <x-supply-icon :category="$code" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-2xl font-bold leading-none tabular-nums {{ $row['total'] > 0 ? 'text-gray-900' : 'text-gray-300' }}">{{ number_format($row['total']) }}</p>
                                    <p class="text-xs font-medium mt-1 truncate {{ $row['total'] > 0 ? 'text-gray-600' : 'text-gray-400' }}">{{ $row['label'] }}</p>
                                </div>
                            </div>
                            <p class="mt-3 text-[11px] font-medium flex items-center {{ $row['total'] === 0 ? 'text-gray-400' : ($attention > 0 ? 'text-amber-600' : '') }}"
                               @if($row['total'] > 0 && $attention === 0) style="color:#1a6b3c;" @endif>
                                @if($row['total'] === 0)
                                    None yet
                                @elseif($attention > 0)
                                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>{{ $attention }} need{{ $attention === 1 ? 's' : '' }} restocking
                                @else
                                    <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5" style="background-color:#1a6b3c;"></span>All stocked
                                @endif
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Search + filters --}}
        @php
            $filterFields = [
                ['name' => 'category', 'label' => 'Category', 'all' => 'All categories', 'options' => \App\Models\Supply::CATEGORIES],
                ['name' => 'location', 'label' => 'Branch',   'all' => 'All branches',   'options' => $branches],
                ['name' => 'status',   'label' => 'Stock',    'all' => 'All stock levels', 'options' => \App\Models\Supply::STOCK_STATUSES],
            ];

            // One removable chip per active filter (same validity rules as the controller).
            $chips = [];
            if (filled(request('search'))) {
                $chips[] = ['key' => 'search', 'label' => 'Search: "'.request('search').'"'];
            }
            foreach ($filterFields as $f) {
                $current = (string) request($f['name'], '');
                if ($current !== '' && array_key_exists($current, $f['options'])) {
                    $chips[] = ['key' => $f['name'], 'label' => $f['label'].': '.$f['options'][$current]];
                }
            }
        @endphp

        <form method="GET" action="{{ route('supplies.index') }}" data-live-filter class="bg-white shadow-sm rounded-2xl border border-gray-100 p-4 space-y-4">
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by item name, brand, or model / part number..." autocomplete="off"
                       class="block w-full pl-9 rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm {{ filled(request('search')) ? 'filter-active' : '' }}">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach($filterFields as $f)
                    @php $isOn = (string) request($f['name'], '') !== '' && array_key_exists((string) request($f['name']), $f['options']); @endphp
                    <div class="min-w-0 filter-field">
                        <label for="filter-{{ $f['name'] }}" class="block text-[11px] font-semibold uppercase tracking-wider mb-1 text-gray-400">{{ $f['label'] }}</label>
                        <select id="filter-{{ $f['name'] }}" name="{{ $f['name'] }}"
                                class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm {{ $isOn ? 'filter-active font-medium' : '' }}">
                            <option value="">{{ $f['all'] }}</option>
                            @foreach($f['options'] as $value => $label)
                                <option value="{{ $value }}" @selected((string) request($f['name'], '') === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>

            <div id="supply-chips" data-live-list style="margin-top:0">
                @if($hasFilters)
                    <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-gray-100" style="margin-top:1rem">
                        <span class="text-xs text-gray-500 mr-1">
                            <span class="font-semibold text-gray-700">{{ number_format($supplies->total()) }}</span> matching item{{ $supplies->total() === 1 ? '' : 's' }}
                        </span>
                        @foreach($chips as $chip)
                            <a href="{{ $toggle($chip['key'], null) }}" class="filter-chip" title="Remove this filter">
                                <span>{{ $chip['label'] }}</span>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </a>
                        @endforeach
                        <a href="{{ route('supplies.index') }}" class="ml-auto text-xs font-medium text-gray-500 hover:text-gray-800 underline">Clear all</a>
                    </div>
                @endif
            </div>
        </form>

        <div id="supply-results" data-live-list data-live-scroll class="space-y-6">
            {{-- Item + Stock always show; everything else folds in progressively so the table never scrolls sideways. --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
                @if($supplies->isEmpty())
                    <div class="flex flex-col items-center justify-center text-center px-6 py-14">
                        @if($hasFilters)
                            <p class="text-gray-700 font-medium">No supplies match your filters</p>
                            <p class="text-gray-400 text-sm mt-1">Try a different term or clear the filters.</p>
                            <a href="{{ route('supplies.index') }}" class="mt-4 inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background-color:#1a6b3c;">Clear all filters</a>
                        @else
                            <p class="text-gray-700 font-medium">No supplies yet</p>
                            <p class="text-gray-400 text-sm mt-1">{{ $canManage ? 'Add your first item to start tracking stock.' : 'Nothing has been logged yet.' }}</p>
                            @if($canManage)
                                <a href="{{ route('supplies.create') }}" class="mt-4 inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background-color:#1a6b3c;">Add first item</a>
                            @endif
                        @endif
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="asset-thead border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Item</th>
                                <th class="hidden lg:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                                <th class="hidden md:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Branch</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap">In Stock</th>
                                <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="w-28 sm:w-48 px-3 sm:px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($supplies as $supply)
                                @php
                                    $qtyColor = ['ok' => 'text-gray-900', 'low' => 'text-amber-700', 'critical' => 'text-orange-600', 'out' => 'text-rose-600'][$supply->stock_status];
                                    $reserved = $supply->reserved_quantity;
                                    // Staff can ask for it when some is free and it's kept at their own branch.
                                    $canRequest = ! $canManage && $supply->available_quantity > 0 && $supply->location === $user->location;
                                    $detail = collect([$supply->brand, $supply->model_no])->filter()->implode(' · ');
                                @endphp
                                <tr class="asset-row">
                                    <td class="px-4 py-3.5 max-w-0 w-full align-top">
                                        <div class="flex items-start gap-3">
                                            <span class="type-chip hidden sm:flex" style="width:2.25rem;height:2.25rem;border-radius:.625rem;">
                                                <x-supply-icon :category="$supply->category" class="w-4 h-4" />
                                            </span>
                                            <div class="min-w-0">
                                                <a href="{{ route('supplies.show', $supply) }}" class="font-medium text-gray-800 hover:text-green-800 truncate block">{{ $supply->name }}</a>
                                                <p class="text-xs text-gray-400 mt-0.5 truncate">
                                                    <span class="font-mono">{{ $supply->code }}</span>@if($detail) · {{ $detail }}@endif
                                                </p>
                                                {{-- Folds in whatever's hidden at this breakpoint --}}
                                                <div class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-gray-500 mt-1 lg:hidden">
                                                    <span>{{ $supply->category_label }}</span>
                                                    <span class="md:hidden text-gray-300">·</span>
                                                    <span class="md:hidden">{{ $supply->branch_name ?? '—' }}</span>
                                                    <span class="sm:hidden"><x-supply-status-badge :status="$supply->stock_status" class="!py-0.5 !text-[11px]" /></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden lg:table-cell px-4 py-3.5 text-gray-600 align-top whitespace-nowrap">{{ $supply->category_label }}</td>
                                    <td class="hidden md:table-cell px-4 py-3.5 text-gray-600 align-top whitespace-nowrap">{{ $supply->branch_name ?? '—' }}</td>
                                    <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                        <p class="font-semibold tabular-nums {{ $qtyColor }}">{{ number_format($supply->quantity) }} <span class="text-xs font-normal text-gray-400">{{ $supply->unit }}</span></p>
                                        @if($reserved > 0)
                                            <p class="text-[11px] text-sky-600 mt-0.5">{{ number_format($reserved) }} reserved</p>
                                        @elseif($canManage)
                                            <p class="text-[11px] text-gray-400 mt-0.5">Reorder at {{ number_format($supply->reorder_level) }}</p>
                                        @endif
                                    </td>
                                    <td class="hidden sm:table-cell px-4 py-3.5 align-top whitespace-nowrap">
                                        <x-supply-status-badge :status="$supply->stock_status" />
                                    </td>
                                    <td class="px-3 sm:px-4 py-3.5 text-right whitespace-nowrap align-top">
                                        <a href="{{ route('supplies.show', $supply) }}" class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-slate-600 hover:bg-slate-200 font-medium text-xs mr-1.5">View</a>
                                        @if($canManage)
                                            <a href="{{ route('supplies.edit', $supply) }}" class="inline-flex items-center rounded-md soft-green px-2.5 py-1 font-medium text-xs {{ $isAdmin ? 'mr-1.5' : '' }}">Edit</a>
                                        @elseif($canRequest)
                                            <a href="{{ route('supplies.show', [$supply, 'request' => 1]) }}" class="inline-flex items-center rounded-md px-2.5 py-1 font-semibold text-xs text-white" style="background-color:#1a6b3c;">Request</a>
                                        @endif
                                        @if($isAdmin)
                                            <x-confirm-action-modal
                                                id="delete-supply-{{ $supply->id }}"
                                                action="{{ route('admin.supplies.destroy', $supply) }}"
                                                title="Delete this item?"
                                                message="Remove {{ $supply->name }} ({{ $supply->code }}) and its stock history? This can't be undone."
                                                confirm-label="Delete Item"
                                                trigger-label="Delete"
                                                trigger-class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-rose-600 hover:bg-rose-100 font-medium text-xs"
                                            />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="bg-white border border-gray-200 rounded-xl px-4 py-3.5">{{ $supplies->links() }}</div>
        </div>
    </div>
</x-app-layout>
