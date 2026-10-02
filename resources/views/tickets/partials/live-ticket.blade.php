<div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
    <div class="px-4 sm:px-6 py-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-mono font-semibold text-gray-400 mr-1">{{ $ticket->ticket_number }}</span>
            <x-status-badge :status="$ticket->status" />
            <x-priority-badge :priority="$ticket->priority" />
        </div>
        <div class="flex items-center gap-3">
            @if((auth()->user()->isItSupport() || auth()->user()->isAdmin()) && ! $ticket->isFinished())
                <a href="{{ route('tickets.edit', $ticket) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                    Edit
                </a>
            @endif
            <div class="text-sm text-gray-400">{{ $ticket->created_at->format('M j, Y g:i A') }}</div>
        </div>
    </div>

    @if($ticket->isCancelled())
        <div class="px-4 sm:px-6 py-4 bg-red-50/60 border-b border-red-100 flex items-start gap-3">
            <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-red-100 text-red-600 shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </span>
            <div class="min-w-0 text-sm">
                <p class="font-semibold text-red-800">This ticket was cancelled</p>
                <p class="text-red-700/80 mt-0.5">
                    @if($ticket->canceller)
                        {{ $ticket->canceller->id === auth()->id() ? 'You' : $ticket->canceller->name }} cancelled it
                    @else
                        Cancelled
                    @endif
                    @if($ticket->cancelled_at) on {{ $ticket->cancelled_at->format('M j, Y g:i A') }} @endif
                    before it was accepted by IT Support.
                </p>
                @if($ticket->cancel_reason)
                    <p class="mt-1.5 text-red-900/80 break-words"><span class="font-medium">Reason:</span> {{ $ticket->cancel_reason }}</p>
                @endif
            </div>
        </div>
    @endif

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
                <p class="text-gray-800 font-medium">{{ $ticket->assigneeLabel() }}</p>
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
        @elseif($ticket->isCancelled())
            {{-- Cancelled: the banner above says it all; nothing left to manage. --}}
        @else
            @php
                $user = auth()->user();
                $isAdminUser = $user->isAdmin();
                $isMine = $ticket->assigned_to === $user->id;
                $canReassign = $isAdminUser || ! $ticket->assigned_to || $isMine;
                $reassignLabel = ! $ticket->assigned_to ? 'Assign to an agent' : ($isMine ? 'Hand off to another agent' : 'Reassign to another agent');
            @endphp
            <div class="border-t border-gray-100 bg-gray-50/70" x-data="{ status: '{{ old('status', $ticket->status) }}' }">
                <div class="px-4 sm:px-6 py-4 flex items-center gap-2 text-sm font-semibold text-gray-600">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Manage this ticket
                </div>

                <div class="px-4 sm:px-6 pb-5 space-y-4">
                    {{-- Assignment: who has this ticket right now, and the one-click accept --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            @if($ticket->assignee)
                                <x-avatar :user="$ticket->assignee" size="sm" />
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-400">Currently assigned to</p>
                                    <p class="text-sm font-medium text-gray-800 truncate">
                                        {{ $ticket->assignee->name }}
                                        @if($isMine)<span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-green-100 text-green-700">You</span>@endif
                                    </p>
                                </div>
                            @else
                                <span class="w-9 h-9 rounded-full border border-dashed border-amber-300 bg-amber-50 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs text-amber-700 font-medium">Not yet assigned</p>
                                    <p class="text-sm text-gray-500">Accept it yourself or assign it below.</p>
                                </div>
                            @endif
                        </div>

                        @if(!$ticket->assigned_to)
                            <form method="POST" action="{{ route('tickets.accept', $ticket) }}">
                                @csrf
                                <button class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm hover:opacity-90 transition" style="background-color:#1a6b3c;">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    Accept Ticket
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 {{ $canReassign ? 'sm:grid-cols-2' : '' }} gap-3">
                        {{-- Status --}}
                        <form id="status-form" method="POST" action="{{ route('tickets.status', $ticket) }}"
                              class="rounded-lg border border-gray-200 bg-white p-3.5">
                            @csrf
                            @method('PATCH')
                            <label for="status-select" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Status</label>
                            <div class="flex items-center gap-2">
                                <select id="status-select" name="status" x-model="status"
                                    class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                                    {{ !$ticket->assigned_to ? 'disabled' : '' }}
                                    title="{{ !$ticket->assigned_to ? 'Assign this ticket to an IT agent first' : '' }}">
                                    @foreach(['open','in_progress','pending','resolved','closed'] as $status)
                                        @php $locked = $status === 'closed' && ! $ticket->canBeClosed(); @endphp
                                        <option value="{{ $status }}" @selected($ticket->status === $status) @disabled($locked)>{{ str_replace('_',' ', ucfirst($status)) }}{{ $locked ? ' (mark as Resolved first)' : '' }}</option>
                                    @endforeach
                                </select>
                                <button
                                    class="shrink-0 px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-gray-900 transition disabled:opacity-40 disabled:cursor-not-allowed"
                                    {{ !$ticket->assigned_to ? 'disabled' : '' }}
                                    title="{{ !$ticket->assigned_to ? 'Assign this ticket to an IT agent first' : '' }}">
                                    Update
                                </button>
                            </div>
                        </form>

                        {{-- Assign / reassign --}}
                        @if($canReassign)
                            <form method="POST" action="{{ route('tickets.assign', $ticket) }}"
                                  class="rounded-lg border border-gray-200 bg-white p-3.5" x-data="{ agent: '' }">
                                @csrf
                                @method('PATCH')
                                <label for="assign-select" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">{{ $reassignLabel }}</label>
                                <div class="flex items-center gap-2">
                                    <select id="assign-select" name="assigned_to" x-model="agent" class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                                        <option value="">Choose an IT agent…</option>
                                        @foreach(\App\Models\User::where('role', 'it_support')->orderBy('name')->get() as $agent)
                                            <option value="{{ $agent->id }}" @selected($ticket->assigned_to === $agent->id)>{{ $agent->name }}{{ $agent->id === $user->id ? ' (you)' : '' }}</option>
                                        @endforeach
                                    </select>
                                    <button :disabled="!agent || agent == {{ (int) $ticket->assigned_to }}"
                                            class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-100 transition disabled:opacity-40 disabled:cursor-not-allowed">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                        {{ $ticket->assigned_to ? 'Reassign' : 'Assign' }}
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>

                    @if(!$ticket->assigned_to)
                        <p class="inline-flex items-center gap-1.5 text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-2.5 py-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                            Status updates unlock once this ticket has an assigned agent.
                        </p>
                    @endif

                @if($ticket->assigned_to && $ticket->status === 'resolved')
                    <p class="inline-flex items-center gap-1.5 text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded-lg px-2.5 py-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a2.25 2.25 0 00-2.291.593l-.657.657c-.28.28-.7.36-1.076.238a12.35 12.35 0 01-7.286-7.286c-.122-.376-.042-.796.238-1.076l.657-.657a2.25 2.25 0 00.593-2.292l-1.106-4.423A1.125 1.125 0 007.25 2.25H5.875a2.25 2.25 0 00-2.25 2.25v2.25z" /></svg>
                        Call or message {{ $ticket->creator->name }} to confirm the fix before closing this ticket.
                    </p>
                @endif

                <div x-show="status === 'closed'" x-cloak class="rounded-lg border border-gray-200 bg-white px-4 py-3.5">
                    <div class="flex items-start gap-2 mb-3 px-3 py-2.5 rounded-lg bg-amber-50 border border-amber-100">
                        <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        <p class="text-xs text-amber-800">
                            <strong>Before closing:</strong> confirm with {{ $ticket->creator->name }} that the issue is actually fixed — this ticket can't be reopened once closed.
                        </p>
                    </div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Solution <span class="text-red-500">*</span></label>
                    <textarea name="solution" form="status-form" rows="3" :required="status === 'closed'"
                        placeholder="Describe how this was resolved — this is shown to {{ $ticket->creator->name }} as the answer to their ticket."
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">{{ old('solution') }}</textarea>
                    @error('solution') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    <p class="text-xs text-gray-400 mt-1.5">Closing is final — the ticket can't be reopened or reassigned afterward.</p>
                </div>
                </div>
            </div>
        @endif
    @endif

    @php
        $viewer = auth()->user();
        $isRequester = $ticket->user_id === $viewer->id;
        $canCancel = $ticket->canBeCancelled() && ($isRequester || $viewer->isItSupport() || $viewer->isAdmin());
    @endphp
    @if($canCancel)
        <div class="px-4 sm:px-6 py-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-800">{{ $isRequester ? 'Raised this by mistake or no longer need help?' : 'Duplicate or invalid request?' }}</p>
                <p class="text-xs text-gray-500 mt-0.5">You can cancel this ticket until IT Support accepts it. After that, cancelling is no longer possible.</p>
            </div>

            <x-confirm-action-modal
                id="cancel-ticket-{{ $ticket->id }}"
                form="cancel-ticket-form"
                method="POST"
                tone="warning"
                title="Cancel ticket {{ $ticket->ticket_number }}?"
                message="{{ $isRequester ? 'IT Support will no longer see this request in their queue.' : 'The requester will be notified that this ticket was cancelled, and it will leave the IT queue.' }} This can’t be undone — you’d need to submit a new ticket."
                confirmLabel="Yes, cancel ticket"
                cancelLabel="Keep ticket"
                busyLabel="Cancelling…"
                confirmClass="bg-red-600 hover:bg-red-700"
                triggerLabel="Cancel ticket"
                triggerClass="shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-red-200 bg-white text-sm font-semibold text-red-600 hover:bg-red-50 transition"
            >
                <label for="cancel-reason-{{ $ticket->id }}" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Reason <span class="font-normal normal-case text-gray-400">(optional)</span></label>
                <textarea id="cancel-reason-{{ $ticket->id }}" name="reason" form="cancel-ticket-form" rows="3" maxlength="500"
                          placeholder="e.g. The issue fixed itself / submitted twice"
                          class="block w-full rounded-lg border-gray-300 text-sm focus:border-red-500 focus:ring-red-500 resize-none"></textarea>
            </x-confirm-action-modal>

            <form id="cancel-ticket-form" method="POST" action="{{ route('tickets.cancel', $ticket) }}" class="hidden">@csrf</form>
        </div>
    @endif
</div>

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
