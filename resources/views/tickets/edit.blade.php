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
        'pending'     => ['label' => 'Pending User', 'hint' => 'Waiting on a reply or action from the requester', 'dot' => 'bg-purple-500', 'on' => 'border-purple-400 bg-purple-50 ring-2 ring-purple-500/20'],
        'pending_backend' => ['label' => 'Pending Backend', 'hint' => 'Needs a server-side fix (e.g. spam, cPanel, mail settings)', 'dot' => 'bg-teal-500', 'on' => 'border-teal-400 bg-teal-50 ring-2 ring-teal-500/20'],
        'pending_vendor' => ['label' => 'Awaiting Vendor', 'hint' => 'With a supplier / service provider, e.g. under warranty. Add the vendor details from the ticket page.', 'dot' => 'bg-orange-500', 'on' => 'border-orange-400 bg-orange-50 ring-2 ring-orange-500/20'],
        'resolved'    => ['label' => 'Resolved',    'hint' => 'Fixed, waiting to be closed',    'dot' => 'bg-green-600',  'on' => 'border-green-400 bg-green-50 ring-2 ring-green-500/20'],
    ];
    $statusLabels = collect($statuses)->map(fn ($st) => $st['label'])->all();
    $isUnassigned = $ticket->assigned_to === null;

    $priorityLabels = collect($priorities)->map(fn ($p) => $p['level'].' · '.$p['label'])->all();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition shrink-0" title="Back to ticket">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 leading-none mb-1">Edit ticket</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate" title="{{ $ticket->title }}">{{ $ticket->title }}</h2>
            </div>
            <span class="hidden sm:inline-flex px-2 py-1 rounded-md bg-gray-100 text-gray-600 font-mono text-xs font-semibold shrink-0">{{ $ticket->ticket_number }}</span>
        </div>
    </x-slot>

    <style>[x-cloak] { display: none !important; }</style>

    <div class="py-6 sm:py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
         x-data="{
            category: @js(old('category', $ticket->category)),
            priority: @js(old('priority', $ticket->priority)),
            origCategory: @js($ticket->category),
            origPriority: @js($ticket->priority),
            priorityLabels: @js($priorityLabels),
            status: @js(old('status', $ticket->status)),
            origStatus: @js($ticket->status),
            statusLabels: @js($statusLabels),
            saving: false,
            get categoryChanged() { return this.category !== this.origCategory },
            get priorityChanged() { return this.priority !== this.origPriority },
            get statusChanged() { return this.status !== this.origStatus },
            get dirty() { return this.categoryChanged || (this.priorityChanged && !{{ $isVipTicket ? 'true' : 'false' }}) || this.statusChanged },
            get changeCount() { return (this.categoryChanged ? 1 : 0) + ((this.priorityChanged && !{{ $isVipTicket ? 'true' : 'false' }}) ? 1 : 0) + (this.statusChanged ? 1 : 0) },
            init() {
                // Warn before leaving the page with edits that were not saved.
                window.addEventListener('beforeunload', (e) => {
                    if (this.dirty && !this.saving) { e.preventDefault(); e.returnValue = ''; }
                });
            },
         }">

        @if($errors->any())
            <div class="p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-100">{{ $errors->first() }}</div>
        @endif

        {{-- Ticket at a glance, so you know exactly what you're correcting --}}
        <div class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
            <div class="flex">
                <span class="w-1 shrink-0" style="background-color:#1a6b3c;"></span>
                <div class="flex-1 min-w-0 p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-status-badge :status="$ticket->status" />
                            <x-priority-badge :priority="$ticket->priority" />
                            <span class="text-xs text-gray-400">Submitted {{ $ticket->created_at->format('M j, Y g:i A') }}</span>
                        </div>
                        <a href="{{ route('tickets.show', $ticket) }}" class="text-xs font-medium text-green-700 hover:underline">View full ticket</a>
                    </div>
                    <p class="text-sm text-gray-700 leading-relaxed line-clamp-2 whitespace-pre-line break-words">{{ $ticket->description }}</p>
                    <dl class="mt-3 pt-3 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-3 text-sm">
                        <div class="min-w-0">
                            <dt class="text-gray-400 text-xs uppercase tracking-wide mb-0.5">Requested by</dt>
                            <dd class="font-medium text-gray-800 truncate">{{ $ticket->creator->name }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-gray-400 text-xs uppercase tracking-wide mb-0.5">Department</dt>
                            <dd class="font-medium text-gray-800 truncate">{{ $ticket->department ?? '—' }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-gray-400 text-xs uppercase tracking-wide mb-0.5">Category</dt>
                            <dd class="font-medium text-gray-800 truncate">{{ $ticket->category }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-gray-400 text-xs uppercase tracking-wide mb-0.5">Assigned to</dt>
                            <dd class="font-medium text-gray-800 truncate">{{ $ticket->assigneeLabel() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('tickets.update', $ticket) }}" @submit="saving = true"
              class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            @csrf
            @method('PUT')

            {{-- ===== Left: the three things you can change ===== --}}
            <div class="lg:col-span-2 space-y-6 min-w-0">

                {{-- Category --}}
                <section class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <span class="w-1.5 h-4 rounded-full bg-green-600"></span>
                            Category
                        </h3>
                        <span x-show="categoryChanged" x-cloak class="text-[11px] font-semibold uppercase tracking-wide text-green-700 bg-green-50 rounded-full px-2 py-0.5">Changed</span>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">What kind of help does this ticket actually need? Pick the one that fits best.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach($categories as $cat)
                            <label class="cursor-pointer">
                                <input type="radio" name="category" value="{{ $cat }}" x-model="category" class="sr-only peer" required>
                                <div class="flex items-center gap-3 rounded-xl border border-gray-200 px-3.5 py-3 text-sm text-gray-700 transition hover:border-green-400 hover:bg-green-50/40
                                            peer-checked:border-green-600 peer-checked:bg-green-50 peer-checked:text-green-900 peer-checked:ring-2 peer-checked:ring-green-600/20 peer-focus-visible:ring-2 peer-focus-visible:ring-green-600">
                                    <span class="w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center shrink-0"
                                          :class="category === @js($cat) ? '!border-green-600' : ''">
                                        <span class="w-2 h-2 rounded-full bg-green-600" x-show="category === @js($cat)" x-cloak></span>
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
                </section>

                {{-- Priority --}}
                <section class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <span class="w-1.5 h-4 rounded-full bg-green-600"></span>
                            Priority
                        </h3>
                        <span x-show="priorityChanged && {{ $isVipTicket ? 'false' : 'true' }}" x-cloak class="text-[11px] font-semibold uppercase tracking-wide text-green-700 bg-green-50 rounded-full px-2 py-0.5">Changed</span>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">How urgent is it, really? Requests and questions are usually Low.</p>

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
                                    <input type="radio" name="priority" value="{{ $value }}" x-model="priority" class="sr-only peer" required>
                                    <div class="h-full rounded-xl border px-3.5 py-3 transition peer-focus-visible:ring-2 peer-focus-visible:ring-green-600"
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
                </section>

                {{-- Status --}}
                <section class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <span class="w-1.5 h-4 rounded-full bg-green-600"></span>
                            Status
                        </h3>
                        <span x-show="statusChanged" x-cloak class="text-[11px] font-semibold uppercase tracking-wide text-green-700 bg-green-50 rounded-full px-2 py-0.5">Changed</span>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">Where is this ticket in the process? Fix it here if it was set by mistake.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach($statuses as $value => $st)
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="{{ $value }}" x-model="status" class="sr-only peer">
                                <div class="h-full rounded-xl border px-3.5 py-3 transition peer-focus-visible:ring-2 peer-focus-visible:ring-green-600"
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
                </section>
            </div>

            {{-- ===== Right: reason, live summary and the save button (stays in view while scrolling) ===== --}}
            <aside class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden lg:sticky lg:top-6">
                <div class="p-5" x-data="{ reason: @js(old('reason', '')) }">
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <h3 class="text-sm font-semibold text-gray-800">
                            Reason for the change
                            <span class="ml-1 text-[11px] font-medium text-gray-400">Optional</span>
                        </h3>
                        <span class="text-[11px] text-gray-400 tabular-nums" x-text="reason.length + ' / 500'"></span>
                    </div>
                    <p class="text-xs text-gray-500 mb-3">Added to the ticket conversation so everyone can see why it changed.</p>
                    <textarea name="reason" rows="3" maxlength="500" x-model="reason"
                              placeholder="e.g. Staff picked Phone by mistake, this is a billing request"
                              class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">{{ old('reason') }}</textarea>
                    @error('reason') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                </div>

                {{-- Live summary of what will change --}}
                <div class="mx-5 mb-5 rounded-xl border p-4 transition"
                     :class="dirty ? 'border-green-200 bg-green-50/60' : 'border-gray-200 bg-gray-50'">
                    <p class="text-xs font-semibold uppercase tracking-wide mb-2" :class="dirty ? 'text-green-700' : 'text-gray-400'">
                        Summary of changes
                        <span x-show="dirty" x-cloak x-text="'(' + changeCount + ')'"></span>
                    </p>

                    <p x-show="!dirty" class="text-sm text-gray-500">Nothing changed yet. Pick a different category, priority or status.</p>

                    <ul x-show="dirty" x-cloak class="space-y-3 text-sm">
                        <li x-show="categoryChanged">
                            <p class="text-xs text-gray-500 mb-0.5">Category</p>
                            <p class="flex flex-wrap items-center gap-x-2">
                                <span class="line-through text-gray-400" x-text="origCategory"></span>
                                <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                                <span class="font-semibold text-green-900" x-text="category"></span>
                            </p>
                        </li>
                        <li x-show="statusChanged">
                            <p class="text-xs text-gray-500 mb-0.5">Status</p>
                            <p class="flex flex-wrap items-center gap-x-2">
                                <span class="line-through text-gray-400" x-text="statusLabels[origStatus]"></span>
                                <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                                <span class="font-semibold text-green-900" x-text="statusLabels[status]"></span>
                            </p>
                        </li>
                        <li x-show="priorityChanged && {{ $isVipTicket ? 'false' : 'true' }}">
                            <p class="text-xs text-gray-500 mb-0.5">Priority</p>
                            <p class="flex flex-wrap items-center gap-x-2">
                                <span class="line-through text-gray-400" x-text="priorityLabels[origPriority]"></span>
                                <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                                <span class="font-semibold text-green-900" x-text="priorityLabels[priority]"></span>
                            </p>
                        </li>
                    </ul>

                    <p class="text-xs text-gray-500 mt-3 flex items-start gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                        <span>{{ $ticket->creator->name }}@if($ticket->assignee && $ticket->assignee->id !== $ticket->user_id) and {{ $ticket->assignee->name }}@endif will be notified, and the change is recorded in the activity log.</span>
                    </p>
                </div>

                <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 space-y-2.5">
                    <button type="submit" :disabled="!dirty || saving"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
                            style="background-color:#1a6b3c;">
                        <svg x-show="saving" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        <span x-text="saving ? 'Saving…' : 'Save changes'"></span>
                    </button>
                    <a href="{{ route('tickets.show', $ticket) }}"
                       class="w-full inline-flex items-center justify-center px-5 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 bg-white hover:bg-gray-50 transition">Cancel</a>
                    <p class="text-xs text-gray-400 text-center pt-1">
                        To assign an agent or close the ticket, use the
                        <a href="{{ route('tickets.show', $ticket) }}" class="text-green-700 hover:underline">ticket page</a>.
                    </p>
                </div>
            </aside>
        </form>
    </div>
</x-app-layout>
