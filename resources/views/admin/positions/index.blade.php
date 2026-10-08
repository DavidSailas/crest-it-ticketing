<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Positions</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-800 text-sm rounded-lg border border-red-100">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <p class="text-sm font-semibold text-gray-700 mb-3">Add a position</p>
            <form method="POST" action="{{ route('admin.positions.store') }}" class="flex gap-3">
                @csrf
                <input type="text" name="name" placeholder="e.g. Accountant" value="{{ old('name') }}" maxlength="255"
                    class="flex-1 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                <button class="px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm" style="background-color:#1a6b3c;">Add</button>
            </form>
            @error('name') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
            <p class="text-xs text-gray-400 mt-2">Job titles that staff, IT and admin accounts can be assigned (e.g. <span class="font-mono">Accountant</span>, <span class="font-mono">IT Technician</span>). Used on the Manage Users page.</p>
        </div>

        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
            <table class="w-full text-sm table-fixed">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-auto">Position</th>
                        <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-24">Users</th>
                        <th class="px-4 py-3 w-28"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($positions as $position)
                        <tr class="hover:bg-gray-50/60 transition-colors" x-data="{ editing: false }">
                            <td class="px-4 py-3">
                                <div x-show="!editing" class="font-medium text-gray-800 truncate">{{ $position->name }}</div>
                                <form x-show="editing" x-cloak method="POST" action="{{ route('admin.positions.update', $position) }}" class="flex gap-2 items-center" id="position-form-{{ $position->id }}">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $position->name }}" maxlength="255"
                                        class="w-full rounded-md border-gray-300 text-sm py-1 focus:border-green-700 focus:ring-green-700" required>
                                    <button type="submit" class="text-xs text-green-700 font-medium">Save</button>
                                </form>
                                {{-- Folds in on mobile where the Users column is hidden --}}
                                <div class="sm:hidden mt-1 text-xs text-gray-500">{{ $position->users_count }} {{ \Illuminate\Support\Str::plural('user', $position->users_count) }}</div>
                            </td>
                            <td class="hidden sm:table-cell px-4 py-3 text-gray-500">{{ $position->users_count }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button" @click="editing = !editing" class="text-xs text-gray-500 hover:text-gray-700 mr-3">
                                    <span x-text="editing ? 'Cancel' : 'Edit'"></span>
                                </button>
                                <x-confirm-action-modal
                                    id="delete-position-{{ $position->id }}"
                                    action="{{ route('admin.positions.destroy', $position) }}"
                                    title="Delete this position?"
                                    message="Delete {{ $position->name }}? Users currently in this position will keep their history, but it can no longer be assigned. This can't be undone."
                                    confirm-label="Delete Position"
                                    trigger-label="Delete"
                                    trigger-class="text-xs text-red-500 hover:text-red-700"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-400 text-sm">No positions yet — add your first one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
