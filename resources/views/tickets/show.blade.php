<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $ticket->title }}</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-800 text-sm rounded-lg border border-red-100">{{ session('error') }}</div>
        @endif

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono font-semibold text-gray-400 mr-1">{{ $ticket->ticket_number }}</span>
                    <x-status-badge :status="$ticket->status" />
                    <x-priority-badge :priority="$ticket->priority" />
                </div>
                <div class="text-sm text-gray-400">{{ $ticket->created_at->format('M j, Y g:i A') }}</div>
            </div>

            <div class="px-6 py-6">
                <p class="text-gray-800 leading-relaxed">{{ $ticket->description }}</p>

                @if($ticket->attachment_path)
                    <a href="{{ $ticket->attachment_url }}" target="_blank"
                       class="mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 transition px-3.5 py-2.5 text-sm text-gray-700">
                        <svg class="w-4 h-4 text-green-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.5c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124V6.75A2.25 2.25 0 0110.5 4.5h.375a1.125 1.125 0 011.125 1.125v.375m0 0a2.25 2.25 0 002.25 2.25h.375a1.125 1.125 0 011.125 1.125v2.25a2.25 2.25 0 01-2.25 2.25h-6a2.25 2.25 0 01-2.25-2.25v-.75" /></svg>
                        <span class="truncate max-w-[240px] font-medium">{{ $ticket->attachment_name }}</span>
                        <span class="text-xs text-gray-400">Download</span>
                    </a>
                @endif

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-100 text-sm">
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
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                        <p class="text-sm text-gray-500">This ticket is closed — status and assignment are locked. See the solution below.</p>
                    </div>
                @else
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100" x-data="{ status: '{{ old('status', $ticket->status) }}' }">
                        <div class="flex flex-wrap gap-3 items-center">
                            @if(!$ticket->assigned_to)
                                <form method="POST" action="{{ route('tickets.accept', $ticket) }}">
                                    @csrf
                                    <button class="px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm" style="background-color:#1a6b3c;">Accept Ticket</button>
                                </form>
                            @endif

                            <form id="status-form" method="POST" action="{{ route('tickets.status', $ticket) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="status" x-model="status"
                                    class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                                    {{ !$ticket->assigned_to ? 'disabled' : '' }}
                                    title="{{ !$ticket->assigned_to ? 'Assign this ticket to an IT agent first' : '' }}">
                                    @foreach(['open','in_progress','pending','resolved','closed'] as $status)
                                        <option value="{{ $status }}" @selected($ticket->status === $status)>{{ str_replace('_',' ', ucfirst($status)) }}</option>
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
                                <span class="inline-flex items-center gap-1.5 text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-full px-2.5 py-1">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                    Assign this ticket to update its status
                                </span>
                            @endif

                            @if(auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('admin.tickets.assign', $ticket) }}" class="flex items-center gap-2 ml-auto">
                                    @csrf
                                    @method('PATCH')
                                    <label class="text-sm text-gray-500">Assign to:</label>
                                    <select name="assigned_to" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                                        <option value="">Choose IT agent…</option>
                                        @foreach(\App\Models\User::where('role', 'it_support')->orderBy('name')->get() as $agent)
                                            <option value="{{ $agent->id }}" @selected($ticket->assigned_to === $agent->id)>{{ $agent->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-100">Assign</button>
                                </form>
                            @endif
                        </div>

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

        @if($ticket->solution)
            <div class="bg-white shadow-sm rounded-xl border border-green-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-green-100 bg-green-50/50 flex items-center gap-2.5">
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
                <div class="px-6 py-5">
                    <p class="text-gray-800 leading-relaxed whitespace-pre-line">{{ $ticket->solution }}</p>
                </div>
            </div>
        @endif

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden scroll-mt-6" x-data="ticketComments({
                ticketId: {{ $ticket->id }},
                pollUrl: '{{ route('tickets.chat.poll', $ticket) }}',
                sendUrl: '{{ route('tickets.comment', $ticket) }}',
                currentUserId: {{ auth()->id() }},
                latestId: {{ $ticket->comments->max('id') ?? 0 }},
                initialComments: {{ $ticket->comments->sortBy('created_at')->values()->map(fn($c) => [
                    'id' => $c->id,
                    'body' => $c->body,
                    'author_name' => $c->author->name,
                    'author_id' => $c->user_id,
                    'is_mine' => $c->user_id === auth()->id(),
                    'is_it' => $c->author->role !== 'staff',
                    'created_at' => $c->created_at->toIso8601String(),
                ])->toJson() }}
             })" x-init="init()">

            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                        Comments
                        <span class="px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs font-medium" x-text="comments.length"></span>
                    </h3>
                    <p class="text-sm text-gray-400 mt-0.5">
                        @if($ticket->isClosed())
                            This ticket is closed — comments are now read-only.
                        @elseif((auth()->user()->isItSupport() || auth()->user()->isAdmin()) && !$ticket->assigned_to)
                            Accept this ticket first — you can't comment until it's assigned.
                        @elseif(auth()->user()->isStaff())
                            Add a comment or reply to IT support about this ticket.
                        @else
                            Log updates, findings, or the resolution — {{ $ticket->creator->name }} will see them here.
                        @endif
                    </p>
                </div>
            </div>

            <div x-ref="scrollArea" class="max-h-[480px] overflow-y-auto">
                <template x-if="comments.length === 0">
                    <div class="flex flex-col items-center justify-center text-center px-6 py-14">
                        <svg class="w-9 h-9 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        <p class="text-sm text-gray-400">No comments yet.</p>
                    </div>
                </template>

                <template x-for="comment in comments" :key="comment.id">
                    <div class="flex gap-3 px-6 py-4 border-b border-gray-50 last:border-0" :class="comment.is_mine ? 'bg-green-50/30' : ''">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
                             :style="`background-color: ${comment.is_it ? '#1a6b3c' : '#6b7280'}`"
                             x-text="initials(comment.author_name)"></div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-semibold text-gray-800" x-text="comment.author_name"></span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide"
                                      :class="comment.is_it ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                      x-text="comment.is_it ? 'IT Support' : 'Requester'"></span>
                                <span class="text-xs text-gray-300" x-text="formatDateTime(comment.created_at)"></span>
                            </div>
                            <p class="text-sm text-gray-700 mt-1 whitespace-pre-line break-words leading-relaxed" x-text="comment.body"></p>
                        </div>
                    </div>
                </template>
            </div>

            @if($ticket->isClosed())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/60 flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                    <p class="text-sm text-gray-500">This ticket is closed, so commenting is disabled. Submit a new ticket if you need further help.</p>
                </div>
            @elseif((auth()->user()->isItSupport() || auth()->user()->isAdmin()) && !$ticket->assigned_to)
                <div class="px-6 py-4 border-t border-gray-100 bg-amber-50/60 flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    <p class="text-sm text-amber-800">This ticket isn't assigned yet — accept it above before commenting.</p>
                </div>
            @else
                <form @submit.prevent="send" class="px-6 py-4 border-t border-gray-100 bg-gray-50/40">
                    <div class="flex gap-3">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0" style="background-color:#1a6b3c;">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="flex-1">
                            <textarea
                                x-ref="input"
                                x-model="draft"
                                @keydown.enter.prevent="if (!$event.shiftKey) send()"
                                rows="3"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 resize-none bg-white"
                                placeholder="Write a comment..."
                                :disabled="sending"
                            ></textarea>
                            <div class="flex items-center justify-between mt-2">
                                <p class="text-xs text-gray-400"><kbd class="px-1.5 py-0.5 rounded border border-gray-200 bg-white font-sans">Enter</kbd> to post, <kbd class="px-1.5 py-0.5 rounded border border-gray-200 bg-white font-sans">Shift+Enter</kbd> for a new line</p>
                                <button type="submit"
                                    class="px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm transition disabled:opacity-50"
                                    style="background-color:#1a6b3c;"
                                    :disabled="sending || draft.trim() === ''">
                                    <span x-show="!sending">Post Comment</span>
                                    <span x-show="sending">Posting…</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>

        <script>
            function ticketComments({ ticketId, pollUrl, sendUrl, currentUserId, latestId, initialComments }) {
                return {
                    comments: initialComments,
                    latestId: latestId,
                    draft: '',
                    sending: false,
                    pollTimer: null,

                    init() {
                        this.pollTimer = setInterval(() => this.poll(), 4000);
                        window.addEventListener('beforeunload', () => clearInterval(this.pollTimer));
                    },

                    initials(name) {
                        return name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase();
                    },

                    formatDateTime(iso) {
                        const d = new Date(iso);
                        return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' at ' + d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                    },

                    async poll() {
                        try {
                            const res = await fetch(`${pollUrl}?since_id=${this.latestId}`, {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (!res.ok) return;
                            const data = await res.json();

                            if (data.messages && data.messages.length > 0) {
                                const existingIds = new Set(this.comments.map(c => c.id));
                                data.messages.forEach(m => {
                                    if (!existingIds.has(m.id)) {
                                        this.comments.push({
                                            id: m.id,
                                            body: m.body,
                                            author_name: m.author_name,
                                            author_id: m.author_id,
                                            is_mine: m.is_mine,
                                            is_it: m.is_it,
                                            created_at: m.created_at,
                                        });
                                    }
                                });
                            }
                            this.latestId = data.latest_id ?? this.latestId;
                        } catch (e) {
                            // Silently retry on the next interval — no need to alarm the user
                            // over a single missed poll (e.g. brief network blip).
                        }
                    },

                    async send() {
                        const body = this.draft.trim();
                        if (body === '' || this.sending) return;

                        this.sending = true;

                        try {
                            const res = await fetch(sendUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({ body }),
                            });

                            if (res.ok) {
                                const data = await res.json();
                                this.comments.push({
                                    id: data.comment.id,
                                    body: data.comment.body,
                                    author_name: data.comment.author_name,
                                    author_id: data.comment.author_id,
                                    is_mine: true,
                                    is_it: {{ auth()->user()->isStaff() ? 'false' : 'true' }},
                                    created_at: data.comment.created_at,
                                });
                                this.latestId = Math.max(this.latestId, data.comment.id);
                                this.draft = '';
                            }
                        } catch (e) {
                            // leave the draft text in place so nothing is lost
                        } finally {
                            this.sending = false;
                            this.$refs.input?.focus();
                        }
                    },
                };
            }
        </script>
    </div>
</x-app-layout>
