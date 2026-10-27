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

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-6">
            <p class="text-sm font-semibold text-gray-700 mb-3">Add a position</p>
            <form method="POST" action="{{ route('admin.positions.store') }}" class="flex flex-col gap-3 sm:flex-row">
                @csrf
                <input type="text" name="name" placeholder="e.g. Accountant" value="{{ old('name') }}" maxlength="255"
                    class="w-full min-w-0 flex-1 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                <button class="btn btn-primary w-full sm:w-auto">Add</button>
            </form>
            @error('name') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
            <p class="text-xs text-gray-400 mt-2">Job titles that staff, IT and admin accounts can be assigned (e.g. <span class="font-mono">Accountant</span>, <span class="font-mono">IT Technician</span>). Used on the Manage Users page.</p>
        </div>

        {{-- Phones: one card per position --}}
        <ul class="space-y-3 sm:hidden">
            @forelse($positions as $position)
                <li x-data="{ editing: false }" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div x-show="!editing" class="flex items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="break-words font-semibold text-gray-900">{{ $position->name }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500"> <span>{{ $position->users_count }} users</span></p>
                        </div>
                        <button type="button" @click="editing = true" class="btn btn-soft btn-sm">Edit</button>
                        <x-confirm-action-modal
                            id="delete-position-m-{{ $position->id }}"
                            action="{{ route('admin.positions.destroy', $position) }}"
                            title="Delete this position?"
                            message="Delete {{ $position->name }}? It can no longer be assigned, but anything already using it keeps its history. This can't be undone."
                            confirm-label="Delete Position"
                            trigger-label="Delete"
                            trigger-class="btn btn-danger btn-sm"
                        />
                    </div>
                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.positions.update', $position) }}" class="space-y-3">
                        @csrf @method('PUT')
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Name</label>
                            <input type="text" name="name" value="{{ $position->name }}" maxlength="255" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="editing = false" class="btn btn-secondary btn-sm flex-1">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm flex-1">Save</button>
                        </div>
                    </form>
                </li>
            @empty
                <li class="rounded-xl border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-400 shadow-sm">No positions yet — add your first one above.</li>
            @endforelse
        </ul>

        {{-- Tablets and up: the table --}}
        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm sm:block">
            <table class="w-full text-sm table-fixed">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-auto">Position</th>
                        <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-24">Users</th>
                        <th class="px-4 py-3 w-44"></th>
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
                                <button type="button" @click="editing = !editing" class="btn btn-soft btn-sm mr-2">
                                    <span x-text="editing ? 'Cancel' : 'Edit'"></span>
                                </button>
                                <x-confirm-action-modal
                                    id="delete-position-{{ $position->id }}"
                                    action="{{ route('admin.positions.destroy', $position) }}"
                                    title="Delete this position?"
                                    message="Delete {{ $position->name }}? Users currently in this position will keep their history, but it can no longer be assigned. This can't be undone."
                                    confirm-label="Delete Position"
                                    trigger-label="Delete"
                                    trigger-class="btn btn-danger btn-sm"
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
