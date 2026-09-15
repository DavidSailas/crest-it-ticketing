<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Chat Support Inbox</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8"
         x-data="supportInbox('{{ route('support-chat.inbox.poll') }}')" x-init="init()">

        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
            <template x-if="threads.length === 0">
                <div class="flex flex-col items-center justify-center text-center px-6 py-16">
                    <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    <p class="text-gray-500 text-sm">No conversations yet — they'll appear here once a staff member sends a message.</p>
                </div>
            </template>

            <div class="divide-y divide-gray-100">
                <template x-for="thread in threads" :key="thread.user_id">
                    <a :href="thread.url" class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50/70 transition"
                       :class="thread.unread > 0 ? 'bg-green-50/30' : ''">
                        <div class="relative shrink-0">
                            <div class="w-11 h-11 rounded-full flex items-center justify-center text-white text-sm font-bold" style="background-color:#1a6b3c;" x-text="thread.initial"></div>
                            <span x-show="thread.unread > 0" class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold ring-2 ring-white" x-text="thread.unread"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-800 truncate" :class="thread.unread > 0 ? 'text-gray-900' : ''" x-text="thread.name"></p>
                                <span x-show="thread.is_vip" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200">★ VIP</span>
                            </div>
                            <p class="text-sm truncate mt-0.5" :class="thread.unread > 0 ? 'text-gray-700 font-medium' : 'text-gray-500'" x-text="thread.last_message_preview"></p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs text-gray-400" x-text="thread.last_message_at_human"></p>
                        </div>
                    </a>
                </template>
            </div>
        </div>
    </div>

    @php
        $threadsForJs = $threads->map(fn ($t) => [
            'user_id' => $t['user']->id,
            'name' => $t['user']->name,
            'is_vip' => (bool) $t['user']->is_vip,
            'initial' => strtoupper(substr($t['user']->name, 0, 1)),
            'last_message_preview' => $t['last_message']
                ? ($t['last_message']->sender_id === auth()->id() ? 'You: ' : '').\Illuminate\Support\Str::limit($t['last_message']->body, 60)
                : 'No messages yet',
            'unread' => $t['unread'],
            'last_message_at' => \Illuminate\Support\Carbon::parse($t['last_message_at'])->toIso8601String(),
            'last_message_at_human' => \Illuminate\Support\Carbon::parse($t['last_message_at'])->diffForHumans(),
            'url' => route('support-chat.show.user', $t['user']),
        ]);
    @endphp

    <script>
        function supportInbox(pollUrl) {
            return {
                threads: {!! json_encode($threadsForJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!},
                pollTimer: null,

                init() {
                    this.pollTimer = setInterval(() => this.poll(), 5000);
                    window.addEventListener('beforeunload', () => clearInterval(this.pollTimer));
                },

                async poll() {
                    try {
                        const res = await fetch(pollUrl, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.threads = data.threads ?? this.threads;
                    } catch (e) {
                        // retried on next interval
                    }
                },
            };
        }
    </script>
</x-app-layout>
