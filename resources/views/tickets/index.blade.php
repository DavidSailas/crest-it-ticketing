<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                @if(auth()->user()->isStaff()) My Tickets
                @elseif(auth()->user()->isItSupport()) Support Queue
                @else All Tickets @endif
            </h2>
            @if(auth()->user()->isStaff())
                <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm font-semibold shadow-sm" style="background-color:#1a6b3c;">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New Ticket
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto sm:px-6 lg:px-8">
        @if(session('status'))
            <div class="mb-4 p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif

        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                        <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Title</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Department</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Category / Type</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Priority</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Status</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Requested by</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Assigned to</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition">
                            <td class="px-5 py-3.5 font-medium text-gray-800 border-r border-gray-100">{{ $ticket->title }}</td>
                            <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">{{ $ticket->department ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">
                                {{ $ticket->category }}
                                @if($ticket->subcategory)
                                    <span class="text-gray-400">· {{ $ticket->subcategory }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 border-r border-gray-100"><x-priority-badge :priority="$ticket->priority" /></td>
                            <td class="px-5 py-3.5 border-r border-gray-100"><x-status-badge :status="$ticket->status" /></td>
                            <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">
                                <span class="inline-flex items-center gap-1.5">
                                    {{ $ticket->creator->name }}
                                    @if($ticket->creator->is_vip)
                                        <x-vip-badge size="compact" />
                                    @endif
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">{{ $ticket->assignee->name ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('tickets.show', $ticket) }}" class="text-green-700 hover:underline font-medium">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-14 text-center text-gray-400">No tickets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $tickets->links() }}</div>
    </div>
</x-app-layout>
