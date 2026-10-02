@php
    $isAdmin = auth()->user()->isAdmin();
    $isItSupport = auth()->user()->isItSupport();
    // IT Support can add and edit assets. Deleting stays admin-only.
    $canAddAsset = $isAdmin || $isItSupport;
    $canEditAsset = $isAdmin || $isItSupport;

    $statusStyles = [
        'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'in_repair' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'retired' => 'bg-slate-100 text-slate-500 ring-slate-200',
    ];
    $statusDots = [
        'active' => 'bg-emerald-500',
        'in_repair' => 'bg-amber-500',
        'retired' => 'bg-slate-400',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Asset Inventory</h2>
            <div class="flex gap-2">
                @if($isAdmin)
                    {{-- Report export — reflects whatever search/department filter is currently
                         applied below, so the download always matches what's on screen. --}}
                    <div class="relative" x-data="{ showExport: false }">
                        <button @click="showExport = !showExport" @click.outside="showExport = false"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">
                            Export
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="showExport" x-cloak x-transition
                             class="absolute right-0 mt-1.5 w-44 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-20">
                            <a href="{{ route('admin.assets.export.pdf', request()->query()) }}" class="flex items-center gap-2 px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Export as PDF
                            </a>
                            <a href="{{ route('admin.assets.export.excel', request()->query()) }}" class="flex items-center gap-2 px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Export as Excel
                            </a>
                        </div>
                    </div>
                @endif
                @if($canAddAsset)
                    <a href="{{ route('assets.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm font-semibold shadow-sm" style="background-color:#1a6b3c;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        New Asset
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <style>[x-cloak] { display: none !important; }</style>

    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
         x-data='{
            viewingId: null,
         }'>

        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-100">{{ session('error') }}</div>
        @endif

        @php
            // Click-to-filter links: keeps the current search/department, swaps one
            // filter, and clicking an active one again turns it off.
            $toggle = function (string $key, ?string $value) {
                $params = request()->except('page');
                if ($value === null || request($key) === $value) {
                    unset($params[$key]);
                } else {
                    $params[$key] = $value;
                }
                return route('assets.index', array_filter($params, fn ($v) => $v !== null && $v !== ''));
            };
            $resetFacets = route('assets.index', array_filter(request()->only('search', 'department_id')));

            $typeIcons = [
                'DT' => 'M9 3.75h6a1.5 1.5 0 011.5 1.5v13.5a1.5 1.5 0 01-1.5 1.5H9a1.5 1.5 0 01-1.5-1.5V5.25A1.5 1.5 0 019 3.75zM10.5 7.5h3M12 17.25h.008',
                'LT' => 'M3 5.25A1.5 1.5 0 014.5 3.75h15A1.5 1.5 0 0121 5.25v9.75H3V5.25zM1.5 18h21l-1.5 1.5h-18L1.5 18z',
                'MN' => 'M6 20.25h12m-7.5-3v3m3-3v3m-10.5-3h18a1.5 1.5 0 001.5-1.5V5.25a1.5 1.5 0 00-1.5-1.5h-18a1.5 1.5 0 00-1.5 1.5v10.5a1.5 1.5 0 001.5 1.5z',
                'PR' => 'M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z',
                'PH' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
                'NW' => 'M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z',
            ];
        @endphp

        @php
            $totalForPct = max($summary['total'], 1);
            $pctAssigned = round($summary['assigned'] / $totalForPct * 100);
            $pctUnassigned = round($summary['unassigned'] / $totalForPct * 100);
            $pctRepair = round($summary['in_repair'] / $totalForPct * 100);

            // Each device type gets its own colour (full class names so Tailwind can see them).
            $typeColors = [
                'DT' => ['chip' => 'bg-blue-100 text-blue-600',     'solid' => 'bg-blue-600',    'active' => 'border-blue-400 bg-blue-50 ring-blue-500/20',       'hover' => 'hover:border-blue-300 hover:bg-blue-50/50',     'bar' => 'bg-blue-500'],
                'LT' => ['chip' => 'bg-violet-100 text-violet-600', 'solid' => 'bg-violet-600',  'active' => 'border-violet-400 bg-violet-50 ring-violet-500/20', 'hover' => 'hover:border-violet-300 hover:bg-violet-50/50', 'bar' => 'bg-violet-500'],
                'MN' => ['chip' => 'bg-teal-100 text-teal-600',     'solid' => 'bg-teal-600',    'active' => 'border-teal-400 bg-teal-50 ring-teal-500/20',       'hover' => 'hover:border-teal-300 hover:bg-teal-50/50',     'bar' => 'bg-teal-500'],
                'PR' => ['chip' => 'bg-orange-100 text-orange-600', 'solid' => 'bg-orange-600',  'active' => 'border-orange-400 bg-orange-50 ring-orange-500/20', 'hover' => 'hover:border-orange-300 hover:bg-orange-50/50', 'bar' => 'bg-orange-500'],
                'PH' => ['chip' => 'bg-pink-100 text-pink-600',     'solid' => 'bg-pink-600',    'active' => 'border-pink-400 bg-pink-50 ring-pink-500/20',       'hover' => 'hover:border-pink-300 hover:bg-pink-50/50',     'bar' => 'bg-pink-500'],
                'NW' => ['chip' => 'bg-sky-100 text-sky-600',       'solid' => 'bg-sky-600',     'active' => 'border-sky-400 bg-sky-50 ring-sky-500/20',          'hover' => 'hover:border-sky-300 hover:bg-sky-50/50',       'bar' => 'bg-sky-500'],
            ];
        @endphp

        {{-- At-a-glance overview: one colour per meaning (blue = all, green = assigned, amber = stock, rose = repair) --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total --}}
            @php $on = ! request()->hasAny(['type','assignment','status']); @endphp
            <a href="{{ $resetFacets }}" class="group relative block overflow-hidden rounded-2xl border bg-gradient-to-br from-indigo-50 via-white to-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $on ? 'border-indigo-400 ring-2 ring-indigo-500/20' : 'border-indigo-100' }}">
                <span class="absolute inset-x-0 top-0 h-1 bg-indigo-500"></span>
                <div class="flex items-start justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Total assets</p>
                    <span class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                    </span>
                </div>
                <p class="mt-2 text-4xl font-bold text-indigo-900 tabular-nums">{{ number_format($summary['total']) }}</p>
                <p class="mt-1 text-xs text-indigo-600/70">{{ request()->filled('search') || request()->filled('department_id') ? 'In current search' : 'Across all departments' }}</p>
            </a>

            {{-- Assigned --}}
            @php $on = request('assignment') === 'assigned'; @endphp
            <a href="{{ $toggle('assignment', 'assigned') }}" class="group relative block overflow-hidden rounded-2xl border bg-gradient-to-br from-emerald-50 via-white to-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $on ? 'border-emerald-400 ring-2 ring-emerald-500/20' : 'border-emerald-100' }}">
                <span class="absolute inset-x-0 top-0 h-1 bg-emerald-500"></span>
                <div class="flex items-start justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Assigned</p>
                    <span class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                    </span>
                </div>
                <p class="mt-2 text-4xl font-bold text-emerald-800 tabular-nums">{{ number_format($summary['assigned']) }}</p>
                <p class="mt-1 text-xs text-emerald-700/70">With a user &middot; {{ $pctAssigned }}%</p>
                <div class="mt-3 h-1.5 rounded-full bg-emerald-100"><div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $pctAssigned }}%"></div></div>
            </a>

            {{-- Unassigned --}}
            @php $on = request('assignment') === 'unassigned'; @endphp
            <a href="{{ $toggle('assignment', 'unassigned') }}" class="group relative block overflow-hidden rounded-2xl border bg-gradient-to-br from-amber-50 via-white to-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $on ? 'border-amber-400 ring-2 ring-amber-500/25' : 'border-amber-200' }}">
                <span class="absolute inset-x-0 top-0 h-1 bg-amber-500"></span>
                <div class="flex items-start justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Unassigned</p>
                    <span class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                    </span>
                </div>
                <p class="mt-2 text-4xl font-bold text-amber-800 tabular-nums">{{ number_format($summary['unassigned']) }}</p>
                <p class="mt-1 text-xs text-amber-700/70">Available in stock &middot; {{ $pctUnassigned }}%</p>
                <div class="mt-3 h-1.5 rounded-full bg-amber-100"><div class="h-1.5 rounded-full bg-amber-500" style="width: {{ $pctUnassigned }}%"></div></div>
            </a>

            {{-- In repair --}}
            @php $on = request('status') === 'in_repair'; @endphp
            <a href="{{ $toggle('status', 'in_repair') }}" class="group relative block overflow-hidden rounded-2xl border bg-gradient-to-br from-rose-50 via-white to-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $on ? 'border-rose-400 ring-2 ring-rose-500/20' : 'border-rose-100' }}">
                <span class="absolute inset-x-0 top-0 h-1 bg-rose-500"></span>
                <div class="flex items-start justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">In repair</p>
                    <span class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085" /></svg>
                    </span>
                </div>
                <p class="mt-2 text-4xl font-bold text-rose-800 tabular-nums">{{ number_format($summary['in_repair']) }}</p>
                <p class="mt-1 text-xs text-rose-600/70">{{ $summary['retired'] }} retired</p>
                <div class="mt-3 h-1.5 rounded-full bg-rose-100"><div class="h-1.5 rounded-full bg-rose-500" style="width: {{ $pctRepair }}%"></div></div>
            </a>
        </div>

        {{-- Breakdown by device type --}}
        <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-4 sm:p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                    <span class="w-1.5 h-4 rounded-full bg-gradient-to-b from-indigo-500 to-teal-500"></span>
                    Devices by type
                </h3>
                <p class="text-xs text-gray-400 hidden sm:block">Click a type to filter the list</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
                @foreach($summary['byType'] as $code => $row)
                    @php
                        $active = request('type') === $code;
                        $c = $typeColors[$code] ?? $typeColors['DT'];
                    @endphp
                    <a href="{{ $toggle('type', $code) }}"
                       class="group relative overflow-hidden rounded-xl border p-3.5 transition hover:-translate-y-0.5 hover:shadow-sm {{ $active ? $c['active'].' ring-2' : 'border-gray-200 bg-white '.$c['hover'] }}">
                        <span class="absolute inset-x-0 top-0 h-0.5 {{ $row['total'] > 0 || $active ? $c['bar'] : 'bg-gray-200' }}"></span>
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $active ? $c['solid'].' text-white' : ($row['total'] > 0 ? $c['chip'] : 'bg-gray-100 text-gray-400') }}">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $typeIcons[$code] ?? '' }}" /></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-2xl font-bold leading-none tabular-nums {{ $row['total'] > 0 ? 'text-gray-900' : 'text-gray-300' }}">{{ number_format($row['total']) }}</p>
                                <p class="text-xs font-medium mt-1 truncate {{ $row['total'] > 0 ? 'text-gray-600' : 'text-gray-400' }}">{{ $row['label'] }}</p>
                            </div>
                        </div>
                        <p class="mt-3 text-[11px] font-medium {{ $row['total'] === 0 ? 'text-gray-400' : ($row['unassigned'] > 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                            @if($row['total'] === 0)
                                None yet
                            @elseif($row['unassigned'] > 0)
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500 mr-1"></span>{{ $row['unassigned'] }} unassigned
                            @else
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>All assigned
                            @endif
                        </p>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Search + filters --}}
        <form method="GET" action="{{ route('assets.index') }}" class="bg-white shadow-sm rounded-xl border border-gray-100 p-4 flex flex-col lg:flex-row gap-3">
            <div class="flex-1 relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by tag, device name, or serial number..."
                       class="block w-full pl-9 rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm">
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:flex gap-3">
                <select name="department_id" onchange="this.form.submit()" class="rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm lg:w-44">
                    <option value="">All departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                <select name="type" onchange="this.form.submit()" class="rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm lg:w-40">
                    <option value="">All types</option>
                    @foreach(\App\Models\Asset::TYPES as $code => $label)
                        <option value="{{ $code }}" @selected(request('type') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="assignment" onchange="this.form.submit()" class="rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm lg:w-40">
                    <option value="">Assigned &amp; unassigned</option>
                    <option value="assigned" @selected(request('assignment') === 'assigned')>Assigned</option>
                    <option value="unassigned" @selected(request('assignment') === 'unassigned')>Unassigned</option>
                </select>
                <select name="status" onchange="this.form.submit()" class="rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm lg:w-36">
                    <option value="">All statuses</option>
                    @foreach(\App\Models\Asset::STATUSES as $code => $label)
                        <option value="{{ $code }}" @selected(request('status') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 shrink-0">
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background-color:#1a6b3c;">Search</button>
                @if($hasFilters)
                    <a href="{{ route('assets.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50 text-center">Clear</a>
                @endif
            </div>
        </form>

        @if($hasFilters)
            <p class="text-sm text-gray-500 -mt-2 px-1">
                Showing <span class="font-semibold text-gray-700">{{ number_format($assets->total()) }}</span> matching asset{{ $assets->total() === 1 ? '' : 's' }}
            </p>
        @endif

        {{-- Table: Asset Tag + Device always show; everything else folds progressively
             so the table never needs a horizontal scrollbar. --}}
        <div class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
            @if($assets->isEmpty())
                <div class="flex flex-col items-center justify-center text-center px-6 py-14">
                    @if($hasFilters)
                        <p class="text-gray-700 font-medium">No assets match your filters</p>
                        <p class="text-gray-400 text-sm mt-1">Try a different term or clear the filters.</p>
                    @else
                        <p class="text-gray-700 font-medium">No assets yet</p>
                        <p class="text-gray-400 text-sm mt-1">{{ $canAddAsset ? 'Assign your first asset to get started.' : 'Nothing has been logged yet.' }}</p>
                    @endif
                </div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-slate-50 to-indigo-50/40 border-b border-gray-200">
                            <th class="px-3 sm:px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap">Asset Tag</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Device</th>
                            <th class="hidden lg:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Department</th>
                            <th class="hidden md:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Assigned To</th>
                            <th class="hidden xl:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap">Assigned Since</th>
                            <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                            <th class="w-20 sm:w-32 px-3 sm:px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($assets as $asset)
                            <tr class="hover:bg-indigo-50/30 transition-colors">
                                <td class="px-3 sm:px-4 py-3.5 whitespace-nowrap align-top"><span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 font-mono text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-100">{{ $asset->asset_tag }}</span></td>
                                <td class="px-4 py-3.5 max-w-0 w-full align-top">
                                    <p class="font-medium text-gray-800 truncate">{{ $asset->device_name }}</p>
                                    @if($asset->serial_number)
                                        <p class="text-xs text-gray-400 mt-0.5 truncate">SN: {{ $asset->serial_number }}</p>
                                    @endif
                                    {{-- Folds in whatever's hidden at this breakpoint --}}
                                    <div class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-gray-500 mt-1">
                                        <span>{{ \App\Models\Asset::TYPES[$asset->type] ?? $asset->type }}</span>
                                        <span class="lg:hidden text-gray-300">·</span>
                                        <span class="lg:hidden truncate max-w-[9rem]">{{ $asset->department->name ?? '—' }}</span>
                                        <span class="md:hidden text-gray-300">·</span>
                                        <span class="md:hidden truncate max-w-[9rem]">{{ $asset->user->name ?? 'Unassigned' }}</span>
                                        <span class="sm:hidden inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium ring-1 ring-inset {{ $statusStyles[$asset->status] ?? 'bg-gray-100 text-gray-500 ring-gray-200' }}">
                                            {{ \App\Models\Asset::STATUSES[$asset->status] ?? ucfirst($asset->status) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="hidden lg:table-cell px-4 py-3.5 text-gray-600 align-top whitespace-nowrap">
                                    <p class="truncate max-w-[10rem]">{{ $asset->department->name ?? '—' }}</p>
                                </td>
                                <td class="hidden md:table-cell px-4 py-3.5 text-gray-600 align-top whitespace-nowrap">
                                    <p class="truncate max-w-[10rem]">{{ $asset->user->name ?? '—' }}</p>
                                </td>
                                <td class="hidden xl:table-cell px-4 py-3.5 text-gray-600 align-top whitespace-nowrap">
                                    @if($asset->assigned_date)
                                        <p>{{ $asset->assigned_date->format('M j, Y') }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $asset->assigned_duration }} ago</p>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="hidden sm:table-cell px-4 py-3.5 align-top whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap gap-1.5 {{ $statusStyles[$asset->status] ?? 'bg-gray-100 text-gray-500 ring-gray-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $statusDots[$asset->status] ?? 'bg-slate-400' }}"></span>
                                        {{ \App\Models\Asset::STATUSES[$asset->status] ?? ucfirst($asset->status) }}
                                    </span>
                                </td>
                                <td class="px-3 sm:px-4 py-3.5 text-right whitespace-nowrap align-top">
                                    <button @click="viewingId = {{ $asset->id }}" class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-slate-600 hover:bg-slate-200 font-medium text-xs mr-1.5">View</button>
                                    @if($canEditAsset)
                                        <a href="{{ route('assets.edit', $asset) }}" class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-emerald-700 hover:bg-emerald-100 font-medium text-xs {{ $isAdmin ? 'mr-1.5' : '' }}">Edit</a>
                                    @endif
                                    @if($isAdmin)
                                        <x-confirm-action-modal
                                            id="delete-asset-{{ $asset->id }}"
                                            action="{{ route('admin.assets.destroy', $asset) }}"
                                            title="Delete this asset?"
                                            message="Remove {{ $asset->asset_tag }} ({{ $asset->device_name }}) from inventory? This can't be undone."
                                            confirm-label="Delete Asset"
                                            trigger-label="Delete"
                                            trigger-class="inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-rose-600 hover:bg-rose-100 font-medium text-xs"
                                        />
                                    @endif
                                </td>
                            </tr>

                            {{-- Read-only details modal — available to everyone --}}
                            <tr x-show="viewingId === {{ $asset->id }}" x-cloak>
                                <td colspan="7" class="px-0 py-0">
                                    <div class="fixed inset-0 bg-black/30 z-40 flex items-center justify-center p-4" @click.self="viewingId = null">
                                        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
                                            <div class="flex justify-between items-center mb-4">
                                                <h3 class="text-base font-semibold text-gray-800 font-mono">{{ $asset->asset_tag }}</h3>
                                                <button @click="viewingId = null" class="text-gray-400 hover:text-gray-600">&times;</button>
                                            </div>

                                            <div class="grid grid-cols-2 gap-y-4 gap-x-4 text-sm">
                                                <div>
                                                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Device Name</p>
                                                    <p class="text-gray-800 font-medium">{{ $asset->device_name }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Type</p>
                                                    <p class="text-gray-800 font-medium">{{ \App\Models\Asset::TYPES[$asset->type] ?? $asset->type }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Company</p>
                                                    <p class="text-gray-800 font-medium">{{ \App\Models\Asset::COMPANIES[$asset->company] ?? $asset->company }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Location</p>
                                                    <p class="text-gray-800 font-medium">{{ \App\Models\Asset::locations()[$asset->location] ?? $asset->location }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Department</p>
                                                    <p class="text-gray-800 font-medium">{{ $asset->department->name ?? '—' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Assigned To</p>
                                                    <p class="text-gray-800 font-medium">{{ $asset->user->name ?? '—' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Assigned Since</p>
                                                    @if($asset->assigned_date)
                                                        <p class="text-gray-800 font-medium">{{ $asset->assigned_date->format('M j, Y') }}</p>
                                                        <p class="text-xs text-gray-400 mt-0.5">{{ $asset->assigned_duration }} ago</p>
                                                    @else
                                                        <p class="text-gray-800 font-medium">—</p>
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Serial Number</p>
                                                    <p class="text-gray-800 font-medium">{{ $asset->serial_number ?? '—' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Status</p>
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset {{ $statusStyles[$asset->status] ?? 'bg-gray-100 text-gray-500 ring-gray-200' }}">
                                                        {{ \App\Models\Asset::STATUSES[$asset->status] ?? ucfirst($asset->status) }}
                                                    </span>
                                                </div>
                                                <div class="col-span-2">
                                                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Notes</p>
                                                    <p class="text-gray-800 whitespace-pre-line">{{ $asset->notes ?: '—' }}</p>
                                                </div>
                                            </div>

                                            <div class="mt-6 pt-4 border-t border-dashed border-gray-200 flex justify-between text-xs text-gray-400">
                                                <span>Added {{ $asset->created_at->format('M j, Y') }}</span>
                                                <span>Last updated {{ $asset->updated_at->format('M j, Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="bg-white border border-gray-200 rounded-xl px-4 py-3.5">{{ $assets->links() }}</div>
    </div>
</x-app-layout>
