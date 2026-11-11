@if($ticket->isCancelled())
    <div class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50/60 flex items-center gap-2.5">
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
        <p class="text-sm text-gray-500">This ticket was cancelled, so commenting is disabled. Submit a new ticket if you still need help.</p>
    </div>
@elseif($ticket->isClosed())
    <div class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50/60 flex items-center gap-2.5">
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
        <p class="text-sm text-gray-500">This ticket is closed, so commenting is disabled. Submit a new ticket if you need further help.</p>
    </div>
@elseif((auth()->user()->isItSupport() || auth()->user()->isAdmin()) && !$ticket->assigned_to)
    <div class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-amber-50/60 flex items-center gap-2.5">
        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
        <p class="text-sm text-amber-800">This ticket isn't assigned yet — accept it above before commenting.</p>
    </div>
@else
    <form @submit.prevent="send" class="cm-composer px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50/40">
        <div class="flex gap-3">
            <div class="hidden sm:block shrink-0"><x-avatar :user="auth()->user()" size="md" /></div>
            <div class="flex-1">
                <textarea
                    x-ref="input"
                    x-model="draft"
                    {{-- Enter posts on a computer; on a phone keyboard Enter is a new line and the round button posts --}}
                    @keydown.enter="if (!$event.shiftKey && !$event.isComposing && !window.matchMedia('(pointer: coarse)').matches) { $event.preventDefault(); send() }"
                    @input="autosize($event.target)"
                    rows="1"
                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 resize-none bg-white"
                    placeholder="Write a comment..."
                    :disabled="sending"
                ></textarea>
                <div class="flex items-center justify-between mt-2">
                    <p class="text-xs text-gray-400 hidden sm:block"><kbd class="px-1.5 py-0.5 rounded border border-gray-200 bg-white font-sans">Enter</kbd> to post, <kbd class="px-1.5 py-0.5 rounded border border-gray-200 bg-white font-sans">Shift+Enter</kbd> for a new line</p>
                    <button type="submit" aria-label="Post comment"
                        {{-- keep focus in the box: otherwise the keyboard closes, the layout jumps, and the tap misses the button --}}
                        @mousedown.prevent
                        class="px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm transition disabled:opacity-50"
                        style="background-color:#1a6b3c;"
                        :disabled="sending || draft.trim() === ''">
                        <span x-show="!sending">Post Comment</span>
                        <span x-show="sending">Posting…</span>
                        {{-- phones: a round "send" button with just this icon (the words are hidden) --}}
                        <svg class="cm-send-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </form>
@endif
