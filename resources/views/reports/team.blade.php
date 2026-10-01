<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reports</h2>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-[80rem] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @include('reports.partials.period')

        @include('reports.partials.table-card', [
            't' => $engineerReport, 'team' => 'engineers', 'limit' => 10,
            'count_label' => 'IT engineers', 'empty_text' => 'No IT engineer accounts yet.',
        ])
        @include('reports.partials.table-card', [
            't' => $staffReport, 'team' => 'staff', 'limit' => 10,
            'count_label' => 'staff', 'empty_text' => 'No staff accounts yet.',
        ])

        <p class="text-center text-xs text-gray-400 pt-2">Crest Forwarder Inc. — IT Service Desk · Internal use only</p>
    </div>
</x-app-layout>
