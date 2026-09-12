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
                <form method="POST" action="{{ route('tickets.store') }}" class="p-6 sm:p-8 space-y-8">
                    @csrf

                    {{-- Section: Request --}}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">Request</p>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Title</label>
                                <div class="relative">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Cannot access shared drive"
                                        class="block w-full rounded-lg border-gray-300 pl-10 focus:border-green-700 focus:ring-green-700 text-sm" required>
                                </div>
                                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Department</label>
                                <div class="relative">
                                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" /></svg>
                                    <select name="department" class="block w-full rounded-lg border-gray-300 pl-10 focus:border-green-700 focus:ring-green-700 text-sm appearance-none bg-white" required>
                                        <option value="">Select department</option>
                                        @foreach(['Accounting','Sales','Operations','Human Resources','Marketing','Warehouse/Logistics','Customer Service','Executive/Management','IT','Other'] as $dept)
                                            <option value="{{ $dept }}" @selected(old('department') === $dept)>{{ $dept }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="w-4 h-4 text-gray-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </div>
                                @error('department') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section: Classification --}}
                    <div class="pt-6 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">Classification</p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
                                <div class="relative">
                                    <select id="category" name="category" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm appearance-none bg-white pr-9" required>
                                        <option value="">Select</option>
                                        <option value="Hardware" @selected(old('category') === 'Hardware')>Hardware</option>
                                        <option value="Software" @selected(old('category') === 'Software')>Software</option>
                                    </select>
                                    <svg class="w-4 h-4 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </div>
                                @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                                <div class="relative">
                                    <select id="subcategory" name="subcategory" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm appearance-none bg-white pr-9 disabled:bg-gray-50 disabled:text-gray-400" required disabled>
                                        <option value="">Select category first</option>
                                    </select>
                                    <svg class="w-4 h-4 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </div>
                                @error('subcategory') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Priority</label>
                                @if(auth()->user()->is_vip)
                                    <div class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2.5">
                                        <span class="text-amber-600">★</span>
                                        <span class="text-sm font-medium text-amber-800">Critical (VIP account)</span>
                                    </div>
                                    <input type="hidden" name="priority" value="critical">
                                    <p class="text-xs text-gray-400 mt-1">Your account is marked VIP, so this ticket is automatically set to Critical.</p>
                                @else
                                    <div class="relative">
                                        <select name="priority" id="priority" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 text-sm appearance-none bg-white pr-9" required>
                                            <option value="critical" @selected(old('priority') === 'critical')>Critical</option>
                                            <option value="high" @selected(old('priority') === 'high')>High</option>
                                            <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium</option>
                                            <option value="low" @selected(old('priority') === 'low')>Low</option>
                                        </select>
                                        <svg class="w-4 h-4 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                    </div>
                                @endif
                                @error('priority') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
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
                    <p class="text-sm font-semibold text-gray-800 mb-3">Expected response time</p>
                    <div class="space-y-2.5 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 text-gray-600"><span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>Critical</span>
                            <span class="text-gray-800 font-medium">Within 1 hour</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 text-gray-600"><span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>High</span>
                            <span class="text-gray-800 font-medium">Within 4 hours</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 text-gray-600"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Medium</span>
                            <span class="text-gray-800 font-medium">Within 1 business day</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 text-gray-600"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Low</span>
                            <span class="text-gray-800 font-medium">Within 3 business days</span>
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
        const subcategoryOptions = {
            Hardware: ['Laptop', 'Desktop', 'Printer', 'Monitor', 'Keyboard / Mouse', 'Network Equipment (Router/Switch)', 'Other Hardware'],
            Software: ['ERP System', 'Email / Outlook', 'Network / VPN', 'Operating System', 'Business Application', 'Account / Access', 'Other Software'],
        };

        const categorySelect = document.getElementById('category');
        const subcategorySelect = document.getElementById('subcategory');
        const oldSubcategory = @json(old('subcategory'));

        function populateSubcategories() {
            const options = subcategoryOptions[categorySelect.value] || [];

            if (options.length === 0) {
                subcategorySelect.innerHTML = '<option value="">Select category first</option>';
                subcategorySelect.disabled = true;
                return;
            }

            subcategorySelect.disabled = false;
            subcategorySelect.innerHTML = '<option value="">Select type</option>' +
                options.map(opt => `<option value="${opt}" ${opt === oldSubcategory ? 'selected' : ''}>${opt}</option>`).join('');
        }

        categorySelect.addEventListener('change', populateSubcategories);
        if (categorySelect.value) populateSubcategories();

        // character counter
        const description = document.getElementById('description');
        const charCount = document.getElementById('char-count');
        function updateCount() { charCount.textContent = description.value.length; }
        description.addEventListener('input', updateCount);
        updateCount();
    </script>
</x-app-layout>
