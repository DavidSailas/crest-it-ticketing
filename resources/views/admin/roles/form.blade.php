@php
    $title = $readonly ? $role->name.' — full access' : ($editing ? 'Edit '.$role->name : 'Create a role');

    // What the page script needs: groups with their items, the colours, and how permissions depend on each other.
    $groupData = collect($groups)->map(fn ($g, $key) => [
        'key' => $key,
        'label' => $g['label'],
        'items' => collect($g['items'])->map(fn ($i, $k) => ['key' => $k, 'text' => strtolower($i[0].' '.$i[1])])->values()->all(),
    ])->values()->all();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <a href="{{ route('admin.roles.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; All roles</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mt-1">{{ $title }}</h2>
            </div>
        </div>
    </x-slot>

    <style>
        .rf-layout { display: grid; gap: 1.5rem; grid-template-columns: 1fr; align-items: start; }
        @media (min-width: 1024px) { .rf-layout { grid-template-columns: minmax(0, 1fr) 20rem; } .rf-side { position: sticky; top: 1rem; } }
        .rf-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .85rem; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
        .rf-pad { padding: 1.1rem 1.25rem; }
        .rf-group { margin-bottom: 1rem; }
        .rf-ghead { display: flex; align-items: center; gap: .75rem; padding: .85rem 1.25rem; border-bottom: 1px solid #f3f4f6; }
        .rf-ghead h3 { margin: 0; font-size: .95rem; font-weight: 600; color: #111827; }
        .rf-ghead p { margin: .1rem 0 0; font-size: .75rem; color: #9ca3af; }
        .rf-count { margin-left: auto; font-size: .75rem; font-weight: 600; color: #4b5563; background: #f3f4f6; border-radius: 9999px; padding: .1rem .55rem; white-space: nowrap; }
        .rf-all { background: none; border: 0; font-size: .75rem; font-weight: 600; color: #1a6b3c; cursor: pointer; padding: 0; }
        .rf-all:hover { text-decoration: underline; }
        .rf-all:disabled { color: #9ca3af; cursor: default; text-decoration: none; }
        .rf-row { display: flex; align-items: flex-start; gap: .9rem; padding: .8rem 1.25rem; border-bottom: 1px solid #f9fafb; cursor: pointer; }
        .rf-row:last-child { border-bottom: 0; }
        .rf-row:hover { background: #fafafa; }
        .rf-row b { display: block; font-size: .9rem; font-weight: 600; color: #1f2937; }
        .rf-row span.rf-d { display: block; font-size: .78rem; color: #6b7280; line-height: 1.4; margin-top: .1rem; }
        /* toggle switch */
        .rf-switch { position: relative; flex: none; width: 2.3rem; height: 1.3rem; margin-top: .1rem; }
        .rf-switch input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; margin: 0; cursor: pointer; z-index: 2; }
        .rf-switch i { position: absolute; inset: 0; background: #d1d5db; border-radius: 9999px; transition: background .15s; }
        .rf-switch i::after { content: ''; position: absolute; top: .15rem; left: .15rem; width: 1rem; height: 1rem; border-radius: 9999px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.3); transition: transform .15s; }
        .rf-switch input:checked + i { background: #1a6b3c; }
        .rf-switch input:checked + i::after { transform: translateX(1rem); }
        .rf-switch input:focus-visible + i { outline: 2px solid #1a6b3c; outline-offset: 2px; }
        .rf-switch input:disabled { cursor: not-allowed; }
        .rf-switch input:disabled + i { opacity: .6; }
        .rf-label { display: block; margin: 0 0 .3rem; font-size: .8rem; font-weight: 600; color: #374151; }
        .rf-input { display: block; width: 100%; border: 1px solid #d1d5db; border-radius: .5rem; font-size: .875rem; padding: .5rem .75rem; background: #fff; }
        .rf-input:focus { outline: none; border-color: #1a6b3c; box-shadow: 0 0 0 1px #1a6b3c; }
        .rf-input:disabled { background: #f3f4f6; color: #6b7280; }
        .rf-swatches { display: flex; flex-wrap: wrap; gap: .5rem; }
        .rf-sw { position: relative; width: 1.9rem; height: 1.9rem; border-radius: 9999px; border: 2px solid transparent; cursor: pointer; }
        .rf-sw input { position: absolute; inset: 0; opacity: 0; margin: 0; cursor: pointer; width: 100%; height: 100%; }
        .rf-sw.is-on { border-color: #111827; box-shadow: 0 0 0 2px #fff inset; }
        .rf-sw:focus-within { outline: 2px solid #1a6b3c; outline-offset: 2px; }
        .rf-badge { display: inline-flex; align-items: center; border-radius: 9999px; padding: .2rem .75rem; font-size: .8rem; font-weight: 600; }
        .rf-bar { height: .4rem; background: #e5e7eb; border-radius: 9999px; overflow: hidden; }
        .rf-bar > div { height: 100%; background: #1a6b3c; transition: width .2s; }
        .rf-note { font-size: .78rem; color: #4b5563; background: #f0f9f3; border: 1px solid #d4ecdc; border-radius: .5rem; padding: .5rem .65rem; margin: .5rem 0 0; }
        .rf-locked { padding: .8rem 1.25rem; display: flex; gap: .75rem; align-items: flex-start; font-size: .85rem; color: #6b7280; border-bottom: 1px solid #f9fafb; }
        .rf-locked:last-child { border-bottom: 0; }
        .rf-locked b { display: block; color: #374151; font-weight: 600; }
        .rf-lock-ico { flex: none; width: 1.3rem; text-align: center; color: #9ca3af; }
        .rf-link { background: none; border: 0; padding: 0; font-size: .8rem; font-weight: 500; color: #6b7280; cursor: pointer; text-decoration: underline; }
        .rf-link:hover { color: #111827; }
        .rf-link--danger:hover { color: #b91c1c; }
        .rf-empty { display: none; text-align: center; color: #9ca3af; font-size: .9rem; padding: 2rem 0; }
    </style>

    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4"
         x-data="rolePerms(@js([
            'granted' => $granted,
            'implies' => $implies,
            'presets' => $presets,
            'groups' => $groupData,
            'colors' => $colors,
            'color' => old('color', $role->color ?? 'sky'),
            'name' => old('name', $role->name),
            'total' => count(\App\Support\Permissions::grantable()),
            'readonly' => $readonly,
         ]))">

        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100" role="status">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-800 text-sm rounded-lg border border-red-100" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="p-3 bg-red-50 text-red-800 text-sm rounded-lg border border-red-100" role="alert">
                <p class="font-semibold">The role was not saved:</p>
                <ul class="mt-1 list-disc pl-5 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @if($readonly)
            <div class="p-3 bg-amber-50 text-amber-800 text-sm rounded-lg border border-amber-100">
                The <b>Administrator</b> role always has full access so there is always someone who can manage the system. It cannot be changed.
            </div>
        @endif

        <form method="POST" action="{{ $editing ? route('admin.roles.update', $role) : route('admin.roles.store') }}" novalidate>
            @csrf
            @if($editing) @method('PUT') @endif

            <div class="rf-layout">

                {{-- ============================ PERMISSIONS ============================ --}}
                <div>
                    @unless($readonly)
                        <div style="margin-bottom:1rem">
                            <label class="rf-label" for="rf-search">Find a permission</label>
                            <input id="rf-search" type="search" class="rf-input" placeholder="e.g. export, assets, tickets…" x-model="q" autocomplete="off">
                        </div>
                    @endunless

                    @foreach($groups as $gKey => $group)
                        <section class="rf-card rf-group" x-show="groupVisible('{{ $gKey }}')" aria-labelledby="g-{{ $gKey }}">
                            <header class="rf-ghead">
                                <div>
                                    <h3 id="g-{{ $gKey }}">{{ $group['label'] }}</h3>
                                    <p>{{ $group['description'] }}</p>
                                </div>
                                <span class="rf-count" x-text="groupCount('{{ $gKey }}') + ' of {{ count($group['items']) }}'"></span>
                                @unless($readonly)
                                    <button type="button" class="rf-all" @click="setGroup('{{ $gKey }}', !allOn('{{ $gKey }}'))" x-text="allOn('{{ $gKey }}') ? 'Clear' : 'Select all'"></button>
                                @endunless
                            </header>

                            @foreach($group['items'] as $key => [$label, $desc])
                                <label class="rf-row" x-show="match(@js(strtolower($label.' '.$desc)))">
                                    <span class="rf-switch">
                                        <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                               :checked="has('{{ $key }}')" @change="toggle('{{ $key }}')" @disabled($readonly)>
                                        <i></i>
                                    </span>
                                    <span>
                                        <b>{{ $label }}</b>
                                        <span class="rf-d">{{ $desc }}</span>
                                        @if(in_array($key, \App\Support\Permissions::NOT_FOR_ADMIN, true) && $readonly)
                                            <span class="rf-d" style="color:#9ca3af">Not applicable to administrators.</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </section>
                    @endforeach

                    <p class="rf-empty" x-show="q.trim() !== '' && visibleCount() === 0" style="display:none" :style="(q.trim() !== '' && visibleCount() === 0) ? 'display:block' : ''">No permission matches &ldquo;<span x-text="q"></span>&rdquo;.</p>

                    <section class="rf-card" x-show="q.trim() === ''">
                        <header class="rf-ghead">
                            <div>
                                <h3>Always Administrator-only</h3>
                                <p>These stay with Administrators so nobody can give themselves more power.</p>
                            </div>
                        </header>
                        @foreach($locked as $key => [$label, $desc])
                            <div class="rf-locked">
                                <span class="rf-lock-ico" aria-hidden="true">&#128274;</span>
                                <span><b>{{ $label }}</b>{{ $desc }}</span>
                            </div>
                        @endforeach
                    </section>
                </div>

                {{-- ============================ DETAILS ============================ --}}
                <aside class="rf-side">
                    <div class="rf-card rf-pad" style="display:grid;gap:1rem">
                        <div>
                            <span class="rf-badge" :style="`color:${fg};background:${bg}`" x-text="name.trim() || 'New role'"></span>
                        </div>

                        <div>
                            <label class="rf-label" for="rf-name">Role name</label>
                            <input id="rf-name" name="name" type="text" maxlength="60" class="rf-input" x-model="name"
                                   value="{{ old('name', $role->name) }}" placeholder="e.g. Head" @disabled($readonly || ($role->is_system ?? false)) @if(! $readonly && ! ($role->is_system ?? false)) required @endif>
                            @if($role->is_system ?? false)<p style="margin:.3rem 0 0;font-size:.72rem;color:#9ca3af">Built-in roles keep their name.</p>@endif
                        </div>

                        <div>
                            <label class="rf-label" for="rf-desc">Description <span style="font-weight:400;color:#9ca3af">(optional)</span></label>
                            <textarea id="rf-desc" name="description" rows="2" maxlength="255" class="rf-input" @disabled($readonly)
                                      placeholder="Who is this role for?">{{ old('description', $role->description) }}</textarea>
                        </div>

                        <div>
                            <span class="rf-label">Colour</span>
                            <div class="rf-swatches" role="radiogroup" aria-label="Role colour">
                                @foreach($colors as $cKey => [$cfg, $cbg])
                                    <label class="rf-sw" :class="{ 'is-on': color === '{{ $cKey }}' }" style="background:{{ $cfg }}" title="{{ ucfirst($cKey) }}">
                                        <input type="radio" name="color" value="{{ $cKey }}" x-model="color" @disabled($readonly)>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        @unless($editing)
                            <div>
                                <label class="rf-label" for="rf-from">Start from</label>
                                <select id="rf-from" class="rf-input" @change="copy($event.target.value)">
                                    <option value="">Nothing ticked</option>
                                    @foreach($roles as $r)
                                        <option value="{{ $r->key }}" @selected($r->key === 'staff')>Same as {{ $r->name }}</option>
                                    @endforeach
                                </select>
                                <p style="margin:.3rem 0 0;font-size:.72rem;color:#9ca3af">Copies that role's permissions, then you adjust them.</p>
                            </div>
                        @endunless

                        <div style="border-top:1px solid #f3f4f6;padding-top:.9rem">
                            <div style="display:flex;justify-content:space-between;font-size:.8rem;color:#4b5563;margin-bottom:.4rem">
                                <span>Permissions on</span>
                                <b><span x-text="count"></span> of {{ count(\App\Support\Permissions::grantable()) }}</b>
                            </div>
                            <div class="rf-bar"><div :style="`width:${Math.round(count / total * 100)}%`"></div></div>
                            <template x-if="has('tickets.work')"><p class="rf-note">Works the ticket queue and gets the IT Support dashboard, Support Inbox and ticket tools.</p></template>
                            <template x-if="!has('tickets.work') && has('tickets.view_all')"><p class="rf-note">Can see every ticket, read-only.</p></template>
                            <template x-if="!has('tickets.work') && !has('tickets.view_all')"><p class="rf-note">Raises tickets and sees only their own.</p></template>
                        </div>

                        @unless($readonly)
                            <div style="display:grid;gap:.5rem">
                                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">{{ $editing ? 'Save changes' : 'Create role' }}</button>
                                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary" style="width:100%;justify-content:center">Cancel</a>
                            </div>
                        @endunless
                    </div>

                    @if($editing && ! $readonly)
                        <div style="margin-top:.9rem;display:flex;flex-direction:column;gap:.4rem;align-items:center">
                            <span style="font-size:.78rem;color:#9ca3af">{{ $people }} {{ $people === 1 ? 'person has' : 'people have' }} this role</span>
                        </div>
                    @endif
                </aside>
            </div>
        </form>

        {{-- Reset / delete live outside the main form (a form cannot sit inside a form). --}}
        @if($editing && ! $readonly)
            <div style="display:flex;flex-wrap:wrap;gap:1.25rem;justify-content:center;padding-top:.5rem">
                @if($defaults !== null)
                    <form method="POST" action="{{ route('admin.roles.reset', $role) }}"
                          onsubmit="return confirm('Put &quot;{{ $role->name }}&quot; back to its default permissions?');">
                        @csrf
                        <button type="submit" class="rf-link">Reset to default permissions</button>
                    </form>
                @endif
                @unless($role->is_system)
                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                          onsubmit="return confirm('Delete the role &quot;{{ $role->name }}&quot;? This cannot be undone.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="rf-link rf-link--danger" @disabled($people > 0)
                            title="{{ $people > 0 ? 'Move the people to another role first' : 'Delete this role' }}">Delete this role</button>
                    </form>
                @endunless
            </div>
        @endif
    </div>

    <script>
        function rolePerms(cfg) {
            return {
                perms: cfg.granted.slice(),
                implies: cfg.implies,
                presets: cfg.presets,
                groups: cfg.groups,
                colors: cfg.colors,
                color: cfg.color,
                name: cfg.name || '',
                total: cfg.total,
                q: '',

                get fg() { return (this.colors[this.color] || this.colors.slate)[0]; },
                get bg() { return (this.colors[this.color] || this.colors.slate)[1]; },
                get count() { return this.perms.filter(p => this.groups.some(g => g.items.some(i => i.key === p))).length; },

                has(k) { return this.perms.includes(k); },
                toggle(k) { this.has(k) ? this.remove(k) : this.add(k); },
                // ticking something also ticks what it needs (you cannot add assets you cannot see)
                add(k) {
                    if (!this.has(k)) this.perms.push(k);
                    (this.implies[k] || []).forEach(n => this.add(n));
                },
                // unticking something also unticks what depends on it
                remove(k) {
                    this.perms = this.perms.filter(p => p !== k);
                    Object.keys(this.implies).forEach(d => {
                        if ((this.implies[d] || []).includes(k) && this.has(d)) this.remove(d);
                    });
                },
                group(key) { return this.groups.find(g => g.key === key); },
                groupCount(key) { return this.group(key).items.filter(i => this.has(i.key)).length; },
                allOn(key) { const g = this.group(key); return this.groupCount(key) === g.items.length; },
                setGroup(key, on) { this.group(key).items.forEach(i => on ? this.add(i.key) : this.remove(i.key)); },
                copy(roleKey) { this.perms = roleKey ? (this.presets[roleKey] || []).slice() : []; },

                match(text) { const q = this.q.trim().toLowerCase(); return q === '' || text.includes(q); },
                groupVisible(key) { return this.group(key).items.some(i => this.match(i.text)); },
                visibleCount() { return this.groups.reduce((n, g) => n + g.items.filter(i => this.match(i.text)).length, 0); },
            };
        }
    </script>
</x-app-layout>
