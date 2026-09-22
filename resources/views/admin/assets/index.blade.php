@php
    $isAdmin = auth()->user()->isAdmin();
    // IT Support can add new assets too, but editing/deleting an existing
    // asset stays admin-only (kept separate from $isAdmin on purpose).
    $canAddAsset = $isAdmin || auth()->user()->isItSupport();

    $statusStyles = [
        'active' => 'bg-green-50 text-green-700 ring-green-200',
        'in_repair' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'retired' => 'bg-gray-100 text-gray-500 ring-gray-200',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Asset Inventory</h2>
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

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <p class="text-sm text-gray-500">{{ $assets->total() }} asset{{ $assets->total() === 1 ? '' : 's' }} found</p>
            @if($canAddAsset)
                <a href="{{ route('assets.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm transition"
                   style="background-color:#1a6b3c;" onmouseover="this.style.backgroundColor='#145530'" onmouseout="this.style.backgroundColor='#1a6b3c'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New Asset
                </a>
            @endif
        </div>

        {{-- Search + department filter --}}
        <form method="GET" action="{{ route('assets.index') }}" class="bg-white shadow-sm rounded-xl border border-gray-100 p-4 flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by tag, device name, or serial number..."
                       class="block w-full pl-9 rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm">
            </div>
            <select name="department_id" onchange="this.form.submit()" class="rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm sm:w-56">
                <option value="">All departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white shrink-0" style="background-color:#1a6b3c;">Search</button>
            @if(request('search') || request('department_id'))
                <a href="{{ route('assets.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50 text-center shrink-0">Clear</a>
            @endif
        </form>

        {{-- Table: Asset Tag + Device always show; everything else folds progressively
             so the table never needs a horizontal scrollbar. --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            @if($assets->isEmpty())
                <div class="flex flex-col items-center justify-center text-center px-6 py-14">
                    @if(request('search') || request('department_id'))
                        <p class="text-gray-700 font-medium">No assets match your search</p>
                        <p class="text-gray-400 text-sm mt-1">Try a different term or clear the filter.</p>
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
                                <td class="hidden lg:table-cell px-4 py-3.5 text-gray-600 align-top">
                                    <p class="truncate max-w-[10rem]">{{ $asset->department->name ?? '—' }}</p>
                                </td>
                                <td class="hidden md:table-cell px-4 py-3.5 text-gray-600 align-top">
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
                                <td class="hidden sm:table-cell px-4 py-3.5 align-top">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset whitespace-nowrap {{ $statusStyles[$asset->status] ?? 'bg-gray-100 text-gray-500 ring-gray-200' }}">
                                        {{ \App\Models\Asset::STATUSES[$asset->status] ?? ucfirst($asset->status) }}
                                    </span>
                                </td>
                                @if($isAdmin)
                                    <td class="px-3 sm:px-4 py-3.5 text-right whitespace-nowrap align-top">
                                        <button @click="viewingId = {{ $asset->id }}" class="text-gray-500 hover:underline font-medium text-xs mr-3">View</button>
                                        <a href="{{ route('admin.assets.edit', $asset) }}" class="text-green-700 hover:underline font-medium text-xs mr-3">Edit</a>
                                        <x-confirm-action-modal
                                            id="delete-asset-{{ $asset->id }}"
                                            action="{{ route('admin.assets.destroy', $asset) }}"
                                            title="Delete this asset?"
                                            message="Remove {{ $asset->asset_tag }} ({{ $asset->device_name }}) from inventory? This can't be undone."
                                            confirm-label="Delete Asset"
                                            trigger-label="Delete"
                                            trigger-class="text-red-600 hover:underline font-medium text-xs"
                                        />
                                    </td>
                                @else
                                    <td class="px-3 sm:px-4 py-3.5 text-right whitespace-nowrap align-top">
                                        <button @click="viewingId = {{ $asset->id }}" class="text-green-700 hover:underline font-medium text-xs">View</button>
                                    </td>
                                @endif
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
