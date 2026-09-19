<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $ticket->title }}</h2>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-800 text-sm rounded-lg border border-red-100">{{ session('error') }}</div>
        @endif

        {{-- Wide monitors: ticket details on the left, conversation on the right.
             Anything narrower than xl stacks them. --}}
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 items-start">
            {{-- Live region: refreshed automatically when status / approval / assignment change --}}
            <div class="xl:col-span-3 min-w-0 space-y-6" data-live="ticket" data-hash="{{ $ticket->liveHash() }}">
                @include('tickets.partials.live-ticket')
            </div>

            <div class="xl:col-span-2 min-w-0 xl:sticky xl:top-6">
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

                <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                            Comments
                            <span class="px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs font-medium" x-text="comments.length"></span>
                        </h3>
                        <p data-live="hint" data-hash="{{ $ticket->threadHash() }}" class="text-sm text-gray-400 mt-0.5">
                            @include('tickets.partials.live-hint')
                        </p>
                    </div>
                </div>

                <div x-ref="scrollArea" class="max-h-[55vh] min-h-[8rem] overflow-y-auto">
                    <template x-if="comments.length === 0">
                        <div class="flex flex-col items-center justify-center text-center px-4 sm:px-6 py-14">
                            <svg class="w-9 h-9 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                            <p class="text-sm text-gray-400">No comments yet.</p>
                        </div>
                    </template>

                    <template x-for="comment in comments" :key="comment.id">
                        <div class="flex gap-3 px-4 sm:px-6 py-4 border-b border-gray-50 last:border-0" :class="comment.is_mine ? 'bg-green-50/30' : ''">
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

                <div data-live="composer" data-hash="{{ $ticket->threadHash() }}">
                    @include('tickets.partials.live-composer')
                </div>
            </div>
            </div>
        </div>

        <script>
            // Real-time refresh: every few seconds ask the server whether the ticket panel or the
            // comment box changed (e.g. the requester approved, IT resolved / closed / reassigned)
            // and swap in only the parts that did. Comment drafts and the chat are never touched.
            (function () {
                const url = @json(route('tickets.live', $ticket));
                const INTERVAL = 4000;
                let busy = false;

                function flash(el) {
                    el.classList.add('ring-2', 'ring-emerald-300', 'rounded-xl', 'transition');
                    setTimeout(() => el.classList.remove('ring-2', 'ring-emerald-300'), 2200);
                }

                async function refresh() {
                    if (document.hidden || busy) return;
                    busy = true;

                    try {
                        const params = new URLSearchParams();
                        document.querySelectorAll('[data-live]').forEach(el => {
                            params.append(`h[${el.dataset.live}]`, el.dataset.hash);
                        });

                        const res = await fetch(`${url}?${params}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) return;

                        const data = await res.json();
                        Object.entries(data.regions || {}).forEach(([name, region]) => {
                            const el = document.querySelector(`[data-live="${name}"]`);
                            if (!el) return;
                            el.innerHTML = region.html;
                            el.dataset.hash = region.hash;
                            if (name === 'ticket') flash(el);
                        });
                    } catch (e) {
                        // A missed poll (network blip, expired session) just retries next tick.
                    } finally {
                        busy = false;
                    }
                }

                setInterval(refresh, INTERVAL);
                document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
            })();
        </script>

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
