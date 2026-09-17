<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('users.directory') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $user->name }}</h2>
        </div>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- Account card --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-6 flex items-start gap-4">
                <div class="w-14 h-14 rounded-full flex items-center justify-center text-white text-lg font-bold shrink-0" style="background-color:#1a6b3c;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-lg font-semibold text-gray-800">{{ $user->name }}</p>
                        @if($user->is_vip)
                            <x-vip-badge size="compact" />
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $user->email }}</p>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-5 border-t border-gray-100 text-sm">
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Role</p>
                            <p class="text-gray-800 font-medium capitalize">{{ str_replace('_', ' ', $user->role) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Branch</p>
                            <p class="text-gray-800 font-medium">{{ $user->branch_name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Account Created</p>
                            <p class="text-gray-800 font-medium">{{ $user->created_at?->format('M j, Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Assets Issued</p>
                            <p class="text-gray-800 font-medium">{{ $assets->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ticket stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Total Tickets', 'value' => $ticketCounts['total'], 'fg' => 'text-gray-800'],
                ['label' => 'Open', 'value' => $ticketCounts['open'], 'fg' => 'text-amber-600'],
                ['label' => 'In Progress', 'value' => $ticketCounts['in_progress'], 'fg' => 'text-blue-600'],
                ['label' => 'Resolved', 'value' => $ticketCounts['resolved'], 'fg' => 'text-green-700'],
            ] as $stat)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <p class="text-2xl font-semibold {{ $stat['fg'] }} leading-none">{{ $stat['value'] }}</p>
                    <p class="text-xs text-gray-500 mt-1.5">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Assets --}}
        <div>
            <h3 class="text-base font-semibold text-gray-800 mb-3">Assigned Assets</h3>
            <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
                @if($assets->isEmpty())
                    <div class="flex flex-col items-center justify-center text-center px-6 py-12">
                        <svg class="w-9 h-9 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg>
                        <p class="text-sm text-gray-400">No assets issued to this user.</p>
                    </div>
                @else
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Tag</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Type</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Device</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Serial</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assets as $asset)
                                <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition">
                                    <td class="px-4 py-3.5 border-r border-gray-100">
                                        <span class="inline-flex font-mono text-xs font-semibold text-gray-700 bg-gray-100 rounded px-1.5 py-1">{{ $asset->asset_tag }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-gray-600 border-r border-gray-100">{{ \App\Models\Asset::TYPES[$asset->type] ?? $asset->type }}</td>
                                    <td class="px-4 py-3.5 text-gray-800 font-medium border-r border-gray-100">{{ $asset->device_name }}</td>
                                    <td class="px-4 py-3.5 text-gray-500 border-r border-gray-100">{{ $asset->serial_number ?? '—' }}</td>
                                    <td class="px-4 py-3.5"><x-asset-status-badge :status="$asset->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Recent tickets --}}
        <div>
            <h3 class="text-base font-semibold text-gray-800 mb-3">Recent Tickets</h3>
            <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
                @if($tickets->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm text-gray-400">This user hasn't submitted any tickets yet.</p>
                    </div>
                @else
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Ticket</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Priority</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Submitted</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tickets as $ticket)
                                <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition">
                                    <td class="px-4 py-3.5 border-r border-gray-100">
                                        <span class="font-mono text-xs text-gray-400 mr-1">{{ $ticket->ticket_number }}</span>
                                        <span class="font-medium text-gray-800">{{ $ticket->title }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 border-r border-gray-100"><x-priority-badge :priority="$ticket->priority" /></td>
                                    <td class="px-4 py-3.5 border-r border-gray-100"><x-status-badge :status="$ticket->status" /></td>
                                    <td class="px-4 py-3.5 text-gray-500 border-r border-gray-100">{{ $ticket->created_at->diffForHumans() }}</td>
                                    <td class="px-4 py-3.5 text-right">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="text-green-700 hover:underline font-medium text-xs">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
