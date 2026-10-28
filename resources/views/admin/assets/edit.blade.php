<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Asset</h2>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data='{
            editUserId: "{{ old("user_id", $asset->user_id ?? "") }}",
            editUserQuery: "",
            userDropdownOpen: false,
            userDirectory: @json($users->map(fn ($u) => ["id" => $u->id, "name" => $u->name, "username" => $u->username]), JSON_HEX_APOS),
            assignTo: "{{ old("assignment", $asset->assignment) }}",
            assignTouched: true,
            suggested: false,
            deptId: "{{ old("department_id", $asset->department_id ?? "") }}",
            itDeptId: "{{ $itDepartmentId ?? "" }}",
            typeCode: "{{ old("type", $asset->type) }}",
            departmentTypes: @json(\App\Models\Asset::DEPARTMENT_TYPES),
            setMode(mode) {
                this.assignTouched = true;
                this.suggested = false;
                this.assignTo = mode;
                if (mode === "department") this.syncTag();
            },
            // Network equipment normally belongs to the IT Department: suggest it until the admin chooses.
            suggest() {
                if (this.assignTouched || !this.itDeptId) return;
                if (this.departmentTypes.includes(this.typeCode)) {
                    this.assignTo = "department";
                    this.deptId = this.itDeptId;
                    this.suggested = true;
                    this.syncTag();
                }
            },
            // The tag checker listens for "change" on the Tag > Department select.
            syncTag() {
                this.$nextTick(() => {
                    const el = this.$refs.tagDept;
                    if (el) { el.dispatchEvent(new Event("change", { bubbles: true })); }
                });
            },
            get selectedUser() {
                return this.userDirectory.find(u => u.id == this.editUserId) || null;
            },
            get filteredUsers() {
                const q = this.editUserQuery.trim().toLowerCase();
                const list = q === ""
                    ? this.userDirectory
                    : this.userDirectory.filter(u => u.name.toLowerCase().includes(q) || (u.username ?? '').toLowerCase().includes(q));
                return list.slice(0, 30);
            },
            initials(name) {
                if (!name) return "?";
                return name.trim().split(/\s+/).slice(0, 2).map(s => s[0].toUpperCase()).join("");
            },
            selectUser(u) {
                this.editUserId = u.id;
                this.editUserQuery = "";
                this.userDropdownOpen = false;
            },
            clearUser() {
                this.editUserId = "";
                this.editUserQuery = "";
                this.userDropdownOpen = false;
            },
         }'>
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="flex items-center gap-2.5 px-6 sm:px-8 py-5 border-b border-gray-100">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-700 shrink-0">
                    <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-800 font-mono">{{ $asset->asset_tag }}</p>
                    <p class="text-xs text-gray-400">{{ $asset->device_name }}</p>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                @if(session('error'))
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3.5">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3.5">
                        <p class="text-sm font-semibold text-red-800 mb-1">Please fix the following:</p>
                        <ul class="text-sm text-red-700 list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('assets.update', $asset) }}" class="space-y-6" novalidate>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="user_id" :value="assignTo === 'person' ? editUserId : ''">

                    {{-- Owner --}}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Assign to</p>
                        {{-- Who has it: a person, a whole department (shared equipment), or nobody yet --}}
                        <input type="hidden" name="assignment" :value="assignTo">
                        <div class="grid grid-cols-3 gap-2 mb-4" role="radiogroup" aria-label="Assign to">
                            @foreach([
                                'person' => ['A person', 'Someone uses it', 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
                                'department' => ['A department', 'Shared equipment', 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
                                'stock' => ['Not assigned', 'Keep in stock', 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'],
                            ] as $mode => [$title, $hint, $icon])
                                <button type="button" role="radio" :aria-checked="assignTo === '{{ $mode }}'" @click="setMode('{{ $mode }}')"
                                        :class="assignTo === '{{ $mode }}' ? 'border-green-700 bg-green-50 text-green-800 ring-2 ring-green-700/20' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                                        class="flex flex-col items-center gap-1 rounded-lg border px-2 py-3 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                                    <span class="text-sm font-medium leading-tight">{{ $title }}</span>
                                    <span class="text-[11px] font-normal text-gray-400 leading-tight hidden sm:block">{{ $hint }}</span>
                                </button>
                            @endforeach
                        </div>

                        <div class="relative" x-show="assignTo === 'person'" x-cloak @click.outside="userDropdownOpen = false">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Person</label>

                            {{-- Selected owner: a proper card, not text crammed into an input --}}
                            <div x-show="editUserId && selectedUser" x-cloak
                                 class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50/60 px-3.5 py-2.5">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="flex items-center justify-center w-9 h-9 rounded-full bg-green-700 text-white text-xs font-semibold shrink-0"
                                          x-text="initials(selectedUser?.name)"></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate" x-text="selectedUser?.name"></p>
                                        <p class="text-xs text-gray-500 truncate" x-text="selectedUser?.username"></p>
                                    </div>
                                </div>
                                <button type="button" @click="clearUser()" class="text-xs font-medium text-green-700 hover:underline shrink-0">Change</button>
                            </div>

                            {{-- Search / pick state --}}
                            <div x-show="!editUserId" x-cloak>
                                <div class="relative">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                                    <input type="text"
                                           x-model="editUserQuery"
                                           @focus="userDropdownOpen = true"
                                           @input="userDropdownOpen = true"
                                           placeholder="Search by name or username, or leave blank to unassign..."
                                           autocomplete="off"
                                           class="block w-full pl-9 pr-9 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                                    <button type="button" x-show="editUserQuery" @click="editUserQuery = ''" x-cloak
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>

                                <div x-show="userDropdownOpen" x-cloak
                                     class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto">
                                    <button type="button" @click="clearUser(); userDropdownOpen = false"
                                            class="w-full text-left px-3.5 py-2.5 hover:bg-green-50 transition border-b border-gray-100">
                                        <p class="text-sm font-medium text-gray-600">Unassigned</p>
                                        <p class="text-xs text-gray-400">No owner — asset stays in inventory</p>
                                    </button>
                                    <template x-for="u in filteredUsers" :key="u.id">
                                        <button type="button" @click="selectUser(u)"
                                                class="w-full flex items-center gap-2.5 text-left px-3.5 py-2.5 hover:bg-green-50 transition border-b border-gray-50 last:border-0">
                                            <span class="flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 text-gray-500 text-[10px] font-semibold shrink-0"
                                                  x-text="initials(u.name)"></span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate" x-text="u.name"></p>
                                                <p class="text-xs text-gray-400 truncate" x-text="u.username || '—'"></p>
                                            </div>
                                        </button>
                                    </template>
                                    <div x-show="filteredUsers.length === 0" class="px-3.5 py-3 text-sm text-gray-400">No matching users</div>
                                </div>
                                <p class="text-xs text-gray-400 mt-1.5">Not assigning yet? Leave this blank — the asset stays in inventory.</p>
                            </div>
                        </div>

                        {{-- Department: shared equipment such as a router, switch or office telephone --}}
                        <div x-show="assignTo === 'department'" x-cloak class="rounded-lg border border-sky-200 bg-sky-50/60 p-4 space-y-3">
                            <div x-show="suggested" x-cloak class="flex items-start gap-2 rounded-md bg-white/80 px-3 py-2 text-xs text-sky-900 ring-1 ring-sky-200">
                                <svg class="w-4 h-4 mt-0.5 shrink-0 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                                <span>Network equipment usually belongs to the <strong>IT Department</strong>, so we selected it for you. Prefer a person? Pick <button type="button" class="font-semibold underline" @click="setMode('person')">A person</button>.</span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Department</label>
                                <select x-model="deptId" @change="syncTag()" aria-label="Department this asset is assigned to"
                                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white">
                                    <option value="">Select department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="text-xs text-gray-500">Shared equipment belongs to the whole department, not one person. This is also the department used in the asset tag below.</p>
                            @unless($itDepartmentId)
                                <p class="text-xs text-amber-700">There's no IT Department yet — add one on the Departments page to assign network equipment to it.</p>
                            @endunless
                        </div>

                        {{-- Not assigned --}}
                        <div x-show="assignTo === 'stock'" x-cloak class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                            This asset goes into stock as <strong>unassigned</strong>. You can assign it to a person or a department any time from Edit.
                        </div>
                    </div>

                    {{-- Tag --}}
                    <div class="pt-5 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Tag</p>
                        <div class="grid sm:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Company</label>
                                <select name="company" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    @foreach(\App\Models\Asset::COMPANIES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('company', $asset->company) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Location</label>
                                <select name="location" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    @foreach(\App\Models\Asset::locations() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('location', $asset->location) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Device Type</label>
                                <select name="type" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    @foreach(\App\Models\Asset::TYPES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type', $asset->type) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Department</label>
                                <select name="department_id" x-model="deptId" x-ref="tagDept" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" @selected((string) old('department_id', $asset->department_id) === (string) $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Tag Number</label>
                                <input type="number" name="sequence" min="1" max="999" value="{{ old('sequence', $asset->sequence) }}" required
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                                <p class="text-xs text-gray-400 mt-1.5">
                                    Becomes the last part of the tag, e.g. {{ $asset->company }}-{{ $asset->location }}-{{ $asset->department->code ?? '' }}-{{ $asset->type }}-<strong>{{ str_pad((string) $asset->sequence, 3, '0', STR_PAD_LEFT) }}</strong>. Changing any tag field (company, location, department, type, or number) regenerates the tag.
                                </p>
                                <x-asset-tag-checker :ignore="$asset->id" :current-tag="$asset->asset_tag" />
                            </div>
                        </div>
                    </div>

                    {{-- Device --}}
                    <div class="pt-5 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Device</p>
                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Device Name</label>
                                <input type="text" name="device_name" value="{{ old('device_name', $asset->device_name) }}"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Serial Number</label>
                                <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Assigned Date</label>
                                <input type="date" name="assigned_date" value="{{ old('assigned_date', $asset->assigned_date?->toDateString()) }}" max="{{ now()->toDateString() }}"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                                <select name="status" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    @foreach(\App\Models\Asset::STATUSES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $asset->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <x-asset-notes-field :value="old('notes', $asset->notes)" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <a href="{{ session('list_url.assets', route('assets.index')) }}" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm hover:opacity-90 transition" style="background-color:#1a6b3c;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
