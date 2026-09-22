<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 mb-1">
            <a href="{{ route('admin.users.index') }}" class="hover:text-gray-600">Manage Users</a>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            <span class="text-gray-500">Edit</span>
        </div>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $user->name }}</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if(session('status'))
            <div class="flex items-center gap-2 p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                {{ session('status') }}
            </div>
        @endif
        @if(session('error'))
            <div class="flex items-center gap-2 p-3 bg-red-50 text-red-800 text-sm rounded-lg border border-red-100">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Account details --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="flex items-center gap-2.5 px-6 sm:px-8 py-5 border-b border-gray-100">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-700 shrink-0">
                    <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Account Details</p>
                    <p class="text-xs text-gray-400">Name, login, role, and VIP status</p>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                @if ($errors->hasAny(['first_name', 'last_name', 'username', 'email', 'password', 'department_id', 'role', 'location']))
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3.5">
                        <p class="text-sm font-semibold text-red-800 mb-1">Please fix the following:</p>
                        <ul class="text-sm text-red-700 list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6" novalidate>
                    @csrf @method('PUT')

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Personal Info</p>
                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">First Name</label>
                                <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Last Name</label>
                                <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                            </div>
                        </div>
                    </div>

                    <div class="pt-5 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Login</p>
                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                                <input type="text" name="username" value="{{ old('username', $user->username) }}" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="password" name="password" placeholder="Leave blank to keep current" class="block w-full sm:w-1/2 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            </div>
                        </div>
                    </div>

                    <div class="pt-5 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Assignment</p>
                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Department</label>
                                <select name="department_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    <option value="" disabled @selected(old('department_id', $user->department_id) === null)>Select department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" @selected((string) old('department_id', $user->department_id) === (string) $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                @error('department_id') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Position</label>
                                <select name="position_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    <option value="" disabled @selected(old('position_id', $user->position_id) === null)>Select position</option>
                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}" @selected((string) old('position_id', $user->position_id) === (string) $position->id)>{{ $position->name }}</option>
                                    @endforeach
                                </select>
                                @error('position_id') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                                @if($positions->isEmpty())
                                    <p class="text-xs text-amber-600 mt-1.5">No positions yet — <a href="{{ route('admin.positions.index') }}" class="underline font-medium">add one first</a>.</p>
                                @endif
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                                <select name="role" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white">
                                    <option value="staff" @selected(old('role', $user->role) === 'staff')>Staff</option>
                                    <option value="it_support" @selected(old('role', $user->role) === 'it_support')>IT Support</option>
                                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Branch</label>
                                <select name="location" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    <option value="" disabled @selected(old('location', $user->location) === null)>Select branch</option>
                                    @foreach(\App\Models\Asset::locations() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('location', $user->location) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('location') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <label class="flex items-center gap-2.5 rounded-lg border border-amber-100 bg-amber-50/60 px-4 py-3 cursor-pointer">
                        <input type="checkbox" name="is_vip" value="1" class="rounded border-gray-300 text-amber-500 focus:ring-amber-500" @checked(old('is_vip', $user->is_vip))>
                        <span class="text-sm text-gray-700">Mark as <span class="font-semibold">VIP</span> — tickets auto-escalate to Critical priority</span>
                    </label>

                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm hover:opacity-90 transition" style="background-color:#1a6b3c;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Assets --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="flex items-center gap-2.5 px-6 sm:px-8 py-5 border-b border-gray-100">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-700 shrink-0">
                    <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg>
                </span>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800">Assets <span class="ml-1 text-xs font-normal text-gray-400">({{ $assets->count() }})</span></p>
                    <p class="text-xs text-gray-400">Equipment issued to {{ $user->name }}</p>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <p class="text-xs text-gray-500 bg-gray-50 border border-gray-100 rounded-lg px-3.5 py-2.5 mb-5">
                    Tags are generated automatically as <span class="font-medium text-gray-600">Company-Location-Department-Type-Number</span>, e.g.
                    <span class="font-mono font-semibold text-gray-700 bg-white border border-gray-200 rounded px-1.5 py-0.5">CFI-CEB-IT-LT-001</span>.
                    Each device type keeps its own running number — a laptop and its monitor get separate tags (e.g. <span class="font-mono">…-LT-001</span> and <span class="font-mono">…-MN-001</span>), both assignable to the same person. Click <strong>Edit</strong> on a row below to correct its number if needed.
                </p>

                @if($assets->isEmpty())
                    <div class="flex flex-col items-center justify-center text-center py-10 mb-6 rounded-lg border border-dashed border-gray-200 bg-gray-50/50">
                        <svg class="w-9 h-9 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg>
                        <p class="text-sm text-gray-400">No assets assigned yet.</p>
                    </div>
                @else
                    {{-- A single flexible data column keeps every field (including the inline
                         edit inputs) always on-screen — nothing is ever hidden behind a
                         breakpoint, so editing works the same on any screen and the table
                         never needs to scroll sideways. --}}
                    <div class="border border-gray-200 rounded-lg overflow-hidden mb-6">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50/80 border-b border-gray-200">
                                    <th class="px-3 sm:px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Asset</th>
                                    <th class="hidden sm:table-cell px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th class="w-24 sm:w-40 px-3 sm:px-4 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($assets as $asset)
                                    <tr class="hover:bg-gray-50/60 transition-colors" x-data="{ editing: false }">
                                        <td class="px-3 sm:px-4 py-2.5 max-w-0 w-full align-top">
                                            <div x-show="!editing" class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                <span class="inline-flex font-mono text-xs font-semibold text-gray-700 bg-gray-100 rounded px-1.5 py-1 shrink-0">{{ $asset->asset_tag }}</span>
                                                <span class="text-gray-700 truncate">{{ $asset->device_name }}</span>
                                                <span class="text-xs text-gray-400">{{ $asset->serial_number ?? '—' }}</span>
                                                <span class="sm:hidden"><x-asset-status-badge :status="$asset->status" /></span>
                                            </div>
                                            <div x-show="editing" x-cloak class="space-y-1.5">
                                                <div class="flex items-center gap-1">
                                                    <span class="font-mono text-xs text-gray-400 shrink-0">{{ $asset->company }}-{{ $asset->location }}-{{ $asset->department->code }}-{{ $asset->type }}-</span>
                                                    <input type="number" min="1" max="999" name="sequence" form="asset-form-{{ $asset->id }}" value="{{ $asset->sequence }}"
                                                        class="w-16 rounded-md border-gray-300 text-xs py-1 font-mono focus:border-green-700 focus:ring-green-700" title="Sequence number">
                                                </div>
                                                <input type="text" name="device_name" form="asset-form-{{ $asset->id }}" value="{{ $asset->device_name }}"
                                                    placeholder="Device name"
                                                    class="w-full rounded-md border-gray-300 text-xs py-1 focus:border-green-700 focus:ring-green-700" required>
                                                <input type="text" name="serial_number" form="asset-form-{{ $asset->id }}" value="{{ $asset->serial_number }}"
                                                    placeholder="Serial number"
                                                    class="w-full rounded-md border-gray-300 text-xs py-1 focus:border-green-700 focus:ring-green-700">
                                                <select name="status" form="asset-form-{{ $asset->id }}"
                                                    class="sm:hidden w-full rounded-md border-gray-300 text-xs py-1 bg-white focus:border-green-700 focus:ring-green-700">
                                                    @foreach(\App\Models\Asset::STATUSES as $value => $label)
                                                        <option value="{{ $value }}" @selected($asset->status === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </td>
                                        <td class="hidden sm:table-cell px-4 py-2.5 align-top">
                                            <div x-show="!editing"><x-asset-status-badge :status="$asset->status" /></div>
                                            <select x-show="editing" x-cloak name="status" form="asset-form-{{ $asset->id }}"
                                                class="w-full rounded-md border-gray-300 text-xs py-1 bg-white focus:border-green-700 focus:ring-green-700">
                                                @foreach(\App\Models\Asset::STATUSES as $value => $label)
                                                    <option value="{{ $value }}" @selected($asset->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-3 sm:px-4 py-2.5 text-right whitespace-nowrap align-top">
                                            <form id="asset-form-{{ $asset->id }}" method="POST" action="{{ route('admin.assets.update', $asset) }}" class="hidden">
                                                @csrf @method('PUT')
                                                {{-- Keep the current owner as-is on a normal Save — only the
                                                     dedicated Unassign action (below) should clear it. --}}
                                                <input type="hidden" name="user_id" value="{{ $asset->user_id }}">
                                                <input type="hidden" name="notes" value="{{ $asset->notes }}">
                                            </form>
                                            <button type="button" @click="editing = !editing" class="text-xs text-gray-500 hover:text-gray-700 mr-2">
                                                <span x-text="editing ? 'Cancel' : 'Edit'"></span>
                                            </button>
                                            <button x-show="editing" x-cloak type="submit" form="asset-form-{{ $asset->id }}" class="text-xs text-green-700 font-medium mr-2">Save</button>
                                            {{-- "Remove" here only unassigns the asset from this user — it stays in
                                                 inventory. Deleting an asset outright is only available from the
                                                 main Assets page. --}}
                                            <form id="unassign-form-{{ $asset->id }}" method="POST" action="{{ route('admin.assets.update', $asset) }}" class="hidden">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="user_id" value="">
                                                <input type="hidden" name="device_name" value="{{ $asset->device_name }}">
                                                <input type="hidden" name="serial_number" value="{{ $asset->serial_number }}">
                                                <input type="hidden" name="status" value="{{ $asset->status }}">
                                                <input type="hidden" name="sequence" value="{{ $asset->sequence }}">
                                                <input type="hidden" name="assigned_date" value="{{ $asset->assigned_date?->toDateString() }}">
                                                <input type="hidden" name="notes" value="{{ $asset->notes }}">
                                            </form>
                                            <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'unassign-asset-{{ $asset->id }}')" class="text-xs text-amber-600 hover:text-amber-700">Unassign</button>

                                            <x-modal name="unassign-asset-{{ $asset->id }}" maxWidth="sm" focusable>
                                                <div class="p-6">
                                                    <div class="flex items-start gap-4">
                                                        <span class="flex items-center justify-center w-10 h-10 rounded-full shrink-0 bg-amber-50 text-amber-600">
                                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                                        </span>
                                                        <div class="flex-1 min-w-0 pt-0.5">
                                                            <h2 class="text-base font-semibold text-gray-800">Unassign this asset?</h2>
                                                            <p class="mt-1.5 text-sm text-gray-500">{{ $asset->asset_tag }} will be removed from {{ $user->name }} and stay in inventory as unassigned.</p>
                                                        </div>
                                                    </div>
                                                    <div class="mt-6 flex justify-end gap-3">
                                                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</button>
                                                        <button type="submit" form="unassign-form-{{ $asset->id }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 transition">Unassign</button>
                                                    </div>
                                                </div>
                                            </x-modal>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($assetDepartments->isEmpty())
                    <div class="flex items-start gap-2.5 text-sm text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-4 py-3">
                        <svg class="w-4.5 h-4.5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        <span>No departments have an asset code yet. Add one from the <a href="{{ route('admin.departments.index') }}" class="underline font-medium">Departments</a> page first.</span>
                    </div>
                @else
                    @if ($errors->hasAny(['company', 'location', 'department_id', 'type', 'device_name', 'serial_number', 'sequence']))
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <p class="font-semibold mb-1">Couldn't save asset:</p>
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach(['company', 'location', 'department_id', 'type', 'device_name', 'serial_number', 'sequence'] as $field)
                                    @error($field)
                                        <li>{{ $message }}</li>
                                    @enderror
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-5"
                         x-data="{
                            company: '{{ old('company', array_key_first(\App\Models\Asset::COMPANIES)) }}',
                            location: '{{ old('location', array_key_first(\App\Models\Asset::locations())) }}',
                            department: '{{ old('department_id', $assetDepartments->first()->id) }}',
                            type: '{{ old('type', array_key_first(\App\Models\Asset::TYPES)) }}',
                            sequence: '{{ old('sequence', '') }}',
                            deptCodes: {{ $assetDepartments->pluck('code', 'id')->toJson() }}
                         }">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="w-4 h-4 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Assign a new asset</p>
                        </div>

                        <form method="POST" action="{{ route('admin.users.assets.store', $user) }}" class="space-y-4">
                            @csrf

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Company</label>
                                    <select name="company" x-model="company" class="block w-full rounded-lg border-gray-300 text-sm bg-white focus:border-green-700 focus:ring-green-700" required>
                                        @foreach(\App\Models\Asset::COMPANIES as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Location</label>
                                    <select name="location" x-model="location" class="block w-full rounded-lg border-gray-300 text-sm bg-white focus:border-green-700 focus:ring-green-700" required>
                                        @foreach(\App\Models\Asset::locations() as $value => $label)
                                            <option value="{{ $value }}">{{ $label }} ({{ $value }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Department</label>
                                    <select name="department_id" x-model="department" class="block w-full rounded-lg border-gray-300 text-sm bg-white focus:border-green-700 focus:ring-green-700" required>
                                        @foreach($assetDepartments as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                                    <select name="type" x-model="type" class="block w-full rounded-lg border-gray-300 text-sm bg-white focus:border-green-700 focus:ring-green-700" required>
                                        @foreach(\App\Models\Asset::TYPES as $value => $label)
                                            <option value="{{ $value }}">{{ $label }} ({{ $value }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Device</label>
                                    <input type="text" name="device_name" value="{{ old('device_name') }}" placeholder="e.g. Dell Latitude 5420"
                                        class="block w-full rounded-lg border-gray-300 text-sm bg-white focus:border-green-700 focus:ring-green-700" required>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Serial Number <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <input type="text" name="serial_number" value="{{ old('serial_number') }}"
                                        class="block w-full rounded-lg border-gray-300 text-sm bg-white focus:border-green-700 focus:ring-green-700">
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Tag Number <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <input type="number" name="sequence" x-model="sequence" min="1" max="999" placeholder="Auto"
                                        class="block w-full rounded-lg border-gray-300 text-sm bg-white focus:border-green-700 focus:ring-green-700">
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 -mt-2">Leave Tag Number blank to use the next available number, or type one to match an existing physical label.</p>

                            <div class="flex items-center justify-between pt-1">
                                <p class="text-xs text-gray-400">
                                    Tag preview:
                                    <span class="font-mono font-semibold text-gray-600 bg-white border border-gray-200 rounded px-1.5 py-0.5"
                                          x-text="company + '-' + location + '-' + (deptCodes[department] ?? '???') + '-' + type + '-' + (sequence ? String(sequence).padStart(3, '0') : 'XXX')"></span>
                                </p>
                                <button type="submit" class="px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm hover:opacity-90 transition" style="background-color:#1a6b3c;">Assign Asset</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
