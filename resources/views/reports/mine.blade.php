<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Reports</h2>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-[80rem] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @include('reports.partials.period')

        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Submitted</p>
                <p class="mt-1.5 text-3xl font-semibold text-gray-800 tabular-nums">{{ $totalMine }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ $rangeLabel }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Still open</p>
                <p class="mt-1.5 text-3xl font-semibold tabular-nums" style="color:#b45309;">{{ $openMine }}</p>
                <p class="mt-0.5 text-xs text-gray-400">Open, in progress or pending</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Resolved</p>
                <p class="mt-1.5 text-3xl font-semibold tabular-nums" style="color:#1a6b3c;">{{ $doneMine }}</p>
                <p class="mt-0.5 text-xs text-gray-400">Resolved or closed</p>
            </div>
        </div>

        @include('reports.partials.table-card', [
            't' => $table, 'team' => 'mine', 'limit' => 15,
            'count_label' => 'tickets', 'empty_text' => 'You have not submitted any tickets in this period.',
        ])

        <p class="text-center text-xs text-gray-400 pt-2">Crest Forwarder Inc. — IT Service Desk · Internal use only</p>
    </div>
</x-app-layout>
