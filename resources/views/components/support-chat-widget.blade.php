@props(['threadOwner', 'messages', 'otherPartyLastReadAt', 'isOwnThread', 'sendUrl', 'pollUrl'])

<div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden" x-data="supportChat({
        pollUrl: '{{ $pollUrl }}',
        sendUrl: '{{ $sendUrl }}',
        currentUserId: {{ auth()->id() }},
        latestId: {{ $messages->max('id') ?? 0 }},
        otherPartyLastReadAt: {{ $otherPartyLastReadAt ? "'".\Illuminate\Support\Carbon::parse($otherPartyLastReadAt)->toIso8601String()."'" : 'null' }},
        initialMessages: {{ $messages->map(fn($m) => [
            'id' => $m->id,
            'body' => $m->body,
            'sender_name' => $m->sender->name,
            'sender_id' => $m->sender_id,
            'is_mine' => $m->sender_id === auth()->id(),
            'created_at' => $m->created_at->toIso8601String(),
        ])->toJson() }}
     })" x-init="init()">

    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                Chat Support
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
            </h3>
            <p class="text-sm text-gray-400 mt-0.5">
                @if($isOwnThread)
                    Message IT Support directly — no ticket required for a quick question.
                @else
                    Talking with {{ $threadOwner->name }}.
                @endif
            </p>
        </div>
    </div>

    <div x-ref="scrollArea" class="px-6 py-5 space-y-4 max-h-[420px] overflow-y-auto bg-gray-50/40">
        <template x-if="messages.length === 0">
            <p class="text-gray-400 text-sm">No messages yet — say hello.</p>
        </template>

        <template x-for="(group, gi) in groupedMessages()" :key="gi">
            <div class="flex flex-col" :class="group.is_mine ? 'items-end' : 'items-start'">
                <span class="text-xs font-medium text-gray-400 mb-1 px-1" x-show="!group.is_mine" x-text="group.sender_name"></span>

                <div class="space-y-1" :class="group.is_mine ? 'items-end flex flex-col' : ''">
                    <template x-for="(message, mi) in group.messages" :key="message.id">
                        <div class="group flex flex-col" :class="group.is_mine ? 'items-end' : 'items-start'">
                            <div class="inline-block max-w-xs sm:max-w-md break-words px-4 py-2.5 text-sm leading-relaxed rounded-2xl"
                                 :class="group.is_mine ? 'text-white rounded-br-sm' : 'bg-white border border-gray-200 text-gray-700 rounded-bl-sm shadow-sm'"
                                 :style="group.is_mine ? 'background-color:#1a6b3c;' : ''">
                                <p class="whitespace-pre-line" x-text="message.body"></p>
                            </div>

                            <div class="flex items-center gap-1.5 mt-1 px-1 h-3.5">
                                <span class="text-[11px] text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" x-text="formatTime(message.created_at)"></span>
                                <template x-if="group.is_mine && mi === group.messages.length - 1">
                                    <span class="text-[11px] flex items-center gap-0.5" :class="isSeen(message) ? 'text-green-600' : 'text-gray-300'">
                                        <svg x-show="!isSeen(message)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        <svg x-show="isSeen(message)" class="w-4 h-3.5" viewBox="0 0 20 14" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M1 7l4 4L13 3"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 7l4 4L19 3"/></svg>
                                        <span x-text="isSeen(message) ? 'Seen' : 'Sent'"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <form @submit.prevent="send" class="px-6 py-4 border-t border-gray-100 flex items-end gap-3 bg-white">
        <textarea
            x-ref="input"
            x-model="draft"
            @keydown.enter.prevent="if (!$event.shiftKey) send()"
            rows="2"
            class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 resize-none"
            placeholder="Type a message..."
            :disabled="sending"
        ></textarea>
        <button type="submit"
            class="shrink-0 px-4 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm transition disabled:opacity-50"
            style="background-color:#1a6b3c;" onmouseover="this.style.backgroundColor='#145530'" onmouseout="this.style.backgroundColor='#1a6b3c'"
            :disabled="sending || draft.trim() === ''">
            <span x-show="!sending">Send</span>
            <span x-show="sending">Sending…</span>
        </button>
    </form>
</div>

<script>
    function supportChat({ pollUrl, sendUrl, currentUserId, latestId, otherPartyLastReadAt, initialMessages }) {
        return {
            messages: initialMessages,
            latestId: latestId,
            otherPartyLastReadAt: otherPartyLastReadAt,
            draft: '',
            sending: false,
            pollTimer: null,

            init() {
                this.scrollToBottom();
                this.pollTimer = setInterval(() => this.poll(), 3000);
                window.addEventListener('beforeunload', () => clearInterval(this.pollTimer));
            },

            formatTime(iso) {
                return new Date(iso).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            },

            isSeen(message) {
                if (!this.otherPartyLastReadAt) return false;
                return new Date(message.created_at) <= new Date(this.otherPartyLastReadAt);
            },

            groupedMessages() {
                const groups = [];
                for (const message of this.messages) {
                    const last = groups[groups.length - 1];
                    if (last && last.sender_id === message.sender_id) {
                        last.messages.push(message);
                    } else {
                        groups.push({ sender_id: message.sender_id, sender_name: message.sender_name, is_mine: message.is_mine, messages: [message] });
                    }
                }
                return groups;
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const el = this.$refs.scrollArea;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            },

            async poll() {
                try {
                    const res = await fetch(`${pollUrl}?since_id=${this.latestId}`, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();

                    if (data.messages && data.messages.length > 0) {
                        const existingIds = new Set(this.messages.map(m => m.id));
                        let scrolled = false;
                        data.messages.forEach(m => {
                            if (!existingIds.has(m.id)) { this.messages.push(m); scrolled = true; }
                        });
                        if (scrolled) this.scrollToBottom();
                    }
                    if (data.other_party_last_read_at) this.otherPartyLastReadAt = data.other_party_last_read_at;
                    this.latestId = data.latest_id ?? this.latestId;
                } catch (e) { /* retry next interval */ }
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
                        this.messages.push(data.message);
                        this.latestId = Math.max(this.latestId, data.message.id);
                        this.draft = '';
                        this.scrollToBottom();
                    }
                } catch (e) { /* leave draft in place */ }
                finally { this.sending = false; this.$refs.input?.focus(); }
            },
        };
    }
</script>
