<div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
    <div class="px-4 sm:px-6 py-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-mono font-semibold text-gray-400 mr-1">{{ $ticket->ticket_number }}</span>
            <x-status-badge :status="$ticket->status" />
            @if($ticket->isApproved())
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 whitespace-nowrap" title="Approved by {{ $ticket->creator->name }} on {{ $ticket->approved_at->format('M j, Y g:i A') }}">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Approved
                </span>
            @endif
            <x-priority-badge :priority="$ticket->priority" />
        </div>
        <div class="text-sm text-gray-400">{{ $ticket->created_at->format('M j, Y g:i A') }}</div>
    </div>

    <div class="px-4 sm:px-6 py-6">
        <p class="text-gray-800 leading-relaxed break-words whitespace-pre-line">{{ $ticket->description }}</p>

        @if($ticket->attachment_path)
            <a href="{{ $ticket->attachment_url }}" target="_blank"
               class="mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 transition px-3.5 py-2.5 text-sm text-gray-700">
                <svg class="w-4 h-4 text-green-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.5c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124V6.75A2.25 2.25 0 0110.5 4.5h.375a1.125 1.125 0 011.125 1.125v.375m0 0a2.25 2.25 0 002.25 2.25h.375a1.125 1.125 0 011.125 1.125v2.25a2.25 2.25 0 01-2.25 2.25h-6a2.25 2.25 0 01-2.25-2.25v-.75" /></svg>
                <span class="truncate max-w-[240px] font-medium">{{ $ticket->attachment_name }}</span>
                <span class="text-xs text-gray-400">Download</span>
            </a>
        @endif

        <div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-5 mt-6 pt-6 border-t border-gray-100 text-sm">
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Requested by</p>
                <p class="text-gray-800 font-medium flex items-center gap-1.5">
                    {{ $ticket->creator->name }}
                    @if($ticket->creator->is_vip)
                        <x-vip-badge size="compact" />
                    @endif
                </p>
                @if($ticket->isOnBehalf())
                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded px-1.5 py-0.5 mt-1.5 inline-block">
                        On behalf of <strong>{{ $ticket->affectedPersonName() }}</strong>
                    </p>
                @endif
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Department</p>
                <p class="text-gray-800 font-medium">{{ $ticket->department ?? '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Location</p>
                <p class="text-gray-800 font-medium">{{ $ticket->location ?? '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Category</p>
                <p class="text-gray-800 font-medium">{{ $ticket->category }} @if($ticket->subcategory)<span class="text-gray-400">· {{ $ticket->subcategory }}</span>@endif</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Assigned to</p>
                <p class="text-gray-800 font-medium">{{ $ticket->assignee->name ?? 'Unassigned' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Created</p>
                <p class="text-gray-800 font-medium">{{ $ticket->created_at->format('M j, Y g:i A') }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Resolved</p>
                <p class="text-gray-800 font-medium">{{ $ticket->resolved_at?->format('M j, Y g:i A') ?? '—' }}</p>
            </div>
        </div>
    </div>

    @if((auth()->user()->isItSupport() || auth()->user()->isAdmin()))
        @if($ticket->isClosed())
            <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                <p class="text-sm text-gray-500">This ticket is closed — status and assignment are locked. See the solution below.</p>
            </div>
        @else
            <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t border-gray-100" x-data="{ status: '{{ old('status', $ticket->status) }}' }">
                <div class="flex flex-wrap gap-3 items-center">
                    @if(!$ticket->assigned_to)
                        <form method="POST" action="{{ route('tickets.accept', $ticket) }}">
                            @csrf
                            <button class="px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm" style="background-color:#1a6b3c;">Accept Ticket</button>
                        </form>
                    @endif

                    <form id="status-form" method="POST" action="{{ route('tickets.status', $ticket) }}" class="flex items-center gap-2 w-full sm:w-auto">
                        @csrf
                        @method('PATCH')
                        <select name="status" x-model="status"
                            class="flex-1 min-w-0 sm:flex-none rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                            {{ !$ticket->assigned_to ? 'disabled' : '' }}
                            title="{{ !$ticket->assigned_to ? 'Assign this ticket to an IT agent first' : '' }}">
                            @foreach(['open','in_progress','pending','resolved','closed'] as $status)
                                @php $locked = $status === 'closed' && ! $ticket->canBeClosed(); @endphp
                                <option value="{{ $status }}" @selected($ticket->status === $status) @disabled($locked)>{{ str_replace('_',' ', ucfirst($status)) }}{{ $locked ? ' (resolve & get approval first)' : '' }}</option>
                            @endforeach
                        </select>
                        <button
                            class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                            {{ !$ticket->assigned_to ? 'disabled' : '' }}
                            title="{{ !$ticket->assigned_to ? 'Assign this ticket to an IT agent first' : '' }}">
                            Update Status
                        </button>
                    </form>

                    @if(!$ticket->assigned_to)
                        <span class="inline-flex items-center gap-1.5 text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-2.5 py-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                            Assign this ticket to update its status
                        </span>
                    @endif

                    @if(auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('admin.tickets.assign', $ticket) }}" class="flex flex-wrap items-center gap-2 w-full sm:w-auto sm:ml-auto">
                            @csrf
                            @method('PATCH')
                            <label class="text-sm text-gray-500">Assign to:</label>
                            <select name="assigned_to" class="flex-1 min-w-0 sm:flex-none rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                                <option value="">Choose IT agent…</option>
                                @foreach(\App\Models\User::where('role', 'it_support')->orderBy('name')->get() as $agent)
                                    <option value="{{ $agent->id }}" @selected($ticket->assigned_to === $agent->id)>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                            <button class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-100">Assign</button>
                        </form>
                    @endif
                </div>

                @if($ticket->assigned_to)
                    @if($ticket->isAwaitingApproval())
                        <p class="mt-3 inline-flex items-center gap-1.5 text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-2.5 py-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Waiting for {{ $ticket->creator->name }} to approve the resolution before this ticket can be closed.
                        </p>
                    @elseif($ticket->canBeClosed())
                        <p class="mt-3 inline-flex items-center gap-1.5 text-xs text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-lg px-2.5 py-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            {{ $ticket->creator->name }} approved this on {{ $ticket->approved_at->format('M j, Y g:i A') }} — you can close it now.
                        </p>
                    @else
                        <p class="mt-3 text-xs text-gray-400">To close this ticket: set it to Resolved, then the requester approves it.</p>
                    @endif
                @endif

                <div x-show="status === 'closed'" x-cloak class="mt-3 rounded-lg border border-gray-200 bg-white px-4 py-3.5">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Solution <span class="text-red-500">*</span></label>
                    <textarea name="solution" form="status-form" rows="3" :required="status === 'closed'"
                        placeholder="Describe how this was resolved — this is shown to {{ $ticket->creator->name }} as the answer to their ticket."
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">{{ old('solution') }}</textarea>
                    @error('solution') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    <p class="text-xs text-gray-400 mt-1.5">Closing is final — the ticket can't be reopened or reassigned afterward.</p>
                </div>
            </div>
        @endif
    @endif
</div>

{{-- Requester sign-off: IT marked it Resolved, now the requester confirms it's really fixed.
     Until they do, IT Support cannot close the ticket. --}}
@if(auth()->id() === $ticket->user_id && ! $ticket->isClosed())
    @if($ticket->isAwaitingApproval())
        <div class="bg-white shadow-sm rounded-xl border border-green-200 overflow-hidden">
            <div class="px-4 sm:px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-green-100 text-green-700 shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Is your issue fixed?</p>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ $ticket->assignee->name ?? 'IT Support' }} marked this ticket as resolved. Click <strong>Approve</strong> to confirm so IT can close it.
                            If it's still not working, leave a comment below instead and IT will reopen it.
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('tickets.approve', $ticket) }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm transition hover:opacity-90" style="background-color:#1a6b3c;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Approve
                    </button>
                </form>
            </div>
        </div>
    @elseif($ticket->isApproved())
        <div class="px-5 py-3.5 rounded-xl border border-emerald-100 bg-emerald-50/60 flex items-center gap-2.5">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            <p class="text-sm text-emerald-800">You approved this resolution on {{ $ticket->approved_at->format('M j, Y g:i A') }}. IT Support will close the ticket shortly.</p>
        </div>
    @endif
@endif

@if($ticket->solution)
    <div class="bg-white shadow-sm rounded-xl border border-green-100 overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-green-100 bg-green-50/50 flex items-center gap-2.5">
            <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-green-100 text-green-700 shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-gray-800">Solution</p>
                <p class="text-xs text-gray-500">
                    Resolved by {{ $ticket->assignee->name ?? 'IT Support' }}
                    @if($ticket->closed_at) · {{ $ticket->closed_at->format('M j, Y g:i A') }} @endif
                </p>
            </div>
        </div>
        <div class="px-4 sm:px-6 py-5">
            <p class="text-gray-800 leading-relaxed whitespace-pre-line">{{ $ticket->solution }}</p>
        </div>
    </div>
@endif
