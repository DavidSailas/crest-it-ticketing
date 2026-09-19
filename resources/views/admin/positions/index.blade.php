@php
    $isAdmin = auth()->user()->isAdmin();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Positions</h2>
    </x-slot>

    <style>[x-cloak] { display: none !important; }</style>

    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
         x-data="{ showCreate: {{ $errors->any() ? 'true' : 'false' }}, editingId: null }">

        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-100">{{ session('error') }}</div>
        @endif

        <div class="flex justify-between items-center">
            <p class="text-sm text-gray-500">Job titles staff/IT/admin accounts can be assigned (e.g. Accountant, IT Technician). Used on the Manage Users page.</p>
            <button @click="showCreate = true"
                    class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm transition"
                    style="background-color:#1a6b3c;" onmouseover="this.style.backgroundColor='#145530'" onmouseout="this.style.backgroundColor='#1a6b3c'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New Position
            </button>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            @if($positions->isEmpty())
                <div class="flex flex-col items-center justify-center text-center px-6 py-14">
                    <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                    </div>
                    <p class="text-gray-700 font-medium">No positions yet</p>
                    <p class="text-gray-400 text-sm mt-1">Add your first one to start assigning it to users.</p>
                </div>
            @else
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                            <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Position</th>
                            <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Users</th>
                            <th class="px-5 py-3 text-right font-semibold text-gray-600"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($positions as $position)
                            <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition">
                                <td class="px-5 py-3.5 font-medium text-gray-800 border-r border-gray-100">{{ $position->name }}</td>
                                <td class="px-5 py-3.5 text-gray-500 border-r border-gray-100">{{ $position->users_count }}</td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button @click="editingId = {{ $position->id }}" class="text-green-700 hover:underline font-medium text-xs mr-3">Edit</button>
                                    <form method="POST" action="{{ route('admin.positions.destroy', $position) }}" class="inline"
                                          onsubmit="return confirm('Remove position \'{{ $position->name }}\'?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline font-medium text-xs">Delete</button>
                                    </form>
                                </td>
                            </tr>

                            {{-- Edit modal for this row --}}
                            <tr x-show="editingId === {{ $position->id }}" x-cloak>
                                <td colspan="3" class="px-0 py-0">
                                    <div class="fixed inset-0 bg-black/30 z-40 flex items-center justify-center p-4" @click.self="editingId = null">
                                        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6">
                                            <div class="flex justify-between items-center mb-4">
                                                <h3 class="text-base font-semibold text-gray-800">Edit Position</h3>
                                                <button @click="editingId = null" class="text-gray-400 hover:text-gray-600">&times;</button>
                                            </div>
                                            <form method="POST" action="{{ route('admin.positions.update', $position) }}" class="space-y-4">
                                                @csrf
                                                @method('PUT')
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                                    <input type="text" name="name" value="{{ old('name', $position->name) }}"
                                                           class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required>
                                                </div>
                                                <div class="flex justify-end gap-3 pt-2">
                                                    <button type="button" @click="editingId = null" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</button>
                                                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background-color:#1a6b3c;">Save changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Create modal --}}
        <div x-show="showCreate" x-cloak class="fixed inset-0 bg-black/30 z-40 flex items-center justify-center p-4" @click.self="showCreate = false">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-semibold text-gray-800">New Position</h3>
                    <button @click="showCreate = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.positions.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" placeholder="e.g. Accountant" value="{{ old('name') }}"
                               class="block w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700" required autofocus>
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showCreate = false" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background-color:#1a6b3c;">Add Position</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
