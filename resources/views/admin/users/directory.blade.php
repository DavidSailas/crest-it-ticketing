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

        <div class="bg-white shadow-sm rounded-xl overflow-x-auto border border-gray-200">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Email</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Branch</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">VIP</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Assets</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition">
                            <td class="px-4 py-3.5 font-medium text-gray-800 border-r border-gray-100">{{ $user->name }}</td>
                            <td class="px-4 py-3.5 text-gray-600 border-r border-gray-100">{{ $user->email }}</td>
                            <td class="px-4 py-3.5 text-gray-600 border-r border-gray-100">{{ $user->branch_name ?? '—' }}</td>
                            <td class="px-4 py-3.5 border-r border-gray-100">
                                @if($user->is_vip)
                                    <x-vip-badge size="compact" />
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-gray-500 border-r border-gray-100">{{ $assetCounts[$user->id] ?? 0 }}</td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('users.directory.show', $user) }}" class="text-green-700 hover:underline font-medium text-xs">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center text-gray-400">No matching staff members.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $users->links() }}</div>
    </div>
</x-app-layout>
