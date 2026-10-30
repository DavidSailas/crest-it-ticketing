@php
    $editing = $announcement->exists;
    $submitted = old('title') !== null;
    $v = fn (string $key, $default = '') => old($key, $announcement->{$key} ?? $default);
    $flag = fn (string $key) => $submitted ? (bool) old($key) : (bool) $announcement->{$key};
    $dt = fn (string $key) => old($key, $announcement->{$key}?->format('Y-m-d\TH:i'));
    $cats = \App\Models\Announcement::CATEGORIES;
    $audiences = \App\Models\Announcement::AUDIENCES;
    $formState = [
        'title' => $v('title'),
        'body' => $v('body'),
        'category' => $v('category', 'feature'),
        'cats' => $cats,
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editing ? 'Edit announcement' : 'New announcement' }}</h2>
            <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary btn-sm">&larr; All announcements</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" x-data="annForm(@js($formState))"
              action="{{ $editing ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}"
              class="ann-form-grid">
            @csrf
            @if($editing) @method('PUT') @endif

            {{-- ── Left: the message ───────────────────────────── --}}
            <div class="ann-panel">
                <section class="ann-section">
                    <div class="ann-section__head">
                        <span class="ann-section__n">1</span>
                        <div><div class="ann-section__t">Write the update</div><div class="ann-section__s">What do people need to know?</div></div>
                    </div>

                    <div class="ann-field">
                        <label class="ann-label" for="ann-title">Title</label>
                        <input id="ann-title" name="title" type="text" maxlength="150" required x-model="title"
                               class="ann-input" placeholder="e.g. You can now track your tickets in real time">
                        @error('title')<p class="ann-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="ann-field">
                        <span class="ann-label">Type</span>
                        <div class="ann-cats" role="radiogroup">
                            @foreach($cats as $key => $c)
                                <label class="ann-cat" style="--fg:{{ $c['fg'] }};--bg:{{ $c['bg'] }}" :class="category === '{{ $key }}' ? 'is-on' : ''">
                                    <input type="radio" name="category" value="{{ $key }}" class="ann-sr" x-model="category">
                                    <span class="ann-ico" style="--fg:{{ $c['fg'] }};--bg:{{ $c['bg'] }}"><x-announcement-icon :path="$c['icon']" /></span>
                                    {{ $c['label'] }}
                                </label>
                            @endforeach
                        </div>
                        @error('category')<p class="ann-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="ann-field">
                        <label class="ann-label" for="ann-body">Message</label>
                        <textarea id="ann-body" name="body" required maxlength="5000" x-model="body" class="ann-input"
                                  placeholder="What changed, why it helps, and what people need to do (if anything)."></textarea>
                        <div style="display:flex;justify-content:space-between;gap:1rem">
                            <p class="ann-hint">Blank line = new paragraph &middot; start a line with <code>-</code> for a bullet &middot; <code>**bold**</code> for emphasis.</p>
                            <p class="ann-hint" style="white-space:nowrap" x-text="body.length + ' / 5000'"></p>
                        </div>
                        @error('body')<p class="ann-err">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="ann-section">
                    <div class="ann-section__head">
                        <span class="ann-section__n">2</span>
                        <div><div class="ann-section__t">Choose the audience</div><div class="ann-section__s">Who sees it, and how.</div></div>
                    </div>

                    <div class="ann-field">
                        <label class="ann-label" for="ann-audience">Who should see it?</label>
                        <select id="ann-audience" name="audience" class="ann-input">
                            @foreach($audiences as $key => $label)
                                <option value="{{ $key }}" @selected($v('audience', 'all') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('audience')<p class="ann-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="ann-field">
                        <label class="ann-check">
                            <input type="checkbox" name="is_popup" value="1" @checked($flag('is_popup'))>
                            <div><b>Show as a pop-up when people open the system</b><span>Appears once per person, then stays in their Announcements feed.</span></div>
                        </label>
                        <label class="ann-check">
                            <input type="checkbox" name="is_pinned" value="1" @checked($flag('is_pinned'))>
                            <div><b>Pin to the top</b><span>Keeps it first on the dashboard and the feed, even after it is no longer new.</span></div>
                        </label>
                        @if($editing)
                            <label class="ann-check">
                                <input type="checkbox" name="resend" value="1">
                                <div><b>Show it again to people who already saw it</b><span>Use this after a big correction. Their "seen" status is reset.</span></div>
                            </label>
                        @endif
                    </div>
                </section>

                <section class="ann-section">
                    <div class="ann-section__head">
                        <span class="ann-section__n">3</span>
                        <div><div class="ann-section__t">Schedule <span class="ann-muted" style="font-weight:400">(optional)</span></div><div class="ann-section__s">Great for maintenance windows.</div></div>
                    </div>

                    <div class="ann-two">
                        <div>
                            <label class="ann-label" for="ann-start">Show from</label>
                            <input id="ann-start" type="datetime-local" name="starts_at" value="{{ $dt('starts_at') }}" class="ann-input">
                            @error('starts_at')<p class="ann-err">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="ann-label" for="ann-end">Hide after</label>
                            <input id="ann-end" type="datetime-local" name="ends_at" value="{{ $dt('ends_at') }}" class="ann-input">
                            @error('ends_at')<p class="ann-err">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <p class="ann-hint">Leave both empty to publish right away and keep it until you remove it.</p>
                </section>

                <div class="ann-actions">
                    <button type="submit" name="action" value="publish" class="btn btn-primary">{{ $editing && $announcement->is_published ? 'Save changes' : 'Publish now' }}</button>
                    <button type="submit" name="action" value="draft" class="btn btn-secondary">{{ $editing && ! $announcement->is_published ? 'Save draft' : 'Save as draft' }}</button>
                    <a href="{{ route('admin.announcements.index') }}" class="ann-cancel">Cancel</a>
                </div>
            </div>

            {{-- ── Right: live preview ─────────────────────────── --}}
            <aside class="ann-sticky" aria-label="Preview">
                <p class="ann-preview-label">Live preview</p>
                <div class="ann-preview" :style="'--fg:' + cat.fg + ';--bg:' + cat.bg">
                    <div class="ann-preview__hero">
                        <div class="ann-preview__row">
                            <span class="ann-ico" :style="'--fg:' + cat.fg + ';--bg:' + cat.bg">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="cat.icon"/></svg>
                            </span>
                            <div style="min-width:0">
                                <span class="ann-chip" :style="'--fg:' + cat.fg + ';--bg:#fff;box-shadow:inset 0 0 0 1px ' + cat.fg + '33'" x-text="cat.label"></span>
                                <h3 class="ann-preview__title" x-text="title || 'Your title appears here'"></h3>
                            </div>
                        </div>
                    </div>
                    <div class="ann-preview__body"><div class="ann-body" :style="'--fg:' + cat.fg" x-html="rendered()"></div></div>
                </div>
                <div class="ann-tips">
                    <b>Writing tips</b><br>
                    Lead with what changed, then why it matters to them. Keep it short, and say clearly if they need to do something.
                </div>
            </aside>
        </form>
    </div>

    <script>
        function annForm(state) {
            return {
                title: state.title, body: state.body, category: state.category, cats: state.cats,
                get cat() { return this.cats[this.category] || this.cats.update; },
                rendered() {
                    const esc = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    const inline = t => esc(t)
                        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                        .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
                    let html = '', list = [], para = [];
                    const flushList = () => { if (list.length) { html += '<ul>' + list.map(i => '<li>' + i + '</li>').join('') + '</ul>'; list = []; } };
                    const flushPara = () => { if (para.length) { html += '<p>' + para.join('<br>') + '</p>'; para = []; } };
                    (this.body || '').split(/\r?\n/).forEach(raw => {
                        const line = raw.trim();
                        if (!line) { flushList(); flushPara(); return; }
                        const m = line.match(/^[-•]\s+(.+)$/);
                        if (m) { flushPara(); list.push(inline(m[1])); return; }
                        flushList(); para.push(inline(line));
                    });
                    flushList(); flushPara();
                    return html || '<p class="ann-muted">Your message appears here as you type.</p>';
                },
            };
        }
    </script>
</x-app-layout>
