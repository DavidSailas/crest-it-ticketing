<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Users</h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <p class="text-sm text-gray-500">
            @if(auth()->user()->role === 'staff')
                Browse staff and IT Support accounts and the assets assigned to them. This is view-only.
            @else
                Look up a coworker's account while you work a ticket. This is view-only — head to Manage Users to edit or remove an account.
            @endif
        </p>

        {{-- Search + filters (same Branch / Department filters as Admin > Manage Users) --}}
        <form method="GET" action="{{ route('users.directory') }}" data-live-filter class="bg-white rounded-xl border border-gray-200 shadow-sm p-3.5 flex flex-col lg:flex-row gap-3"
              x-data="{ filters: {{ count($filters) ? 'true' : 'false' }} }">
            <div class="flex flex-1 items-center gap-2">
            <div class="relative min-w-0 flex-1">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                <input type="text" name="q" value="{{ $search }}" placeholder="Search name or email" autocomplete="off"
                    class="w-full rounded-lg border-gray-300 text-sm pl-9 focus:border-green-700 focus:ring-green-700">
            </div>
                {{-- Phones: the dropdowns tuck away behind this button so the list stays in view. --}}
                <button type="button" @click="filters = ! filters" :aria-expanded="filters" aria-controls="directory-filter-fields"
                        class="btn btn-secondary shrink-0 sm:hidden" :class="filters ? 'border-green-700 bg-green-50 text-green-800' : ''">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18M6 12h12m-9 7.5h6" /></svg>
                    Filters
                    @if(count($filters) > 0)<span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-green-700 px-1.5 text-[11px] font-semibold text-white">{{ count($filters) }}</span>@endif
                </button>
            </div>
            <div id="directory-filter-fields" class="hidden grid-cols-1 gap-3 sm:grid sm:grid-cols-3 lg:flex" :class="filters && 'max-sm:!grid'">
                <select name="role" aria-label="Filter by role" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 lg:w-36">
                    <option value="">All roles</option>
                    <option value="staff" @selected(($filters['role'] ?? '') === 'staff')>Staff</option>
                    <option value="it_support" @selected(($filters['role'] ?? '') === 'it_support')>IT Support</option>
                </select>
                <select name="branch" aria-label="Filter by branch office" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 lg:w-40">
                    <option value="">All branches</option>
                    @foreach($branches as $code => $label)
                        <option value="{{ $code }}" @selected(($filters['branch'] ?? '') === (string) $code)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="department_id" aria-label="Filter by department" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 lg:w-44">
                    <option value="">All departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((int) ($filters['department_id'] ?? 0) === $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 shrink-0">
                <a href="{{ route('users.directory') }}" data-live-clear
                   @if($search === '' && ! count($filters)) style="display:none" @endif
                   class="btn btn-secondary btn-sm">Clear</a>
            </div>
        </form>

        <div id="directory-results" data-live-list data-live-scroll class="space-y-4">

        @if($search !== '' || count($filters))
            <p class="text-sm text-gray-500 px-1">
                Showing <span class="font-semibold text-gray-700">{{ number_format($users->total()) }}</span> matching user{{ $users->total() === 1 ? '' : 's' }}
            </p>
        @endif

        {{-- Phones: one card per person --}}
        <div class="space-y-3 md:hidden">
            @forelse($users as $user)
                @php $assetTotal = $assetCounts[$user->id] ?? 0; @endphp
                <x-m-card :href="route('users.directory.show', $user)" :label="'View '.$user->name">
                    <x-slot name="lead"><x-avatar :user="$user" size="md" /></x-slot>
                    <x-slot name="title">{{ $user->name }}</x-slot>
                    <x-slot name="sub">
                        <span class="break-all">{{ $user->email }}</span>
                        <span class="mt-1.5 flex flex-wrap items-center gap-1.5">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $user->role === 'it_support' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ str_replace('_', ' ', $user->role) }}</span>
                            @if($user->is_vip)<x-vip-badge size="compact" />@endif
                        </span>
                    </x-slot>
                    <x-slot name="badge">
                        <svg class="mt-1 h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </x-slot>

                    <x-m-meta label="Department">{{ $user->department->name ?? '—' }}</x-m-meta>
                    <x-m-meta label="Branch">{{ $user->branch_name ?? '—' }}</x-m-meta>
                    <x-m-meta label="Assets">{{ $assetTotal }} asset{{ $assetTotal === 1 ? '' : 's' }}</x-m-meta>
                </x-m-card>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white px-6 py-12 text-center text-sm text-gray-400 shadow-sm">No matching accounts.</div>
            @endforelse
        </div>

        {{-- Tablets and up: the table --}}
        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200">
                        <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                        <th class="hidden sm:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                        <th class="hidden md:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Department</th>
                        <th class="hidden md:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Branch</th>
                        <th class="hidden sm:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">VIP</th>
                        <th class="hidden lg:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Assets</th>
                        <th class="w-16 sm:w-24 px-4 sm:px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 sm:px-5 py-3.5 max-w-0 w-full">
                                <div class="flex items-center gap-3 min-w-0">
                                    <x-avatar :user="$user" size="sm" />
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-800 truncate">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                                <div class="md:hidden mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-gray-500">
                                    <span class="sm:hidden capitalize">{{ str_replace('_', ' ', $user->role) }}</span>
                                    <span class="sm:hidden text-gray-300">·</span>
                                    <span>{{ $user->branch_name ?? '—' }}</span>
                                    <span class="text-gray-300">·</span>
                                    <span class="truncate max-w-[8rem]">{{ $user->department->name ?? '—' }}</span>
                                    <span class="lg:hidden text-gray-300">·</span>
                                    <span class="lg:hidden">{{ $assetCounts[$user->id] ?? 0 }} asset{{ ($assetCounts[$user->id] ?? 0) === 1 ? '' : 's' }}</span>
                                    @if($user->is_vip)
                                        <span class="sm:hidden"><x-vip-badge size="compact" /></span>
                                    @endif
                                </div>
                            </td>
                            <td class="hidden sm:table-cell px-5 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize {{ $user->role === 'it_support' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ str_replace('_', ' ', $user->role) }}</span>
                            </td>
                            <td class="hidden md:table-cell px-5 py-3.5 text-gray-600 whitespace-nowrap"><span class="block truncate max-w-[10rem]">{{ $user->department->name ?? '—' }}</span></td>
                            <td class="hidden md:table-cell px-5 py-3.5 text-gray-600 whitespace-nowrap">{{ $user->branch_name ?? '—' }}</td>
                            <td class="hidden sm:table-cell px-5 py-3.5 whitespace-nowrap">
                                @if($user->is_vip)
                                    <x-vip-badge size="compact" />
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="hidden lg:table-cell px-5 py-3.5 text-gray-500 whitespace-nowrap">{{ $assetCounts[$user->id] ?? 0 }}</td>
                            <td class="px-4 sm:px-5 py-3.5 text-right whitespace-nowrap">
                                <x-view-link href="{{ route('users.directory.show', $user) }}" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-14 text-center text-gray-400">No matching accounts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl px-4 py-3.5">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>
