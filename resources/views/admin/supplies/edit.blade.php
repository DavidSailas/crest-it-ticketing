<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Supply Item</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="flex items-center gap-2.5 px-6 sm:px-8 py-5 border-b border-gray-100">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-700 shrink-0">
                    <x-supply-icon :category="$supply->category" class="w-4 h-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $supply->name }}</p>
                    <p class="text-xs text-gray-400"><span class="font-mono">{{ $supply->code }}</span> · Update the item's details</p>
                </div>
            </div>
            <div class="p-6 sm:p-8">
                @include('admin.supplies._form', [
                    'supply' => $supply,
                    'branches' => $branches,
                    'action' => route('supplies.update', $supply),
                    'method' => 'PUT',
                    'mode' => 'edit',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
