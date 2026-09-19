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
            showCreate: false,
            editingId: null,
            viewingId: null,
            newUserId: "",
            newUserQuery: "",
            userDropdownOpen: false,
            userDirectory: @json($users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]), JSON_HEX_APOS),
            get filteredUsers() {
                const q = this.newUserQuery.trim().toLowerCase();
                const list = q === ""
                    ? this.userDirectory
                    : this.userDirectory.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
                return list.slice(0, 30);
            },
            selectUser(u) {
                this.newUserId = u.id;
                this.newUserQuery = `${u.name} \u2014 ${u.email}`;
                this.userDropdownOpen = false;
            },
            resetUserPicker() {
                this.newUserId = "";
                this.newUserQuery = "";
                this.userDropdownOpen = false;
            },
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
                <button @click="showCreate = true; resetUserPicker()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm transition"
                        style="background-color:#1a6b3c;" onmouseover="this.style.backgroundColor='#145530'" onmouseout="this.style.backgroundColor='#1a6b3c'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New Asset
                </button>
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

        {{-- Table --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-x-auto">
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
                <table class="min-w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Asset Tag</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Device</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Department</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Assigned To</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Assigned Since</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Status</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $asset)
                            <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition">
                                <td class="px-4 py-3.5 font-mono text-xs font-semibold text-gray-700 border-r border-gray-100">{{ $asset->asset_tag }}</td>
                                <td class="px-4 py-3.5 border-r border-gray-100">
                                    <p class="font-medium text-gray-800">{{ $asset->device_name }}</p>
                                    @if($asset->serial_number)
                                        <p class="text-xs text-gray-400 mt-0.5">SN: {{ $asset->serial_number }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-gray-600 border-r border-gray-100">{{ \App\Models\Asset::TYPES[$asset->type] ?? $asset->type }}</td>
                                <td class="px-4 py-3.5 text-gray-600 border-r border-gray-100">{{ $asset->department->name ?? '—' }}</td>
                                <td class="px-4 py-3.5 text-gray-600 border-r border-gray-100">{{ $asset->user->name ?? '—' }}</td>
                                <td class="px-4 py-3.5 text-gray-600 border-r border-gray-100">
                                    @if($asset->assigned_date)
                                        <p class="whitespace-nowrap">{{ $asset->assigned_date->format('M j, Y') }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $asset->assigned_duration }} ago</p>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 border-r border-gray-100">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset {{ $statusStyles[$asset->status] ?? 'bg-gray-100 text-gray-500 ring-gray-200' }}">
                                        {{ \App\Models\Asset::STATUSES[$asset->status] ?? ucfirst($asset->status) }}
                                    </span>
                                </td>
                                @if($isAdmin)
                                    <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                        <button @click="viewingId = {{ $asset->id }}" class="text-gray-500 hover:underline font-medium text-xs mr-3">View</button>
                                        <button @click="editingId = {{ $asset->id }}" class="text-green-700 hover:underline font-medium text-xs mr-3">Edit</button>
                                        <form method="POST" action="{{ route('admin.assets.destroy', $asset) }}" class="inline"
                                              onsubmit="return confirm('Remove asset {{ $asset->asset_tag }}? This can\'t be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline font-medium text-xs">Delete</button>
                                        </form>
                                    </td>
                                @else
                                    <td class="px-4 py-3.5 text-right whitespace-nowrap">
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

                            @if($isAdmin)
                                {{-- Edit modal for this row --}}
                                <tr x-show="editingId === {{ $asset->id }}" x-cloak>
                                    <td colspan="7" class="px-0 py-0">
                                        <div class="fixed inset-0 bg-black/30 z-40 flex items-center justify-center p-4" @click.self="editingId = null">
                                            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
                                                <div class="flex justify-between items-center mb-4">
                                                    <h3 class="text-base font-semibold text-gray-800">Edit {{ $asset->asset_tag }}</h3>
                                                    <button @click="editingId = null" class="text-gray-400 hover:text-gray-600">&times;</button>
                                                </div>
                                                <form method="POST" action="{{ route('admin.assets.update', $asset) }}" class="space-y-4">
                                                    @csrf
                                                    @method('PUT')

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">Device Name</label>
                                                        <input type="text" name="device_name" value="{{ old('device_name', $asset->device_name) }}"
                                                               class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                                                        <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}"
                                                               class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Date</label>
                                                        <input type="date" name="assigned_date" value="{{ old('assigned_date', $asset->assigned_date?->toDateString()) }}" max="{{ now()->toDateString() }}"
                                                               class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                                            <select name="status" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>
                                                                @foreach(\App\Models\Asset::STATUSES as $value => $label)
                                                                    <option value="{{ $value }}" @selected(old('status', $asset->status) === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-1">Sequence #</label>
                                                            <input type="number" name="sequence" min="1" max="999" value="{{ old('sequence', $asset->sequence) }}"
                                                                   class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>
                                                            <p class="text-xs text-gray-400 mt-1">Changing this regenerates the tag.</p>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                                        <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">{{ old('notes', $asset->notes) }}</textarea>
                                                    </div>

                                                    <div class="flex justify-end gap-3 pt-2">
                                                        <button type="button" @click="editingId = null" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</button>
                                                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background-color:#1a6b3c;">Save changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div>{{ $assets->links() }}</div>

        {{-- Create modal --}}
        @if($canAddAsset)
            <div x-show="showCreate" x-cloak class="fixed inset-0 bg-black/30 z-40 flex items-center justify-center p-4" @click.self="showCreate = false">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-base font-semibold text-gray-800">Assign New Asset</h3>
                        <button @click="showCreate = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <form method="POST" :action="newUserId ? `{{ url('/admin/users') }}/${newUserId}/assets` : '#'" class="space-y-4"
                          @submit="if (!newUserId) { $event.preventDefault(); alert('Please choose a user.'); }">
                        @csrf

                        <div class="relative" @click.outside="userDropdownOpen = false">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assign To</label>
                            <div class="relative">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                                <input type="text"
                                       x-model="newUserQuery"
                                       @focus="userDropdownOpen = true"
                                       @input="newUserId = ''; userDropdownOpen = true"
                                       placeholder="Search by name or email..."
                                       autocomplete="off"
                                       class="block w-full pl-9 pr-9 rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700"
                                       required>
                                <button type="button" x-show="newUserQuery" @click="resetUserPicker()" x-cloak
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <div x-show="userDropdownOpen" x-cloak
                                 class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-y-auto">
                                <template x-for="u in filteredUsers" :key="u.id">
                                    <button type="button" @click="selectUser(u)"
                                            class="w-full text-left px-3.5 py-2.5 hover:bg-green-50 transition border-b border-gray-50 last:border-0">
                                        <p class="text-sm font-medium text-gray-800" x-text="u.name"></p>
                                        <p class="text-xs text-gray-400" x-text="u.email"></p>
                                    </button>
                                </template>
                                <div x-show="filteredUsers.length === 0" class="px-3.5 py-3 text-sm text-gray-400">No matching users</div>
                            </div>

                            <p class="text-xs text-gray-400 mt-1">Matched by name or email — pick the exact person if names repeat.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                                <select name="company" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>
                                    <option value="">Select company</option>
                                    @foreach(\App\Models\Asset::COMPANIES as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <select name="location" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>
                                    <option value="">Select location</option>
                                    @foreach(\App\Models\Asset::locations() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                <select name="department_id" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>
                                    <option value="">Select department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Device Type</label>
                                <select name="type" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>
                                    <option value="">Select type</option>
                                    @foreach(\App\Models\Asset::TYPES as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Device Name</label>
                            <input type="text" name="device_name" placeholder="e.g. Dell Latitude 5420" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                            <input type="text" name="serial_number" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Date</label>
                            <input type="date" name="assigned_date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}"
                                   class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
                            <p class="text-xs text-gray-400 mt-1">Defaults to today — change it if the device was actually handed over earlier.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showCreate = false" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background-color:#1a6b3c;">Assign Asset</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
