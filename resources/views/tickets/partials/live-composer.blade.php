@if($ticket->isClosed())
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
    <form @submit.prevent="send" class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50/40">
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
