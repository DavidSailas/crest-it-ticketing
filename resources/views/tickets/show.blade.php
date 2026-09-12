<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $ticket->title }}</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <x-status-badge :status="$ticket->status" />
                    <x-priority-badge :priority="$ticket->priority" />
                </div>
                <div class="text-sm text-gray-400">{{ $ticket->created_at->diffForHumans() }}</div>
            </div>

            <div class="px-6 py-6">
                <p class="text-gray-800 leading-relaxed">{{ $ticket->description }}</p>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-100 text-sm">
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Requested by</p>
                        <p class="text-gray-800 font-medium flex items-center gap-1.5">
                            {{ $ticket->creator->name }}
                            @if($ticket->creator->is_vip)
                                <x-vip-badge size="compact" />
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Department</p>
                        <p class="text-gray-800 font-medium">{{ $ticket->department ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Category</p>
                        <p class="text-gray-800 font-medium">{{ $ticket->category }} @if($ticket->subcategory)<span class="text-gray-400">· {{ $ticket->subcategory }}</span>@endif</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Assigned to</p>
                        <p class="text-gray-800 font-medium">{{ $ticket->assignee->name ?? 'Unassigned' }}</p>
                    </div>
                </div>
            </div>

            @if((auth()->user()->isItSupport() || auth()->user()->isAdmin()))
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap gap-3 items-center">
                    @if(!$ticket->assigned_to)
                        <form method="POST" action="{{ route('tickets.accept', $ticket) }}">
                            @csrf
                            <button class="px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm" style="background-color:#1a6b3c;">Accept Ticket</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('tickets.status', $ticket) }}" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            @foreach(['open','in_progress','resolved','closed'] as $status)
                                <option value="{{ $status }}" @selected($ticket->status === $status)>{{ str_replace('_',' ', ucfirst($status)) }}</option>
                            @endforeach
                        </select>
                        <button class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-medium">Update Status</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Activity & Comments</h3>

            <div class="space-y-4 mb-5">
                @forelse($ticket->comments as $comment)
                    <div class="text-sm pb-4 border-b border-gray-50 last:border-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-medium text-gray-800">{{ $comment->author->name }}</span>
                            <span class="text-gray-300 text-xs">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-gray-600">{{ $comment->body }}</p>
                    </div>
                @empty
                    <p class="text-gray-400 text-sm">No comments yet.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('tickets.comment', $ticket) }}">
                @csrf
                <textarea name="body" rows="2" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" placeholder="Add a comment..." required></textarea>
                <button class="mt-2 px-4 py-2 rounded-lg text-white text-sm font-medium" style="background-color:#1a6b3c;">Post Comment</button>
            </form>
        </div>
    </div>
</x-app-layout>
