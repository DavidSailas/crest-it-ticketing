@php
    $isAdmin = auth()->user()->isAdmin();
    $isItSupport = auth()->user()->isItSupport();
    // IT Support can add and edit assets. Deleting stays admin-only.
    $canAddAsset = $isAdmin || $isItSupport;
    $canEditAsset = $isAdmin || $isItSupport;

    $statusStyles = [
        'active' => 'bg-green-50 text-green-700 ring-green-200',
        'in_repair' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'retired' => 'bg-gray-100 text-gray-500 ring-gray-200',
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

        {{-- At-a-glance overview --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <a href="{{ $resetFacets }}" class="block rounded-xl border bg-white p-4 shadow-sm transition hover:shadow {{ ! request()->hasAny(['type','assignment','status']) ? 'border-green-700 ring-1 ring-green-700/20' : 'border-gray-100' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total assets</p>
                <p class="mt-1.5 text-3xl font-semibold text-gray-800 tabular-nums">{{ number_format($summary['total']) }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ request()->filled('search') || request()->filled('department_id') ? 'In current search' : 'Across all departments' }}</p>
            </a>
            <a href="{{ $toggle('assignment', 'assigned') }}" class="block rounded-xl border bg-white p-4 shadow-sm transition hover:shadow {{ request('assignment') === 'assigned' ? 'border-green-700 ring-1 ring-green-700/20' : 'border-gray-100' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Assigned</p>
                <p class="mt-1.5 text-3xl font-semibold tabular-nums" style="color:#1a6b3c;">{{ number_format($summary['assigned']) }}</p>
                <p class="mt-0.5 text-xs text-gray-400">With a user</p>
            </a>
            <a href="{{ $toggle('assignment', 'unassigned') }}" class="block rounded-xl border p-4 shadow-sm transition hover:shadow {{ request('assignment') === 'unassigned' ? 'border-amber-500 ring-1 ring-amber-500/30 bg-amber-50' : ($summary['unassigned'] > 0 ? 'border-amber-200 bg-amber-50/50' : 'border-gray-100 bg-white') }}">
                <p class="text-xs font-semibold uppercase tracking-wide {{ $summary['unassigned'] > 0 ? 'text-amber-700' : 'text-gray-500' }}">Unassigned</p>
                <p class="mt-1.5 text-3xl font-semibold tabular-nums {{ $summary['unassigned'] > 0 ? 'text-amber-700' : 'text-gray-800' }}">{{ number_format($summary['unassigned']) }}</p>
                <p class="mt-0.5 text-xs {{ $summary['unassigned'] > 0 ? 'text-amber-700/70' : 'text-gray-400' }}">Available in stock</p>
            </a>
            <a href="{{ $toggle('status', 'in_repair') }}" class="block rounded-xl border bg-white p-4 shadow-sm transition hover:shadow {{ request('status') === 'in_repair' ? 'border-green-700 ring-1 ring-green-700/20' : 'border-gray-100' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">In repair</p>
                <p class="mt-1.5 text-3xl font-semibold text-gray-800 tabular-nums">{{ number_format($summary['in_repair']) }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ $summary['retired'] }} retired</p>
            </a>
        </div>

        {{-- Breakdown by device type --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-4 sm:p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700">Devices by type</h3>
                <p class="text-xs text-gray-400 hidden sm:block">Click a type to filter the list</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
                @foreach($summary['byType'] as $code => $row)
                    @php $active = request('type') === $code; @endphp
                    <a href="{{ $toggle('type', $code) }}"
                       class="group rounded-lg border p-3 transition {{ $active ? 'border-green-700 bg-green-50/60 ring-1 ring-green-700/20' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50' }} {{ $row['total'] === 0 && ! $active ? 'opacity-60' : '' }}">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $active ? 'text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-white' }}" @if($active) style="background-color:#1a6b3c;" @endif>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $typeIcons[$code] ?? '' }}" /></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-xl font-semibold text-gray-800 leading-none tabular-nums">{{ number_format($row['total']) }}</p>
                                <p class="text-xs text-gray-500 mt-1 truncate">{{ $row['label'] }}</p>
                            </div>
                        </div>
                        <p class="mt-2.5 text-[11px] {{ $row['unassigned'] > 0 ? 'text-amber-700 font-medium' : 'text-gray-400' }}">
                            {{ $row['total'] === 0 ? 'None yet' : ($row['unassigned'] > 0 ? $row['unassigned'].' unassigned' : 'All assigned') }}
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
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
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
                        <tr class="bg-gray-50/80 border-b border-gray-200">
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
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="px-3 sm:px-4 py-3.5 font-mono text-xs font-semibold text-gray-700 whitespace-nowrap align-top">{{ $asset->asset_tag }}</td>
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
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap {{ $statusStyles[$asset->status] ?? 'bg-gray-100 text-gray-500 ring-gray-200' }}">
                                        {{ \App\Models\Asset::STATUSES[$asset->status] ?? ucfirst($asset->status) }}
                                    </span>
                                </td>
                                <td class="px-3 sm:px-4 py-3.5 text-right whitespace-nowrap align-top">
                                    <button @click="viewingId = {{ $asset->id }}" class="text-gray-500 hover:underline font-medium text-xs mr-3">View</button>
                                    @if($canEditAsset)
                                        <a href="{{ route('assets.edit', $asset) }}" class="text-green-700 hover:underline font-medium text-xs {{ $isAdmin ? 'mr-3' : '' }}">Edit</a>
                                    @endif
                                    @if($isAdmin)
                                        <x-confirm-action-modal
                                            id="delete-asset-{{ $asset->id }}"
                                            action="{{ route('admin.assets.destroy', $asset) }}"
                                            title="Delete this asset?"
                                            message="Remove {{ $asset->asset_tag }} ({{ $asset->device_name }}) from inventory? This can't be undone."
                                            confirm-label="Delete Asset"
                                            trigger-label="Delete"
                                            trigger-class="text-red-600 hover:underline font-medium text-xs"
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
