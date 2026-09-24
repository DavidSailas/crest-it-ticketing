<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Submit a New Ticket</h2>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Masthead --}}
        <div class="relative overflow-hidden rounded-xl mb-6" style="background-color:#123f24;">
            <svg class="absolute -right-6 -top-10 w-48 h-48 opacity-[0.07] pointer-events-none" viewBox="0 0 100 100" fill="none" stroke="white" stroke-width="1.5">
                <rect x="14" y="30" width="72" height="44" rx="4"/>
                <path d="M14 30 L50 12 L86 30"/>
                <circle cx="50" cy="52" r="7"/>
                <path d="M50 52 v10 M46 62 h8"/>
            </svg>
            <div class="relative px-6 py-6 sm:px-8 sm:py-7">
                <p class="text-white text-lg font-semibold">New Support Request</p>
                <p class="text-green-100/80 text-sm mt-1">Give us a few details and we'll route this to the right person.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            {{-- FORM --}}
            @php
                $myDepartment = auth()->user()->department?->name;
                $myBranch = auth()->user()->branch_name;
                $profileComplete = filled($myDepartment) && filled($myBranch);
            @endphp
            <div class="lg:col-span-2 bg-white shadow-sm rounded-xl border border-gray-100">
                <form method="POST" action="{{ route('tickets.store') }}" class="p-6 sm:p-8 space-y-8" enctype="multipart/form-data"
                      x-data="{
                         forWhom: '{{ old('on_behalf_of_user_id') || old('on_behalf_of_name') ? 'colleague' : 'self' }}',
                         colleagueId: '{{ old('on_behalf_of_user_id', '') }}',
                         directoryOpen: false,
                         colleagues: {{ $colleagues->toJson() }},
                         selfProfileComplete: @js($profileComplete),
                         get selectedColleague() {
                             return this.colleagues.find(c => c.id == this.colleagueId) || null;
                         },
                         // Which profile actually gets used to route this ticket: the
                         // colleague's, if one is picked from the directory — otherwise
                         // the submitter's own (covers 'Myself' and a free-typed name).
                         get targetProfileComplete() {
                             if (this.forWhom === 'colleague' && this.colleagueId && this.selectedColleague) {
                                 return !!this.selectedColleague.profile_complete;
                             }
                             return this.selfProfileComplete;
                         }
                      }">
                    @csrf

                    {{-- Section: Request --}}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Request</p>
                        <p class="text-xs text-gray-400 mb-4">Your department and office are pulled automatically from your profile — no need to pick them every time.</p>

                        @if($profileComplete)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" /></svg>
                                    <div class="min-w-0">
                                        <p class="text-xs text-gray-400">Department</p>
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $myDepartment }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                    <div class="min-w-0">
                                        <p class="text-xs text-gray-400">Office / Branch</p>
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $myBranch }}</p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-2" x-show="!(forWhom === 'colleague' && colleagueId)">Not right? Ask an administrator to update your profile — it's used to route tickets automatically.</p>
                            <p class="text-xs text-gray-400 mt-2" x-show="forWhom === 'colleague' && colleagueId" x-cloak>When you're submitting for a colleague, we route using <span class="font-medium text-gray-500" x-text="selectedColleague?.name"></span>'s department and office instead of yours.</p>
                        @else
                            <div class="flex items-start gap-2.5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3" x-show="!(forWhom === 'colleague' && colleagueId && selectedColleague?.profile_complete)">
                                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                <p class="text-sm text-amber-800">
                                    Your department and/or office branch aren't set on your profile yet, so this ticket can't be routed automatically. Please ask an administrator to update your profile before submitting — or, if this is for a colleague, pick them from the directory below and we'll route using their profile instead.
                                </p>
                            </div>
                            <div class="flex items-start gap-2.5 rounded-lg border border-green-200 bg-green-50 px-4 py-3" x-show="forWhom === 'colleague' && colleagueId && selectedColleague?.profile_complete" x-cloak>
                                <svg class="w-4 h-4 text-green-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <p class="text-sm text-green-800">
                                    Your own profile isn't complete, but that's fine here — we'll route this using <span class="font-medium" x-text="selectedColleague?.name"></span>'s department and office instead.
                                </p>
                            </div>
                        @endif
                        @error('department') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                        @error('location') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                    </div>

                    {{-- Section: Classification --}}
                    <div class="pt-6 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">Classification</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">What do you need help with?</label>
                                <div class="relative">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>
                                    <select id="category" name="category" class="block w-full rounded-lg border-gray-300 pl-10 focus:border-green-700 focus:ring-green-700 text-sm bg-white" required>
                                        <option value="">Select a category</option>
                                        @foreach([
                                            'Computer / Laptop',
                                            'Internet / Network',
                                            'Email / Microsoft 365',
                                            'Password / Account',
                                            'Printer',
                                            'Mobile Device',
                                            'Software / Application',
                                            'Security',
                                            'Phone / Communication',
                                            'Hardware / Office Equipment',
                                            'Access Request',
                                            'Other',
                                        ] as $need)
                                            <option value="{{ $need }}" @selected(old('category') === $need)>{{ $need }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Priority</label>
                                @if(auth()->user()->is_vip)
                                    <div class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2.5">
                                        <span class="text-amber-600">★</span>
                                        <span class="text-sm font-medium text-amber-800">P1 · Critical (VIP account)</span>
                                    </div>
                                    <input type="hidden" name="priority" value="critical">
                                    <p class="text-xs text-gray-400 mt-1">Your account is marked VIP, so this ticket is automatically set to Critical.</p>
                                @else
                                    <div x-data="{ priority: '{{ old('priority', 'low') }}' }">
                                        <select name="priority" id="priority" x-model="priority" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm bg-white" required>
                                            <option value="low">P4 · Low</option>
                                            <option value="medium">P3 · Medium</option>
                                            <option value="high">P2 · High</option>
                                            <option value="critical">P1 · Critical</option>
                                        </select>

                                        <div x-show="priority === 'critical'" x-cloak class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3.5 py-3">
                                            <label class="flex items-start gap-2.5 cursor-pointer">
                                                <input type="checkbox" name="confirms_multiple_affected" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-600 mt-0.5" :required="priority === 'critical'">
                                                <span class="text-xs text-red-800">
                                                    I confirm this issue impacts multiple personnel or an organizational unit (e.g., shared infrastructure, core servers, or enterprise-wide services).
                                                </span>
                                            </label>
                                        </div>
                                        <p x-show="priority !== 'critical'" class="text-xs text-gray-400 mt-1">If this issue is isolated to your workstation, please select High.</p>
                                    </div>
                                @endif
                                @error('priority') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                @error('confirms_multiple_affected') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section: Who is this for --}}
                    <div class="pt-6 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Who is this for?</p>
                        <p class="text-xs text-gray-400 mb-4">Choose "A colleague" when their computer won't start or they can't log in, so they can't raise the ticket themselves.</p>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" value="self" x-model="forWhom" @change="colleagueId = ''" class="peer sr-only">
                                <div class="flex items-center gap-2.5 px-4 py-3 rounded-lg border border-gray-300 text-gray-600 transition peer-checked:border-green-700 peer-checked:bg-green-50 peer-checked:text-green-800">
                                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                    <span class="text-sm font-medium">Myself</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" value="colleague" x-model="forWhom" class="peer sr-only">
                                <div class="flex items-center gap-2.5 px-4 py-3 rounded-lg border border-gray-300 text-gray-600 transition peer-checked:border-green-700 peer-checked:bg-green-50 peer-checked:text-green-800">
                                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                                    <span class="text-sm font-medium">A colleague</span>
                                </div>
                            </label>
                        </div>

                        <div x-show="forWhom === 'colleague'" x-cloak class="mt-4 space-y-3 rounded-lg bg-gray-50/70 border border-gray-100 p-4">
                            <div class="relative"
                                 x-data="{
                                    query: '{{ old('on_behalf_of_user_id') ? addslashes(optional($colleagues->firstWhere('id', (int) old('on_behalf_of_user_id')))->name) : '' }}',
                                    get results() {
                                        const q = this.query.trim().toLowerCase();
                                        if (!q) return this.colleagues;
                                        return this.colleagues.filter(c =>
                                            (c.name ?? '').toLowerCase().includes(q) || (c.email ?? '').toLowerCase().includes(q)
                                        );
                                    },
                                    pick(c) { this.query = c.name; colleagueId = c.id; directoryOpen = false; },
                                    reset() { this.query = ''; colleagueId = ''; directoryOpen = false; $refs.search.focus(); }
                                 }"
                                 @click.outside="directoryOpen = false">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Select from directory</label>

                                <div class="relative">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                                    <input type="text" x-ref="search" x-model="query" @focus="directoryOpen = true" @input="directoryOpen = true; colleagueId = ''"
                                        :disabled="forWhom !== 'colleague'" autocomplete="off" placeholder="Search by name or email…"
                                        class="block w-full rounded-lg border-gray-300 text-sm bg-white pl-9 pr-8 focus:border-green-700 focus:ring-green-700">
                                    <button type="button" x-show="query" @click="reset()" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>

                                <div x-show="directoryOpen" x-cloak class="absolute z-10 mt-1.5 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    <button type="button" @click="reset()" class="w-full text-left px-3.5 py-2.5 text-sm text-gray-500 hover:bg-gray-50 border-b border-gray-100">
                                        Not listed — I'll type their name below
                                    </button>
                                    <template x-for="c in results" :key="c.id">
                                        <button type="button" @click="pick(c)"
                                            class="w-full text-left px-3.5 py-2 flex items-center gap-2.5 hover:bg-green-50"
                                            :class="colleagueId == c.id ? 'bg-green-50' : ''">
                                            <span class="flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 text-gray-500 text-[11px] font-semibold shrink-0" x-text="(c.name || '?').split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()"></span>
                                            <span class="min-w-0 flex-1">
                                                <span class="flex items-center gap-1.5">
                                                    <span class="block text-sm font-medium text-gray-800 truncate" x-text="c.name"></span>
                                                    <span x-show="!c.profile_complete" class="shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700">Profile incomplete</span>
                                                </span>
                                                <span class="block text-xs text-gray-400 truncate" x-text="c.email || '—'"></span>
                                            </span>
                                        </button>
                                    </template>
                                    <p x-show="results.length === 0" class="px-3.5 py-3 text-xs text-gray-400 text-center">No one matches "<span x-text="query"></span>".</p>
                                </div>

                                <input type="hidden" name="on_behalf_of_user_id" :value="colleagueId">
                                @error('on_behalf_of_user_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div x-show="colleagueId && selectedColleague && !selectedColleague.profile_complete" x-cloak
                                 class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2.5">
                                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                <p class="text-xs text-amber-800">
                                    <span x-text="selectedColleague?.name"></span>'s department/office isn't set on their profile yet, so we can't route this ticket automatically. Pick someone else, or ask an administrator to complete their profile first.
                                </p>
                            </div>

                            <div x-show="!colleagueId" x-cloak>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Full name <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="text" name="on_behalf_of_name" :disabled="forWhom !== 'colleague'"
                                    value="{{ old('on_behalf_of_name') }}" placeholder="e.g. Maria Santos"
                                    class="block w-full rounded-lg border-gray-300 text-sm bg-white focus:border-green-700 focus:ring-green-700">
                                @error('on_behalf_of_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <p class="flex items-start gap-1.5 text-xs text-gray-400">
                                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                                Mention their desk location and extension in the description below so IT can go straight to the right spot.
                            </p>
                        </div>
                    </div>

                    {{-- Section: Description --}}
                    <div class="pt-6 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">Description</p>
                        <textarea name="description" id="description" rows="6" maxlength="2000" placeholder="Describe the issue — what happened, when it started, any error messages you saw..."
                            class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm" required>{{ old('description') }}</textarea>
                        <div class="flex justify-between items-center mt-1">
                            @error('description') <p class="text-red-500 text-xs">{{ $message }}</p> @else <span></span> @enderror
                            <p class="text-xs text-gray-300"><span id="char-count">0</span>/2000</p>
                        </div>
                    </div>

                    {{-- Section: Attachment --}}
                    <div class="pt-6 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Attachment <span class="normal-case font-normal text-gray-400">(optional)</span></p>
                        <p class="text-xs text-gray-400 mb-3">A screenshot or file that helps explain the issue. JPG, PNG, PDF, Word, Excel, or ZIP — up to 10 MB.</p>

                        <div x-data="{ fileName: null, fileSize: null }">
                            <label for="attachment"
                                   x-show="!fileName"
                                   class="flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-gray-200 hover:border-green-600 hover:bg-green-50/40 transition cursor-pointer px-4 py-8 text-center">
                                <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                <span class="text-sm text-gray-500"><span class="font-medium text-green-700">Click to upload</span> or drag and drop</span>
                            </label>

                            <div x-show="fileName" x-cloak
                                 class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <svg class="w-5 h-5 text-green-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.5c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124V6.75A2.25 2.25 0 0110.5 4.5h.375a1.125 1.125 0 011.125 1.125v.375m0 0a2.25 2.25 0 002.25 2.25h.375a1.125 1.125 0 011.125 1.125v2.25a2.25 2.25 0 01-2.25 2.25h-6a2.25 2.25 0 01-2.25-2.25v-.75" /></svg>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-700 truncate" x-text="fileName"></p>
                                        <p class="text-xs text-gray-400" x-text="fileSize"></p>
                                    </div>
                                </div>
                                <button type="button" @click="fileName = null; fileSize = null; document.getElementById('attachment').value = '';"
                                        class="text-gray-400 hover:text-red-600 shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <input type="file" name="attachment" id="attachment" class="hidden"
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip"
                                   @change="
                                       if ($event.target.files.length) {
                                           fileName = $event.target.files[0].name;
                                           fileSize = ($event.target.files[0].size / 1024 / 1024).toFixed(2) + ' MB';
                                       } else {
                                           fileName = null; fileSize = null;
                                       }
                                   ">
                        </div>
                        @error('attachment') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <a href="{{ route('tickets.index') }}" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</a>
                        <button type="submit" :disabled="!targetProfileComplete"
                            :title="!targetProfileComplete ? 'Missing department/office for routing — see the note above.' : ''"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed"
                            style="background-color:#1a6b3c;" onmouseover="this.style.backgroundColor='#145530'" onmouseout="this.style.backgroundColor='#1a6b3c'">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            Submit Ticket
                        </button>
                    </div>
                </form>
            </div>

            {{-- SIDEBAR --}}
            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <p class="text-sm font-semibold text-gray-800 mb-1">Expected response time</p>
                    <p class="text-xs text-gray-400 mb-3.5">e.g., Department-wide mail server failure is P1; an individual email configuration issue is P2.</p>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <span class="inline-flex items-start gap-1.5 text-gray-600 shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-600 mt-1.5"></span>
                                <span><span class="font-semibold text-gray-700">P1</span> Critical</span>
                            </span>
                            <span class="text-right">
                                <span class="block text-gray-800 font-medium">Immediate</span>
                                <span class="block text-xs text-gray-400">Organization-wide or multi-departmental impact</span>
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="inline-flex items-start gap-1.5 text-gray-600 shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-orange-500 mt-1.5"></span>
                                <span><span class="font-semibold text-gray-700">P2</span> High</span>
                            </span>
                            <span class="text-right">
                                <span class="block text-gray-800 font-medium">Within 2 hours</span>
                                <span class="block text-xs text-gray-400">Individual operational blockage</span>
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="inline-flex items-start gap-1.5 text-gray-600 shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5"></span>
                                <span><span class="font-semibold text-gray-700">P3</span> Medium</span>
                            </span>
                            <span class="text-right">
                                <span class="block text-gray-800 font-medium">Within 1 business day</span>
                                <span class="block text-xs text-gray-400">Non-critical issue with a viable workaround</span>
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="inline-flex items-start gap-1.5 text-gray-600 shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 mt-1.5"></span>
                                <span><span class="font-semibold text-gray-700">P4</span> Low</span>
                            </span>
                            <span class="text-right">
                                <span class="block text-gray-800 font-medium">Scheduled</span>
                                <span class="block text-xs text-gray-400">General inquiry, request, or enhancement</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <p class="text-sm font-semibold text-gray-800 mb-3">Tips for a faster fix</p>
                    <ul class="space-y-2.5 text-sm text-gray-600">
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-700 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            Include exact error messages, if any.
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-700 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            Mention when the issue started.
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-700 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            Note if it affects only you or your whole team.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // character counter
        const description = document.getElementById('description');
        const charCount = document.getElementById('char-count');
        function updateCount() { charCount.textContent = description.value.length; }
        description.addEventListener('input', updateCount);
        updateCount();
    </script>
</x-app-layout>