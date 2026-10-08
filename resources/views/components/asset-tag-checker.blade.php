@props(['ignore' => null, 'currentTag' => null])

{{--
    Live asset-tag check shown under the "Tag Number" field. It watches the company / location /
    department / type / number inputs of the surrounding <form>, asks the server (debounced) whether
    the resulting tag is free, and explains the result in plain language: available, already used
    (and by whom), or "leave blank and we'll pick the next free one". The server still re-checks on
    save, so this is purely a convenience.
--}}
<div x-data="assetTagChecker({ url: @js(route('assets.tag-check')), ignore: @js($ignore), currentTag: @js($currentTag) })"
     class="mt-3" aria-live="polite">

    {{-- Not enough info yet --}}
    <div x-show="!data && !failed" x-cloak class="flex items-center gap-2 text-xs text-gray-400">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>
        <span>Choose company, location, department and type to preview the tag.</span>
    </div>

    <template x-if="data">
        <div>
            {{-- Department has no asset code --}}
            <div x-show="data.problem" x-cloak class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm text-red-700">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                <span x-text="data.problem"></span>
            </div>

            {{-- Number isn't valid --}}
            <div x-show="data.invalid" x-cloak class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm text-red-700">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                <span x-text="data.invalid"></span>
            </div>

            {{-- Number left blank: we'll pick the next free one --}}
            <div x-show="data.ready && !data.tag && !data.invalid" x-cloak class="rounded-lg border border-gray-200 bg-gray-50 px-3.5 py-2.5">
                <template x-if="data.next">
                    <p class="text-sm text-gray-600">
                        The tag will be generated automatically:
                        <span class="ml-1 inline-block rounded-md px-2 py-0.5 font-mono text-xs font-semibold" style="background:#ecf7f0;color:#14532d;box-shadow:inset 0 0 0 1px #bfe3cc;" x-text="data.next.tag"></span>
                    </p>
                </template>
                <template x-if="!data.next">
                    <p class="text-sm text-red-700">Every number from 001 to 999 is used for this combination — choose another department or type.</p>
                </template>
            </div>

            {{-- Free --}}
            <div x-show="data.tag && !data.taken && !isCurrent" x-cloak class="flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-3.5 py-2.5">
                <svg class="w-4 h-4 mt-0.5 shrink-0" style="color:#1a6b3c;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="text-sm text-green-800"><span class="font-mono font-semibold" x-text="data.tag"></span> is available.</p>
            </div>

            {{-- Unchanged (edit page) --}}
            <div x-show="isCurrent" x-cloak class="flex items-start gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3.5 py-2.5">
                <svg class="w-4 h-4 mt-0.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                <p class="text-sm text-gray-600">This is the asset's current tag (<span class="font-mono font-semibold" x-text="data.tag"></span>) — no change.</p>
            </div>

            {{-- Already used --}}
            <div x-show="data.taken" x-cloak class="rounded-lg border border-red-200 bg-red-50 px-3.5 py-3">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-red-800"><span class="font-mono" x-text="data.tag"></span> is already in use</p>
                        <template x-if="data.existing">
                            <p class="mt-0.5 text-sm text-red-700">
                                Used by <span class="font-medium" x-text="'“' + data.existing.device_name + '”'"></span>
                                &middot; <span x-text="data.existing.owner ? data.existing.owner : 'Unassigned'"></span>
                                &middot; <span x-text="data.existing.status"></span>.
                                <a :href="data.existing.url" target="_blank" rel="noopener" class="font-medium underline hover:no-underline">View asset</a>
                            </p>
                        </template>
                        <div class="mt-2.5 flex flex-wrap items-center gap-2">
                            <template x-if="data.next">
                                <button type="button" @click="useNext()"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-white shadow-sm"
                                        style="background-color:#1a6b3c;">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                                    Use next free number (<span x-text="pad(data.next.sequence)"></span>)
                                </button>
                            </template>
                            <span class="text-xs text-red-700/80">or type a different number.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Couldn't reach the checker (the server still validates on save) --}}
    <p x-show="failed" x-cloak class="text-xs text-gray-400">Couldn't check the tag right now — it will be verified when you save.</p>

    <p x-show="loading" x-cloak class="mt-1.5 flex items-center gap-1.5 text-xs text-gray-400">
        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
        Checking…
    </p>
</div>

@once
<script>
    window.assetTagChecker = function (cfg) {
        const fields = ['company', 'location', 'department_id', 'type', 'sequence'];
        return {
            data: null, failed: false, loading: false, timer: null, ctrl: null, form: null, input: null,

            init() {
                this.form = this.$el.closest('form');
                if (!this.form) return;
                this.input = this.form.querySelector('[name="sequence"]');
                fields.forEach(n => {
                    const el = this.form.querySelector('[name="' + n + '"]');
                    if (!el) return;
                    el.addEventListener('input', () => this.queue());
                    el.addEventListener('change', () => this.queue());
                });
                // Don't submit a tag we already know is taken (the server checks again anyway).
                this.form.addEventListener('submit', (e) => {
                    if (this.taken && this.input) {
                        e.preventDefault();
                        this.input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        this.input.focus();
                    }
                });
                this.check();
            },

            val(name) {
                const el = this.form.querySelector('[name="' + name + '"]');
                return el ? el.value.trim() : '';
            },

            queue() {
                clearTimeout(this.timer);
                this.loading = true;
                this.timer = setTimeout(() => this.check(), 300);
            },

            async check() {
                const params = new URLSearchParams();
                fields.forEach(n => params.set(n, this.val(n)));
                if (cfg.ignore) params.set('ignore', cfg.ignore);

                if (!params.get('company') || !params.get('location') || !params.get('type') || !params.get('department_id')) {
                    this.data = null; this.loading = false; this.paint();
                    return;
                }

                if (this.ctrl) this.ctrl.abort();
                this.ctrl = new AbortController();
                try {
                    const res = await fetch(cfg.url + '?' + params.toString(), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        signal: this.ctrl.signal,
                    });
                    if (!res.ok) throw new Error('bad response');
                    this.data = await res.json();
                    this.failed = false;
                } catch (e) {
                    if (e.name === 'AbortError') return;
                    this.data = null; this.failed = true;
                }
                this.loading = false;
                this.paint();
            },

            get taken() { return !!(this.data && this.data.taken); },
            get isCurrent() { return !!(this.data && this.data.tag && cfg.currentTag && this.data.tag === cfg.currentTag); },

            pad(n) { return String(n).padStart(3, '0'); },

            useNext() {
                if (!this.data || !this.data.next || !this.input) return;
                this.input.value = this.data.next.sequence;
                this.input.dispatchEvent(new Event('input', { bubbles: true }));
            },

            // Red outline on the number field while the tag is taken.
            paint() {
                if (!this.input) return;
                this.input.style.borderColor = this.taken ? '#dc2626' : '';
                this.input.style.boxShadow = this.taken ? '0 0 0 3px rgba(220,38,38,.15)' : '';
            },
        };
    };
</script>
@endonce
