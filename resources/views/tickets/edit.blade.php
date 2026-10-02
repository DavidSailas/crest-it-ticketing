@php
    $isVipTicket = (bool) $ticket->creator?->is_vip;

    // One entry per priority — colours match <x-priority-badge> so it feels like the same system.
    $priorities = [
        'critical' => ['level' => 'P1', 'label' => 'Critical', 'hint' => 'Outage affecting several people or a whole department',
                       'on' => 'border-red-400 bg-red-50 ring-2 ring-red-500/20',      'dot' => 'bg-red-500',    'chip' => 'bg-red-100 text-red-700'],
        'high'     => ['level' => 'P2', 'label' => 'High', 'hint' => 'Blocks one person from working, no workaround',
                       'on' => 'border-orange-400 bg-orange-50 ring-2 ring-orange-500/20', 'dot' => 'bg-orange-500', 'chip' => 'bg-orange-100 text-orange-700'],
        'medium'   => ['level' => 'P3', 'label' => 'Medium', 'hint' => 'Work is affected, but there is a workaround',
                       'on' => 'border-amber-400 bg-amber-50 ring-2 ring-amber-500/20',  'dot' => 'bg-amber-500',  'chip' => 'bg-amber-100 text-amber-700'],
        'low'      => ['level' => 'P4', 'label' => 'Low', 'hint' => 'Routine request or question, no urgency',
                       'on' => 'border-slate-400 bg-slate-50 ring-2 ring-slate-400/20',  'dot' => 'bg-slate-400',  'chip' => 'bg-slate-100 text-slate-600'],
    ];

    // Status options. 'Closed' is intentionally missing: closing needs a written solution,
    // so it stays on the ticket page.
    $statuses = [
        'open'        => ['label' => 'Open',        'hint' => 'Waiting to be picked up',        'dot' => 'bg-amber-500',  'on' => 'border-amber-400 bg-amber-50 ring-2 ring-amber-500/20'],
        'in_progress' => ['label' => 'In Progress', 'hint' => 'An agent is working on it',      'dot' => 'bg-blue-500',   'on' => 'border-blue-400 bg-blue-50 ring-2 ring-blue-500/20'],
        'pending'     => ['label' => 'Pending',     'hint' => 'Waiting on the requester or a vendor', 'dot' => 'bg-purple-500', 'on' => 'border-purple-400 bg-purple-50 ring-2 ring-purple-500/20'],
        'resolved'    => ['label' => 'Resolved',    'hint' => 'Fixed, waiting to be closed',    'dot' => 'bg-green-600',  'on' => 'border-green-400 bg-green-50 ring-2 ring-green-500/20'],
    ];
    $statusLabels = collect($statuses)->map(fn ($st) => $st['label'])->all();
    $isUnassigned = $ticket->assigned_to === null;

    $priorityLabels = collect($priorities)->map(fn ($p) => $p['level'].' · '.$p['label'])->all();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition" title="Back to ticket">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Ticket</h2>
            <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-100 font-mono text-xs font-semibold">{{ $ticket->ticket_number }}</span>
        </div>
    </x-slot>

    <style>[x-cloak] { display: none !important; }</style>

    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
         x-data="{
            category: @js(old('category', $ticket->category)),
            priority: @js(old('priority', $ticket->priority)),
            origCategory: @js($ticket->category),
            origPriority: @js($ticket->priority),
            priorityLabels: @js($priorityLabels),
            status: @js(old('status', $ticket->status)),
            origStatus: @js($ticket->status),
            statusLabels: @js($statusLabels),
            get categoryChanged() { return this.category !== this.origCategory },
            get priorityChanged() { return this.priority !== this.origPriority },
            get statusChanged() { return this.status !== this.origStatus },
            get dirty() { return this.categoryChanged || (this.priorityChanged && !{{ $isVipTicket ? 'true' : 'false' }}) || this.statusChanged },
         }">

        @if($errors->any())
            <div class="p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-100">{{ $errors->first() }}</div>
        @endif

        {{-- Ticket at a glance, so you know exactly what you're correcting --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-50 via-white to-white shadow-sm rounded-2xl border border-indigo-100 p-5">
            <span class="absolute inset-x-0 top-0 h-1 bg-indigo-500"></span>
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <x-status-badge :status="$ticket->status" />
                <x-priority-badge :priority="$ticket->priority" />
                <span class="text-xs text-gray-400">Submitted {{ $ticket->created_at->format('M j, Y g:i A') }}</span>
            </div>
            <p class="text-sm text-gray-800 leading-relaxed line-clamp-3 whitespace-pre-line">{{ $ticket->description }}</p>
            <div class="mt-4 pt-4 border-t border-indigo-100/70 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-0.5">Requested by</p>
                    <p class="font-medium text-gray-800">{{ $ticket->creator->name }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-0.5">Department</p>
                    <p class="font-medium text-gray-800">{{ $ticket->department ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-0.5">Category</p>
                    <p class="font-medium text-gray-800">{{ $ticket->category }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide mb-0.5">Assigned to</p>
                    <p class="font-medium text-gray-800">{{ $ticket->assignee->name ?? 'Unassigned' }}</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Category --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                        <span class="w-1.5 h-4 rounded-full bg-indigo-500"></span>
                        Category
                    </h3>
                    <span x-show="categoryChanged" x-cloak class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600 bg-indigo-50 rounded-full px-2 py-0.5">Changed</span>
                </div>
                <p class="text-xs text-gray-400 mb-4">What kind of help does this ticket actually need? Pick the one that fits best.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    @foreach($categories as $cat)
                        <label class="cursor-pointer">
                            <input type="radio" name="category" value="{{ $cat }}" x-model="category" class="sr-only peer" required>
                            <div class="flex items-center gap-3 rounded-xl border border-gray-200 px-3.5 py-3 text-sm text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50/40
                                        peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-900 peer-checked:ring-2 peer-checked:ring-indigo-500/20 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500">
                                <span class="w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center shrink-0"
                                      :class="category === @js($cat) ? '!border-indigo-600' : ''">
                                    <span class="w-2 h-2 rounded-full bg-indigo-600" x-show="category === @js($cat)" x-cloak></span>
                                </span>
                                <span class="font-medium">{{ $cat }}</span>
                                @if($cat === $ticket->category)
                                    <span class="ml-auto text-[10px] font-semibold uppercase tracking-wide text-gray-400">Current</span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('category') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
            </div>

            {{-- Priority --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                        <span class="w-1.5 h-4 rounded-full bg-orange-500"></span>
                        Priority
                    </h3>
                    <span x-show="priorityChanged && {{ $isVipTicket ? 'false' : 'true' }}" x-cloak class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600 bg-indigo-50 rounded-full px-2 py-0.5">Changed</span>
                </div>
                <p class="text-xs text-gray-400 mb-4">How urgent is it, really? Requests and questions are usually Low.</p>

                @if($isVipTicket)
                    <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5">
                        <span class="text-amber-500 text-lg">★</span>
                        <div>
                            <p class="text-sm font-semibold text-amber-900">P1 · Critical (VIP account)</p>
                            <p class="text-xs text-amber-800/80">{{ $ticket->creator->name }} is a VIP, so this ticket always stays Critical.</p>
                        </div>
                    </div>
                    <input type="hidden" name="priority" value="critical">
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach($priorities as $value => $p)
                            <label class="cursor-pointer">
                                <input type="radio" name="priority" value="{{ $value }}" x-model="priority" class="sr-only" required>
                                <div class="h-full rounded-xl border px-3.5 py-3 transition"
                                     :class="priority === '{{ $value }}' ? '{{ $p['on'] }}' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $p['dot'] }}"></span>
                                        <span class="text-sm font-semibold text-gray-800">{{ $p['level'] }} · {{ $p['label'] }}</span>
                                        @if($value === $ticket->priority)
                                            <span class="ml-auto text-[10px] font-semibold uppercase tracking-wide text-gray-400">Current</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 leading-snug">{{ $p['hint'] }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('priority') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
            </div>

            {{-- Status --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                        <span class="w-1.5 h-4 rounded-full bg-blue-500"></span>
                        Status
                    </h3>
                    <span x-show="statusChanged" x-cloak class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600 bg-indigo-50 rounded-full px-2 py-0.5">Changed</span>
                </div>
                <p class="text-xs text-gray-400 mb-4">Where is this ticket in the process? Fix it here if it was set by mistake.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    @foreach($statuses as $value => $st)
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="{{ $value }}" x-model="status" class="sr-only">
                            <div class="h-full rounded-xl border px-3.5 py-3 transition"
                                 :class="status === '{{ $value }}' ? '{{ $st['on'] }}' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $st['dot'] }}"></span>
                                    <span class="text-sm font-semibold text-gray-800">{{ $st['label'] }}</span>
                                    @if($value === $ticket->status)
                                        <span class="ml-auto text-[10px] font-semibold uppercase tracking-wide text-gray-400">Current</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 mt-1 leading-snug">{{ $st['hint'] }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
                <p x-show="status === 'resolved' && origStatus !== 'resolved'" x-cloak class="mt-3 text-xs text-green-700 bg-green-50 rounded-lg px-3 py-2">
                    The requester will be asked to confirm the fix. To close the ticket, use the ticket page, where you add the solution.
                </p>
                <p x-show="(status === 'open' || status === 'in_progress') && (origStatus === 'resolved')" x-cloak class="mt-3 text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2">
                    Reopening clears the "resolved" date, so the requester has to approve again next time.
                </p>
                @if($isUnassigned)
                    <p class="mt-3 text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2">
                        This ticket has not been accepted yet. You can still correct it here, and accept or assign it from the
                        <a href="{{ route('tickets.show', $ticket) }}" class="font-medium underline">ticket page</a>.
                    </p>
                @endif
                @error('status') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
            </div>

            {{-- Reason (optional) --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 sm:p-6" x-data="{ reason: @js(old('reason', '')) }">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                        <span class="w-1.5 h-4 rounded-full bg-teal-500"></span>
                        Reason for the change
                        <span class="text-[11px] font-medium text-gray-400">Optional</span>
                    </h3>
                    <span class="text-[11px] text-gray-400 tabular-nums" x-text="reason.length + ' / 500'"></span>
                </div>
                <p class="text-xs text-gray-400 mb-3">A short note, for example "Staff picked Phone by mistake, this is a billing request". It is added to the ticket conversation so everyone can see why it changed.</p>
                <textarea name="reason" rows="2" maxlength="500" x-model="reason"
                          placeholder="Why are you changing this ticket?"
                          class="block w-full rounded-lg border-gray-300 text-sm focus:border-teal-600 focus:ring-teal-600">{{ old('reason') }}</textarea>
                @error('reason') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
            </div>

            {{-- Live summary of what will change --}}
            <div class="rounded-2xl border p-4 sm:p-5 transition"
                 :class="dirty ? 'border-indigo-200 bg-indigo-50/60' : 'border-gray-200 bg-gray-50'">
                <p class="text-xs font-semibold uppercase tracking-wide mb-2" :class="dirty ? 'text-indigo-700' : 'text-gray-400'">Summary of changes</p>

                <p x-show="!dirty" class="text-sm text-gray-500">Nothing changed yet. Pick a different category, priority or status above.</p>

                <ul x-show="dirty" x-cloak class="space-y-1.5 text-sm">
                    <li x-show="categoryChanged" class="flex flex-wrap items-center gap-2">
                        <span class="text-gray-500 w-16">Category</span>
                        <span class="line-through text-gray-400" x-text="origCategory"></span>
                        <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        <span class="font-semibold text-indigo-900" x-text="category"></span>
                    </li>
                    <li x-show="statusChanged" class="flex flex-wrap items-center gap-2">
                        <span class="text-gray-500 w-16">Status</span>
                        <span class="line-through text-gray-400" x-text="statusLabels[origStatus]"></span>
                        <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        <span class="font-semibold text-indigo-900" x-text="statusLabels[status]"></span>
                    </li>
                    <li x-show="priorityChanged && {{ $isVipTicket ? 'false' : 'true' }}" class="flex flex-wrap items-center gap-2">
                        <span class="text-gray-500 w-16">Priority</span>
                        <span class="line-through text-gray-400" x-text="priorityLabels[origPriority]"></span>
                        <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        <span class="font-semibold text-indigo-900" x-text="priorityLabels[priority]"></span>
                    </li>
                </ul>

                <p class="text-xs text-gray-400 mt-3 flex items-start gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                    {{ $ticket->creator->name }}@if($ticket->assignee && $ticket->assignee->id !== $ticket->user_id) and {{ $ticket->assignee->name }}@endif will be notified, and the change is recorded in the activity log.
                </p>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-xs text-gray-400">
                    Need to assign an agent or close the ticket? Do that from the
                    <a href="{{ route('tickets.show', $ticket) }}" class="text-indigo-600 hover:underline">ticket page</a>.
                </p>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('tickets.show', $ticket) }}" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</a>
                    <button type="submit" :disabled="!dirty"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed"
                            style="background-color:#1a6b3c;">
                        Save changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
