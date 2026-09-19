<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ticket Submitted</h2>
    </x-slot>

    <div class="py-10 max-w-xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex flex-col items-center text-center mb-6">
            <div class="w-16 h-16 rounded-full bg-green-50 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-800">Your ticket has been submitted</h1>
            <p class="text-sm text-gray-500 mt-1.5 max-w-sm">
                IT has been notified. You can track progress anytime from "My Tickets."
            </p>
        </div>

        {{-- Receipt card --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

            <div class="px-6 py-5 flex items-center justify-between" style="background-color:#123f24;">
                <div>
                    <p class="text-green-100/70 text-xs uppercase tracking-wider">Ticket Reference</p>
                    <p class="text-white text-2xl font-bold font-mono tracking-wide">{{ $ticket->ticket_number }}</p>
                </div>
                <x-status-badge :status="$ticket->status" />
            </div>

            <div class="px-6 py-6">
                <div class="grid grid-cols-2 gap-y-5 gap-x-4 text-sm">
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Priority</p>
                        <x-priority-badge :priority="$ticket->priority" />
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Department</p>
                        <p class="text-gray-800 font-medium">{{ $ticket->department }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Location</p>
                        <p class="text-gray-800 font-medium">{{ $ticket->location }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Category</p>
                        <p class="text-gray-800 font-medium">
                            {{ $ticket->category }}
                            @if($ticket->subcategory)
                                <span class="text-gray-400">· {{ $ticket->subcategory }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Description</p>
                        <p class="text-gray-800 whitespace-pre-line">{{ $ticket->description }}</p>
                    </div>
                    @if($ticket->attachment_path)
                        <div class="col-span-2">
                            <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Attachment</p>
                            <p class="text-gray-800 font-medium flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.5c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124V6.75A2.25 2.25 0 0110.5 4.5h.375a1.125 1.125 0 011.125 1.125v.375m0 0a2.25 2.25 0 002.25 2.25h.375a1.125 1.125 0 011.125 1.125v2.25a2.25 2.25 0 01-2.25 2.25h-6a2.25 2.25 0 01-2.25-2.25v-.75" /></svg>
                                {{ $ticket->attachment_name }}
                            </p>
                        </div>
                    @endif
                </div>

                <div class="mt-6 pt-5 border-t border-dashed border-gray-200 flex justify-between text-xs text-gray-400">
                    <span>Submitted by {{ $ticket->creator->name }}</span>
                    <span>{{ $ticket->created_at->format('M j, Y — g:i A') }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mt-6">
            <a href="{{ route('tickets.show', $ticket) }}"
               class="flex-1 text-center px-4 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm transition"
               style="background-color:#1a6b3c;" onmouseover="this.style.backgroundColor='#145530'" onmouseout="this.style.backgroundColor='#1a6b3c'">
                View Ticket Details
            </a>
            <a href="{{ route('tickets.index') }}"
               class="flex-1 text-center px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">
                Back to My Tickets
            </a>
        </div>

        <p class="text-center text-xs text-gray-400 mt-5">
            Keep your reference number <span class="font-mono font-medium text-gray-500">{{ $ticket->ticket_number }}</span> handy if you need to follow up.
        </p>
    </div>
</x-app-layout>
