<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Roles &amp; Permissions</h2>
                <p class="text-sm text-gray-500 mt-1">Decide who can do what in the system.</p>
            </div>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary" style="justify-content:center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Create a role
            </a>
        </div>
    </x-slot>

    <style>
        .rl-grid { display: grid; gap: 1rem; grid-template-columns: 1fr; }
        @media (min-width: 700px) { .rl-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1100px) { .rl-grid { grid-template-columns: repeat(3, 1fr); } }
        .rl-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .85rem; box-shadow: 0 1px 2px rgba(0,0,0,.04); padding: 1.1rem 1.2rem; display: flex; flex-direction: column; gap: .75rem; }
        .rl-badge { display: inline-flex; align-items: center; gap: .4rem; border-radius: 9999px; padding: .2rem .7rem; font-size: .8rem; font-weight: 600; white-space: nowrap; }
        .rl-chip { font-size: .68rem; font-weight: 600; letter-spacing: .03em; text-transform: uppercase; color: #6b7280; background: #f3f4f6; border-radius: .35rem; padding: .1rem .4rem; }
        .rl-desc { margin: 0; font-size: .84rem; color: #6b7280; line-height: 1.45; min-height: 2.4em; }
        .rl-stats { display: flex; gap: 1.2rem; font-size: .8rem; color: #4b5563; }
        .rl-stats b { color: #111827; font-size: 1rem; display: block; line-height: 1.2; }
        .rl-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: auto; align-items: center; }
        .rl-link { background: none; border: 0; padding: 0; font-size: .8rem; font-weight: 500; color: #6b7280; cursor: pointer; text-decoration: underline; }
        .rl-link:hover { color: #b91c1c; }
        .rl-link:disabled { opacity: .45; cursor: not-allowed; text-decoration: none; }

        .rl-matrix-wrap { overflow-x: auto; border-radius: .85rem; border: 1px solid #e5e7eb; background: #fff; }
        table.rl-matrix { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .85rem; }
        .rl-matrix th, .rl-matrix td { position: relative; padding: .55rem .8rem; border-bottom: 1px solid #f3f4f6; text-align: center; white-space: nowrap; }
        .rl-matrix th:first-child, .rl-matrix td:first-child { text-align: left; position: sticky; left: 0; background: #fff; min-width: 16rem; white-space: normal; z-index: 1; }
        .rl-matrix thead th { background: #f9fafb; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; }
        .rl-matrix thead th:first-child { z-index: 2; background: #f9fafb; }
        .rl-matrix tr.rl-grp td { background: #f9fafb; font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; text-align: left; }
        .rl-matrix .rl-sub { display: block; font-size: .74rem; color: #9ca3af; margin-top: .1rem; }
        .rl-yes { display: inline-flex; width: 1.35rem; height: 1.35rem; border-radius: 9999px; align-items: center; justify-content: center; background: #dcfce7; color: #166534; }
        .rl-no { color: #d1d5db; }
        .rl-yes svg { width: .85rem; height: .85rem; }
        .rl-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; }
    </style>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100" role="status">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-800 text-sm rounded-lg border border-red-100" role="alert">{{ session('error') }}</div>
        @endif

        <p class="text-sm text-gray-500 max-w-3xl">
            A <b>role</b> is a set of things a person is allowed to do. Create one, for example <b>Head</b>, tick what it may do, then give it to people from
            <a href="{{ route('admin.users.index') }}" class="underline">Manage Users</a>. Changes apply straight away.
        </p>

        {{-- ============================ ROLE CARDS ============================ --}}
        <section aria-label="Roles">
            <div class="rl-grid">
                @foreach($roles as $role)
                    @php
                        [$fg, $bg] = $role->colors();
                        $n = $people[$role->key] ?? 0;
                        $count = $role->isAdmin() ? 'All' : count($role->permissionKeys());
                    @endphp
                    <article class="rl-card">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem">
                            <span class="rl-badge" style="color:{{ $fg }};background:{{ $bg }}">{{ $role->name }}</span>
                            @if($role->is_system)<span class="rl-chip">Built-in</span>@endif
                        </div>

                        <p class="rl-desc">{{ $role->description ?: 'No description.' }}</p>

                        <div class="rl-stats">
                            <span><b>{{ $n }}</b>{{ $n === 1 ? 'person' : 'people' }}</span>
                            <span><b>{{ $count }}</b>permissions</span>
                        </div>

                        <div class="rl-actions">
                            @if($role->isAdmin())
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-secondary btn-sm">View access</a>
                                <span style="font-size:.75rem;color:#9ca3af">Always full access</span>
                            @else
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-secondary btn-sm">Edit permissions</a>
                                @unless($role->is_system)
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" style="margin-left:auto"
                                          onsubmit="return confirm('Delete the role &quot;{{ $role->name }}&quot;? This cannot be undone.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="rl-link" @disabled($n > 0)
                                            title="{{ $n > 0 ? 'Move the '.$n.' '.($n === 1 ? 'person' : 'people').' to another role first' : 'Delete this role' }}">Delete</button>
                                    </form>
                                @endunless
                            @endif
                        </div>
                    </article>
                @endforeach

                {{-- a clear "add" tile --}}
                <a href="{{ route('admin.roles.create') }}" class="rl-card" style="border-style:dashed;align-items:center;justify-content:center;text-align:center;color:#6b7280;text-decoration:none;min-height:11rem">
                    <span style="font-size:1.6rem;line-height:1;color:#1a6b3c">+</span>
                    <span style="font-weight:600;color:#374151">Create a role</span>
                    <span style="font-size:.8rem">For example Head, Supervisor or Auditor</span>
                </a>
            </div>
        </section>

        {{-- ============================ COMPARE ============================ --}}
        <section aria-label="Compare roles">
            <h3 class="text-base font-semibold text-gray-800">Compare roles</h3>
            <p class="text-sm text-gray-500 mt-1 mb-3">Everything each role can do, side by side.</p>

            <div class="rl-matrix-wrap">
                <table class="rl-matrix">
                    <thead>
                        <tr>
                            <th scope="col">Permission</th>
                            @foreach($roles as $role)
                                @php [$fg, $bg] = $role->colors(); @endphp
                                <th scope="col"><span class="rl-badge" style="color:{{ $fg }};background:{{ $bg }}">{{ $role->name }}</span></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groups as $group)
                            <tr class="rl-grp"><td colspan="{{ $roles->count() + 1 }}">{{ $group['label'] }}</td></tr>
                            @foreach($group['items'] as $key => [$label, $desc])
                                <tr>
                                    <td>{{ $label }}<span class="rl-sub">{{ $desc }}</span></td>
                                    @foreach($roles as $role)
                                        @php $yes = $role->can($key); @endphp
                                        <td>
                                            @if($yes)
                                                <span class="rl-yes" title="Allowed"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg><span class="rl-sr">Allowed</span></span>
                                            @else
                                                <span class="rl-no" title="Not allowed">&mdash;<span class="rl-sr">Not allowed</span></span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach

                        <tr class="rl-grp"><td colspan="{{ $roles->count() + 1 }}">Administrator only (cannot be given to other roles)</td></tr>
                        @foreach($locked as $key => [$label, $desc])
                            <tr>
                                <td>{{ $label }}<span class="rl-sub">{{ $desc }}</span></td>
                                @foreach($roles as $role)
                                    <td>
                                        @if($role->isAdmin())
                                            <span class="rl-yes" title="Allowed"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg><span class="rl-sr">Allowed</span></span>
                                        @else
                                            <span class="rl-no" title="Not allowed">&mdash;<span class="rl-sr">Not allowed</span></span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
