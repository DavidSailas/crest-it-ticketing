<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Submit a New Ticket</h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8">

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
            <div class="lg:col-span-2 bg-white shadow-sm rounded-xl border border-gray-100">
                <form method="POST" action="{{ route('tickets.store') }}" class="p-6 sm:p-8 space-y-8" enctype="multipart/form-data">
                    @csrf

                    {{-- Section: Request --}}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">Request</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Department</label>
                                <div class="relative">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" /></svg>
                                    <select name="department" class="block w-full rounded-lg border-gray-300 pl-10 focus:border-green-700 focus:ring-green-700 text-sm bg-white" required>
                                        <option value="">Select department</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept }}" @selected(old('department') === $dept)>{{ $dept }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('department') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Office Location</label>
                                <div class="relative">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                    <select name="location" class="block w-full rounded-lg border-gray-300 pl-10 focus:border-green-700 focus:ring-green-700 text-sm bg-white" required>
                                        @foreach(['Cebu Office','Davao Office','Cagayan de Oro Office','Manila Office'] as $branch)
                                            <option value="{{ $branch }}" @selected(old('location', 'Cebu Office') === $branch)>{{ $branch }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('location') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
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

                    {{-- Section: Description --}}
                    <div class="pt-6 border-t border-gray-100" x-data="{ onBehalf: {{ old('on_behalf_of_user_id') || old('on_behalf_of_name') ? 'true' : 'false' }} }">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">Who is this for?</p>

                        <label class="flex items-start gap-2.5 rounded-lg border border-gray-200 px-3.5 py-3 cursor-pointer hover:bg-gray-50/70 transition">
                            <input type="checkbox" x-model="onBehalf" class="rounded border-gray-300 text-green-700 focus:ring-green-700 mt-0.5">
                            <span>
                                <span class="block text-sm font-medium text-gray-700">I'm reporting this for a colleague</span>
                                <span class="block text-xs text-gray-400 mt-0.5">Use this when their computer won't start or they can't log in, so they can't raise the ticket themselves.</span>
                            </span>
                        </label>

                        <div x-show="onBehalf" x-cloak class="mt-3 grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Colleague's account</label>
                                <select name="on_behalf_of_user_id" class="block w-full rounded-lg border-gray-300 text-sm bg-white focus:border-green-700 focus:ring-green-700">
                                    <option value="">Select a colleague…</option>
                                    @foreach($colleagues as $colleague)
                                        <option value="{{ $colleague->id }}" @selected(old('on_behalf_of_user_id') == $colleague->id)>{{ $colleague->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Or type their name <span class="text-gray-400 font-normal">(if not listed)</span></label>
                                <input type="text" name="on_behalf_of_name" value="{{ old('on_behalf_of_name') }}" placeholder="e.g. Maria Santos"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            </div>
                            <p class="sm:col-span-2 text-xs text-gray-400">Their name and desk location help IT go straight to the right machine — mention the location in the description below too.</p>
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
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm transition"
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