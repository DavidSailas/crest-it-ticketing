<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Users</h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <p class="text-sm text-gray-500">Look up a staff member's account while you work a ticket. This is view-only — head to Manage Users to edit or remove an account.</p>

        <form method="GET" action="{{ route('users.directory') }}" class="flex gap-2 max-w-sm">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search name or email"
                class="flex-1 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
            <button class="px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm" style="background-color:#1a6b3c;">Search</button>
        </form>

        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200">
                        <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
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
                                <p class="font-medium text-gray-800 truncate">{{ $user->name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                                <div class="md:hidden mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-gray-500">
                                    <span>{{ $user->branch_name ?? '—' }}</span>
                                    <span class="lg:hidden text-gray-300">·</span>
                                    <span class="lg:hidden">{{ $assetCounts[$user->id] ?? 0 }} asset{{ ($assetCounts[$user->id] ?? 0) === 1 ? '' : 's' }}</span>
                                    @if($user->is_vip)
                                        <span class="sm:hidden"><x-vip-badge size="compact" /></span>
                                    @endif
                                </div>
                            </td>
                            <td class="hidden md:table-cell px-5 py-3.5 text-gray-600 whitespace-nowrap">{{ $user->branch_name ?? '—' }}</td>
                            <td class="hidden sm:table-cell px-5 py-3.5">
                                @if($user->is_vip)
                                    <x-vip-badge size="compact" />
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="hidden lg:table-cell px-5 py-3.5 text-gray-500">{{ $assetCounts[$user->id] ?? 0 }}</td>
                            <td class="px-4 sm:px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('users.directory.show', $user) }}" class="text-green-700 hover:underline font-medium text-xs">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-14 text-center text-gray-400">No matching staff members.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl px-4 py-3.5">{{ $users->links() }}</div>
    </div>
</x-app-layout>
