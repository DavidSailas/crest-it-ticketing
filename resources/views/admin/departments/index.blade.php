<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Departments</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-800 text-sm rounded-lg border border-red-100">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <p class="text-sm font-semibold text-gray-700 mb-3">Add a department</p>
            <form method="POST" action="{{ route('admin.departments.store') }}" class="flex gap-3">
                @csrf
                <input type="text" name="name" placeholder="e.g. Warehouse" value="{{ old('name') }}"
                    class="flex-1 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                <input type="text" name="code" placeholder="Code, e.g. WHS" maxlength="10" value="{{ old('code') }}"
                    class="w-32 rounded-lg border-gray-300 text-sm uppercase focus:border-green-700 focus:ring-green-700" required>
                <button class="px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm" style="background-color:#1a6b3c;">Add</button>
            </form>
            @error('name') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
            @error('code') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
            <p class="text-xs text-gray-400 mt-2">The code is used to build asset tags, e.g. <span class="font-mono">ACC</span> → <span class="font-mono">CFI-ACC-DT-001</span>.</p>
        </div>

        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
            <table class="w-full text-sm table-fixed">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-auto">Department</th>
                        <th class="hidden sm:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-28">Code</th>
                        <th class="hidden md:table-cell px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-20">Tickets</th>
                        <th class="px-4 py-3 w-28"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($departments as $dept)
                        <tr class="hover:bg-gray-50/60 transition-colors" x-data="{ editing: false }">
                            <td class="px-4 py-3">
                                <div x-show="!editing" x-text="'{{ $dept->name }}'" class="font-medium text-gray-800 truncate"></div>
                                <form x-show="editing" method="POST" action="{{ route('admin.departments.update', $dept) }}" class="flex gap-2" id="dept-form-{{ $dept->id }}">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $dept->name }}" class="w-full rounded-md border-gray-300 text-sm py-1 focus:border-green-700 focus:ring-green-700" required>
                                </form>
                                <div class="sm:hidden mt-1 flex items-center gap-2 text-xs text-gray-500">
                                    <span class="font-mono px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">{{ $dept->code ?? '—' }}</span>
                                    <span>·</span>
                                    <span>{{ $dept->tickets_count }} tickets</span>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell px-4 py-3">
                                <span x-show="!editing" class="font-mono text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">{{ $dept->code ?? '—' }}</span>
                                <input x-show="editing" type="text" name="code" form="dept-form-{{ $dept->id }}" value="{{ $dept->code }}" maxlength="10"
                                    class="w-24 rounded-md border-gray-300 text-sm py-1 uppercase focus:border-green-700 focus:ring-green-700" required>
                                <button x-show="editing" type="submit" form="dept-form-{{ $dept->id }}" class="text-xs text-green-700 font-medium ml-2">Save</button>
                            </td>
                            <td class="hidden md:table-cell px-4 py-3 text-gray-500">{{ $dept->tickets_count }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button" @click="editing = !editing" class="text-xs text-gray-500 hover:text-gray-700 mr-3">
                                    <span x-text="editing ? 'Cancel' : 'Edit'"></span>
                                </button>
                                <form method="POST" action="{{ route('admin.departments.destroy', $dept) }}" class="inline"
                                      onsubmit="return confirm('Delete {{ $dept->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-500 hover:text-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
