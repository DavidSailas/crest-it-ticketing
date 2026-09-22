<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Assign New Asset</h2>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data='{
            newUserId: "{{ old("user_id", "") }}",
            newUserQuery: "",
            userDropdownOpen: false,
            userDirectory: @json($users->map(fn ($u) => ["id" => $u->id, "name" => $u->name, "email" => $u->email]), JSON_HEX_APOS),
            get selectedUser() {
                return this.userDirectory.find(u => u.id == this.newUserId) || null;
            },
            get filteredUsers() {
                const q = this.newUserQuery.trim().toLowerCase();
                const list = q === ""
                    ? this.userDirectory
                    : this.userDirectory.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
                return list.slice(0, 30);
            },
            initials(name) {
                if (!name) return "?";
                return name.trim().split(/\s+/).slice(0, 2).map(s => s[0].toUpperCase()).join("");
            },
            selectUser(u) {
                this.newUserId = u.id;
                this.newUserQuery = "";
                this.userDropdownOpen = false;
            },
            clearUser() {
                this.newUserId = "";
                this.newUserQuery = "";
                this.userDropdownOpen = false;
            },
         }'>
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="flex items-center gap-2.5 px-6 sm:px-8 py-5 border-b border-gray-100">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-700 shrink-0">
                    <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-800">New Asset</p>
                    <p class="text-xs text-gray-400">Device details, owner, and tag number</p>
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

                <form method="POST" action="{{ route('assets.store') }}" class="space-y-6" novalidate>
                    @csrf
                    <input type="hidden" name="user_id" :value="newUserId">

                    {{-- Owner --}}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Owner</p>
                        <div class="relative" @click.outside="userDropdownOpen = false">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Assign To <span class="font-normal text-gray-400">(optional)</span></label>

                            {{-- Selected owner: a proper card, not text crammed into an input --}}
                            <div x-show="newUserId && selectedUser" x-cloak
                                 class="flex items-center justify-between gap-3 rounded-lg border border-green-200 bg-green-50/60 px-3.5 py-2.5">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="flex items-center justify-center w-9 h-9 rounded-full bg-green-700 text-white text-xs font-semibold shrink-0"
                                          x-text="initials(selectedUser?.name)"></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate" x-text="selectedUser?.name"></p>
                                        <p class="text-xs text-gray-500 truncate" x-text="selectedUser?.email"></p>
                                    </div>
                                </div>
                                <button type="button" @click="clearUser()" class="text-xs font-medium text-green-700 hover:underline shrink-0">Change</button>
                            </div>

                            {{-- Search / pick state --}}
                            <div x-show="!newUserId" x-cloak>
                                <div class="relative">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                                    <input type="text"
                                           x-model="newUserQuery"
                                           @focus="userDropdownOpen = true"
                                           @input="userDropdownOpen = true"
                                           placeholder="Search by name or email, or leave blank to assign later..."
                                           autocomplete="off"
                                           class="block w-full pl-9 pr-9 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                                    <button type="button" x-show="newUserQuery" @click="newUserQuery = ''" x-cloak
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>

                                <div x-show="userDropdownOpen" x-cloak
                                     class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto">
                                    <button type="button" @click="clearUser(); userDropdownOpen = false"
                                            class="w-full text-left px-3.5 py-2.5 hover:bg-green-50 transition border-b border-gray-100">
                                        <p class="text-sm font-medium text-gray-600">Unassigned</p>
                                        <p class="text-xs text-gray-400">No owner — asset goes into inventory</p>
                                    </button>
                                    <template x-for="u in filteredUsers" :key="u.id">
                                        <button type="button" @click="selectUser(u)"
                                                class="w-full flex items-center gap-2.5 text-left px-3.5 py-2.5 hover:bg-green-50 transition border-b border-gray-50 last:border-0">
                                            <span class="flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 text-gray-500 text-[10px] font-semibold shrink-0"
                                                  x-text="initials(u.name)"></span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate" x-text="u.name"></p>
                                                <p class="text-xs text-gray-400 truncate" x-text="u.email"></p>
                                            </div>
                                        </button>
                                    </template>
                                    <div x-show="filteredUsers.length === 0" class="px-3.5 py-3 text-sm text-gray-400">No matching users</div>
                                </div>
                                <p class="text-xs text-gray-400 mt-1.5">Not assigning yet? Leave this blank — you can assign it to someone later.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Tag --}}
                    <div class="pt-5 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Tag</p>
                        <div class="grid sm:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Company</label>
                                <select name="company" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    <option value="" disabled @selected(old('company') === null)>Select company</option>
                                    @foreach(\App\Models\Asset::COMPANIES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('company') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Location</label>
                                <select name="location" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    <option value="" disabled @selected(old('location') === null)>Select location</option>
                                    @foreach(\App\Models\Asset::locations() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('location') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Device Type</label>
                                <select name="type" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    <option value="" disabled @selected(old('type') === null)>Select type</option>
                                    @foreach(\App\Models\Asset::TYPES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Department</label>
                                <select name="department_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    <option value="" disabled @selected(old('department_id') === null)>Select department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Tag Number</label>
                                <input type="number" name="sequence" min="1" max="999" value="{{ old('sequence') }}" placeholder="Leave blank to auto-generate"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                                <p class="text-xs text-gray-400 mt-1.5">
                                    Becomes the last part of the tag, e.g. CFI-CEB-IT-LT-<strong>001</strong>. Leave blank to use the next available number for this company/location/department/type — or type your own (matches an existing physical label, for example).
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Device --}}
                    <div class="pt-5 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Device</p>
                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Device Name</label>
                                <input type="text" name="device_name" value="{{ old('device_name') }}" placeholder="e.g. Dell Latitude 5420"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Serial Number</label>
                                <input type="text" name="serial_number" value="{{ old('serial_number') }}"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Assigned Date</label>
                                <input type="date" name="assigned_date" value="{{ old('assigned_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                                <p class="text-xs text-gray-400 mt-1.5">Defaults to today — change it if the device was actually handed over earlier.</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
                                <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <a href="{{ route('assets.index') }}" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm hover:opacity-90 transition" style="background-color:#1a6b3c;">Assign Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
