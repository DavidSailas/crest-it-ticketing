<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Chat</h2>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8" x-data="chatInbox()">
        {{-- Initial data lives in its own script tag rather than inline inside
             an HTML attribute — embedding a large @json() blob directly in
             x-data="..." is fragile (quoting/escaping edge cases can break
             Alpine's expression parser entirely), this isn't. --}}
        <script type="application/json" id="chat-inbox-initial-threads">@json($threads)</script>

        <p class="text-sm text-gray-500 mb-5">
            @if(auth()->user()->isStaff())
                Every conversation here is tied to one of your tickets. Open a thread to chat with IT support.
            @else
                Every conversation here is tied to a ticket. Open a thread to chat with the requester.
            @endif
        </p>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 divide-y divide-gray-100 overflow-hidden">
            <template x-if="threads.length === 0">
                <div class="flex flex-col items-center justify-center text-center px-6 py-16">
                    <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-6l-4 4v-4z" />
                        </svg>
                    </div>
                    <p class="text-gray-700 font-medium">No conversations yet</p>
                    <p class="text-gray-400 text-sm mt-1 max-w-sm">
                        @if(auth()->user()->isStaff())
                            Submit a ticket and you'll be able to chat with IT support about it here.
                        @else
                            Once a ticket comes in, you'll be able to chat with the requester here.
                        @endif
                    </p>
                </div>
            </template>

            <template x-for="thread in threads" :key="thread.ticket_id">
                <a :href="thread.url" class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50/70 transition" :class="thread.unread ? 'bg-green-50/30' : ''">
                    {{-- Avatar --}}
                    <span class="w-8 h-8 rounded-full flex items-center justify-center font-semibold shrink-0 text-white text-xs"
                          :style="thread.other_party_initials ? 'background-color:#1a6b3c;' : 'background-color:#d1d5db;'"
                          x-text="thread.other_party_initials || '–'"></span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="truncate" :class="thread.unread ? 'font-semibold text-gray-900' : 'font-medium text-gray-500'" x-text="thread.other_party_name"></span>
                            <template x-if="thread.show_vip_badge">
                                <span class="inline-flex items-center gap-1 rounded-full font-medium bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200 px-1.5 py-0.5 text-[10px]">
                                    <svg class="w-2.5 h-2.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.29 4.64 5.12.74-3.7 3.61.87 5.1L10 13.9l-4.58 2.4.87-5.1-3.7-3.61 5.12-.74L10 1.5z" /></svg>
                                    VIP
                                </span>
                            </template>
                            <span class="text-xs font-mono text-gray-300" x-text="thread.ticket_number"></span>
                        </div>
                        <p class="text-sm truncate mt-0.5" :class="thread.unread ? 'font-semibold text-gray-900' : 'font-normal text-gray-500'" x-text="thread.title"></p>
                        <p class="text-sm truncate mt-0.5" :class="thread.unread ? 'text-gray-800' : 'text-gray-400'">
                            <template x-if="thread.has_messages">
                                <span>
                                    <span :class="thread.unread ? 'font-semibold text-gray-900' : 'font-medium text-gray-500'" x-text="thread.last_message_author + ':'"></span>
                                    <span x-text="' ' + thread.last_message_body"></span>
                                </span>
                            </template>
                            <template x-if="!thread.has_messages">
                                <span class="italic text-gray-400">No messages yet — say hello.</span>
                            </template>
                        </p>
                    </div>

                    <div class="shrink-0 flex flex-col items-end gap-1.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap"
                              :class="{
                                  'bg-amber-100 text-amber-700': thread.status === 'open',
                                  'bg-blue-100 text-blue-700': thread.status === 'in_progress',
                                  'bg-purple-100 text-purple-700': thread.status === 'pending',
                                  'bg-green-100 text-green-700': thread.status === 'resolved',
                                  'bg-gray-200 text-gray-600': thread.status === 'closed',
                              }">
                            <span class="w-1.5 h-1.5 rounded-full"
                                  :class="{
                                      'bg-amber-500': thread.status === 'open',
                                      'bg-blue-500': thread.status === 'in_progress',
                                      'bg-purple-500': thread.status === 'pending',
                                      'bg-green-600': thread.status === 'resolved',
                                      'bg-gray-400': thread.status === 'closed',
                                  }"></span>
                            <span x-text="thread.status_label"></span>
                        </span>
                        <span class="flex items-center gap-1.5 whitespace-nowrap">
                            <span x-show="thread.unread" class="w-2 h-2 rounded-full shrink-0" style="background-color:#1a6b3c;"></span>
                            <span class="text-xs" :class="thread.unread ? 'text-gray-600 font-medium' : 'text-gray-400'" x-text="thread.last_activity_human"></span>
                        </span>
                    </div>
                </a>
            </template>
        </div>
    </div>

    <script>
        function chatInbox() {
            const statusLabels = { open: 'Open', in_progress: 'In Progress', pending: 'Pending', resolved: 'Resolved', closed: 'Closed' };
            const decorate = (list) => (list || []).map(t => ({ ...t, status_label: statusLabels[t.status] || t.status }));

            let initialThreads = [];
            try {
                const raw = document.getElementById('chat-inbox-initial-threads')?.textContent;
                initialThreads = raw ? JSON.parse(raw) : [];
            } catch (e) {
                initialThreads = [];
            }

            return {
                threads: decorate(initialThreads),

                init() {
                    this.poll();
                    setInterval(() => this.poll(), 4000);
                    // Refresh the moment the tab regains focus, so coming back
                    // from a ticket page reflects "read" immediately.
                    window.addEventListener('focus', () => this.poll());
                },

                async poll() {
                    try {
                        const res = await fetch('{{ route('chat.poll') }}', { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.threads = decorate(data.threads);
                    } catch (e) {
                        // Silent — a missed poll just means we retry in a few seconds.
                    }
                },
            };
        }
    </script>
</x-app-layout>
