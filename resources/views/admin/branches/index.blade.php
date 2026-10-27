<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Branches</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-800 text-sm rounded-lg border border-red-100">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:p-6">
            <p class="text-sm font-semibold text-gray-700 mb-3">Add a branch</p>
            <form method="POST" action="{{ route('admin.branches.store') }}" class="flex flex-col gap-3 sm:flex-row">
                @csrf
                <input type="text" name="name" placeholder="e.g. Iloilo" value="{{ old('name') }}"
                    class="w-full min-w-0 flex-1 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                <input type="text" name="code" placeholder="Code, e.g. ILO" maxlength="10" value="{{ old('code') }}"
                    class="w-full rounded-lg border-gray-300 text-sm uppercase focus:border-green-700 focus:ring-green-700 sm:w-32" required>
                <button class="btn btn-primary w-full sm:w-auto">Add</button>
            </form>
            @error('name') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
            @error('code') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
            <p class="text-xs text-gray-400 mt-2">The code is used to build asset tags and is stored on staff profiles, e.g. <span class="font-mono">CEB</span> → <span class="font-mono">CFI-CEB-IT-DT-001</span>.</p>
        </div>

        {{-- Phones: one card per branch --}}
        <ul class="space-y-3 sm:hidden">
            @forelse($branches as $branch)
                <li x-data="{ editing: false }" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div x-show="!editing" class="flex items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="break-words font-semibold text-gray-900">{{ $branch->name }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500"><span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-gray-600">{{ $branch->code ?? '—' }}</span> <span>{{ $branch->users_count }} staff · {{ $branch->assets_count }} assets</span></p>
                        </div>
                        <button type="button" @click="editing = true" class="btn btn-soft btn-sm">Edit</button>
                        <x-confirm-action-modal
                            id="delete-branch-m-{{ $branch->id }}"
                            action="{{ route('admin.branches.destroy', $branch) }}"
                            title="Delete this branch?"
                            message="Delete {{ $branch->name }}? It can no longer be assigned, but anything already using it keeps its history. This can't be undone."
                            confirm-label="Delete Branch"
                            trigger-label="Delete"
                            trigger-class="btn btn-danger btn-sm"
                        />
                    </div>
                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.branches.update', $branch) }}" class="space-y-3">
                        @csrf @method('PUT')
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Name</label>
                            <input type="text" name="name" value="{{ $branch->name }}" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                        </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Code</label>
                        <input type="text" name="code" value="{{ $branch->code }}" maxlength="10" required class="block w-full rounded-lg border-gray-300 text-sm uppercase focus:border-green-700 focus:ring-green-700">
                    </div>
                        <div class="flex gap-2">
                            <button type="button" @click="editing = false" class="btn btn-secondary btn-sm flex-1">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm flex-1">Save</button>
                        </div>
                    </form>
                </li>
            @empty
                <li class="rounded-xl border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-400 shadow-sm">No branches yet — add your first one above.</li>
            @endforelse
        </ul>

        {{-- Tablets and up: the table --}}
        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm sm:block">
            <table class="w-full text-sm table-fixed">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-auto">Branch</th>
                        <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-28">Code</th>
                        <th class="hidden md:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-20">Staff</th>
                        <th class="hidden md:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-20">Assets</th>
                        <th class="px-4 py-3 w-44"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($branches as $branch)
                        <tr class="hover:bg-gray-50/60 transition-colors" x-data="{ editing: false }">
                            <td class="px-4 py-3">
                                <div x-show="!editing" x-text="'{{ $branch->name }}'" class="font-medium text-gray-800 truncate"></div>
                                <form x-show="editing" method="POST" action="{{ route('admin.branches.update', $branch) }}" class="flex gap-2" id="branch-form-{{ $branch->id }}">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $branch->name }}" class="w-full rounded-md border-gray-300 text-sm py-1 focus:border-green-700 focus:ring-green-700" required>
                                </form>
                                {{-- Folds in on mobile where Code/Staff/Assets columns are hidden --}}
                                <div class="sm:hidden mt-1 flex items-center gap-2 text-xs text-gray-500">
                                    <span class="font-mono px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">{{ $branch->code }}</span>
                                    <span>{{ $branch->users_count }} staff</span>
                                    <span>·</span>
                                    <span>{{ $branch->assets_count }} assets</span>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell px-4 py-3">
                                <span x-show="!editing" class="font-mono text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">{{ $branch->code }}</span>
                                <input x-show="editing" type="text" name="code" form="branch-form-{{ $branch->id }}" value="{{ $branch->code }}" maxlength="10"
                                    class="w-24 rounded-md border-gray-300 text-sm py-1 uppercase focus:border-green-700 focus:ring-green-700" required>
                                <button x-show="editing" type="submit" form="branch-form-{{ $branch->id }}" class="text-xs text-green-700 font-medium ml-2">Save</button>
                            </td>
                            <td class="hidden md:table-cell px-4 py-3 text-gray-500">{{ $branch->users_count }}</td>
                            <td class="hidden md:table-cell px-4 py-3 text-gray-500">{{ $branch->assets_count }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button" @click="editing = !editing" class="btn btn-soft btn-sm mr-2">
                                    <span x-text="editing ? 'Cancel' : 'Edit'"></span>
                                </button>
                                <x-confirm-action-modal
                                    id="delete-branch-{{ $branch->id }}"
                                    action="{{ route('admin.branches.destroy', $branch) }}"
                                    title="Delete this branch?"
                                    message="Delete {{ $branch->name }}? Users and assets already using it will keep their history, but it can no longer be assigned. This can't be undone."
                                    confirm-label="Delete Branch"
                                    trigger-label="Delete"
                                    trigger-class="btn btn-danger btn-sm"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">No branches yet — add your first one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
