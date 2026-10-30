{{-- "What's new" pop-up: shown once, right after someone opens the system. --}}
@php
    $annPayload = \App\Models\Announcement::popupsFor(auth()->user())
        ->map(fn ($a) => $a->popupPayload())->values()->all();
@endphp

{{-- Always on the page so a live announcement can open it; stays hidden until there is something to show. --}}
<div x-data="annPopup(@js($annPayload), @js(route('announcements.read')))" x-init="init()" x-cloak x-show="open"
         @ann-push.window="push($event.detail)"
         class="ann-modal" role="dialog" aria-modal="true" aria-labelledby="ann-popup-title"
         @keydown.escape.window="close()">
        <div class="ann-dialog" :style="'--fg:' + cur.fg + ';--bg:' + cur.bg">
            <div class="ann-dialog__hero">
                <div class="ann-dialog__eyebrow">
                    <span x-text="cur.eyebrow"></span>
                    <button type="button" class="ann-x" @click="close()" aria-label="Close">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="ann-dialog__row">
                    <span class="ann-ico" :style="'--fg:' + cur.fg + ';--bg:' + cur.bg">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="cur.icon"/></svg>
                    </span>
                    <div style="min-width:0">
                        <span class="ann-chip" :style="'--fg:' + cur.fg + ';--bg:#fff;box-shadow:inset 0 0 0 1px ' + cur.fg + '33'" x-text="cur.label"></span>
                        <span class="ann-chip ann-chip--plain" x-show="cur.pinned" x-cloak>Pinned</span>
                        <h2 id="ann-popup-title" class="ann-dialog__title" x-text="cur.title"></h2>
                        <p class="ann-dialog__meta"><span x-text="cur.date"></span><template x-if="cur.by"><span> &middot; Posted by <span x-text="cur.by"></span></span></template></p>
                    </div>
                </div>
            </div>

            <div class="ann-dialog__scroll" x-ref="scroll">
                <div class="ann-body" :style="'--fg:' + cur.fg" x-html="cur.html"></div>
            </div>

            <div class="ann-dialog__foot">
                <div class="ann-dots" x-show="items.length > 1">
                    <template x-for="(a, n) in items" :key="a.id"><span class="ann-dot" :class="n === i ? 'is-on' : ''"></span></template>
                    <span style="margin-left:.4rem" x-text="(i + 1) + ' of ' + items.length"></span>
                </div>
                <a href="{{ route('announcements.index') }}" class="ann-link" @click="close()" x-show="items.length === 1">View all announcements</a>
                <button type="button" class="btn btn-secondary" x-show="i > 0" @click="prev()">Back</button>
                <button type="button" class="btn btn-primary" x-ref="primary" @click="next()" x-text="last ? 'Got it' : 'Next'"></button>
            </div>
        </div>
    </div>

    <script>
        function annPopup(items, readUrl) {
            // One announcement + its version: an edit / "show it again" is a new key, so it can pop up again.
            const keyOf = a => a.id + ':' + (a.v || 0);
            const seenKeys = () => { try { return JSON.parse(sessionStorage.getItem('annSeen') || '[]'); } catch (e) { return []; } };

            return {
                items: items, i: 0, open: false,
                init() {
                    // Same tab, same visit: never show one the person already closed.
                    const seen = seenKeys();
                    this.items = this.items.filter(a => !seen.includes(keyOf(a)));
                    if (this.items.length) setTimeout(() => this.show(false), 400);
                },
                show(live) {
                    this.open = true;
                    document.body.classList.add('overflow-y-hidden');
                    this.$nextTick(() => {
                        // A live pop-up must not grab the keyboard from someone who is mid-sentence.
                        const el = document.activeElement;
                        const typing = live && el && (/^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName) || el.isContentEditable);
                        if (! typing && this.$refs.primary) this.$refs.primary.focus();
                    });
                },
                /** Called by the live poll (via the "ann-push" event) with unread pop-ups. */
                push(list) {
                    const seen = seenKeys();
                    const fresh = [];
                    (list || []).forEach(a => {
                        if (seen.includes(keyOf(a))) return;
                        const idx = this.open ? this.items.findIndex(x => x.id === a.id) : -1;
                        if (idx > -1) {                       // already on screen: refresh it if it was edited
                            if (keyOf(this.items[idx]) !== keyOf(a)) this.items.splice(idx, 1, a);
                            return;
                        }
                        fresh.push(a);
                    });
                    if (! fresh.length) return;
                    if (this.open) { this.items = this.items.concat(fresh); return; }  // join the ones being read
                    this.items = fresh; this.i = 0; this.show(true);
                },
                get cur() { return this.items[this.i] || {}; },
                get last() { return this.i >= this.items.length - 1; },
                next() { if (this.last) { this.close(); return; } this.i++; if (this.$refs.scroll) this.$refs.scroll.scrollTop = 0; },
                prev() { if (this.i > 0) { this.i--; if (this.$refs.scroll) this.$refs.scroll.scrollTop = 0; } },
                close() {
                    if (! this.open) return;
                    this.open = false;
                    document.body.classList.remove('overflow-y-hidden');
                    const ids = this.items.map(a => a.id);
                    try { sessionStorage.setItem('annSeen', JSON.stringify(seenKeys().concat(this.items.map(keyOf)))); } catch (e) {}
                    const token = document.querySelector('meta[name="csrf-token"]');
                    fetch(readUrl, {
                        method: 'POST', keepalive: true, credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json', 'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token ? token.content : '',
                        },
                        body: JSON.stringify({ ids: ids }),
                    }).catch(() => {});
                },
            };
        }
    </script>
