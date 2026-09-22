<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Ticket</h2>
    </x-slot>

    <style>[x-cloak] { display: none !important; }</style>

    <div class="py-8 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">

            <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background-color:#123f24;">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Editing request</p>
                    <p class="text-xs text-gray-500">You can make changes until IT support accepts this ticket.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="p-6 space-y-5" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" value="{{ old('title', $ticket->title) }}" placeholder="e.g. Cannot access shared drive"
                        class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>
                    @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <x-image-upload name="image" :existing-url="$ticket->imageUrl()" remove-field-name="remove_image" />

                <div class="grid grid-cols-2 gap-4" x-data="{
                        category: '{{ old('category', $ticket->category) }}',
                        options: {
                            hardware: [
                                ['desktop', 'Desktop Computer'],
                                ['laptop', 'Laptop'],
                                ['printer', 'Printer / Scanner'],
                                ['monitor', 'Monitor / Display'],
                                ['peripherals', 'Keyboard / Mouse / Peripherals'],
                                ['network_equipment', 'Network Equipment (Router, Switch)'],
                                ['other_hardware', 'Other Hardware'],
                            ],
                            software: [
                                ['email', 'Email'],
                                ['operating_system', 'Operating System'],
                                ['business_application', 'Business Application'],
                                ['account_login', 'Account & Login / Password'],
                                ['installation_update', 'Installation / Update'],
                                ['other_software', 'Other Software'],
                            ],
                        }
                    }">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select name="department" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>
                            <option value="">Select department</option>
                            @foreach(['CSR','Accounting','Sales','IT','HR','Operation','Tracking'] as $dept)
                                <option value="{{ $dept }}" @selected(old('department', $ticket->department) === $dept)>{{ $dept }}</option>
                            @endforeach
                        </select>
                        @error('department') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category" x-model="category" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>
                            <option value="">Select category</option>
                            <option value="hardware">Hardware</option>
                            <option value="software">Software</option>
                        </select>
                        @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-2" x-show="category" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sub-category</label>
                        <select name="subcategory" class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" :required="category">
                            <option value="">Select sub-category</option>
                            <template x-for="opt in options[category] || []" :key="opt[0]">
                                <option :value="opt[0]" x-text="opt[1]" :selected="opt[0] === '{{ old('subcategory', $ticket->subcategory) }}'"></option>
                            </template>
                        </select>
                        @error('subcategory') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach([
                            'p1' => ['label' => 'P1 (Critical)', 'ring' => 'peer-checked:bg-red-600 peer-checked:border-red-600', 'dot' => 'bg-red-500'],
                            'p2' => ['label' => 'P2 (High)', 'ring' => 'peer-checked:bg-orange-500 peer-checked:border-orange-500', 'dot' => 'bg-orange-500'],
                            'p3' => ['label' => 'P3 (Medium)', 'ring' => 'peer-checked:bg-blue-600 peer-checked:border-blue-600', 'dot' => 'bg-blue-500'],
                            'p4' => ['label' => 'P4 (Low)', 'ring' => 'peer-checked:bg-gray-500 peer-checked:border-gray-500', 'dot' => 'bg-gray-400'],
                        ] as $value => $opt)
                            <label class="cursor-pointer">
                                <input type="radio" name="priority" value="{{ $value }}" class="peer sr-only" {{ old('priority', $ticket->priority) === $value ? 'checked' : '' }} required>
                                <div class="flex items-center justify-center gap-2 text-sm font-medium py-2.5 rounded-lg border border-gray-300 text-gray-700 transition peer-checked:text-white {{ $opt['ring'] }}">
                                    <span class="w-2 h-2 rounded-full {{ $opt['dot'] }}"></span>
                                    {{ $opt['label'] }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('priority') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="5" placeholder="Describe the issue — what happened, when it started, any error messages you saw..."
                        class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>{{ old('description', $ticket->description) }}</textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('tickets.show', $ticket) }}" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm"
                        style="background-color:#1a6b3c;" onmouseover="this.style.backgroundColor='#145530'" onmouseout="this.style.backgroundColor='#1a6b3c'">
                        Save changes
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-red-100 p-5 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-gray-800">Cancel this ticket</p>
                <p class="text-xs text-gray-500 mt-0.5">This withdraws your request. You can't undo this — submit a new ticket if you need help again.</p>
            </div>
            <x-confirm-action-modal
                id="cancel-ticket-{{ $ticket->id }}"
                action="{{ route('tickets.cancel', $ticket) }}"
                method="POST"
                title="Cancel this ticket?"
                message="This withdraws your request. You can't undo this — submit a new ticket if you need help again."
                confirm-label="Cancel Ticket"
                trigger-label="Cancel ticket"
                trigger-class="shrink-0 px-4 py-2 rounded-lg text-sm font-medium text-red-600 border border-red-200 hover:bg-red-50 transition"
            />
        </div>
    </div>
</x-app-layout>
