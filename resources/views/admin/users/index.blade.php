<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Users</h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif

        <div class="flex items-start gap-2.5 rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3">
            <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.29 4.64 5.12.74-3.7 3.61.87 5.1L10 13.9l-4.58 2.4.87-5.1-3.7-3.61 5.12-.74L10 1.5z" /></svg>
            <p class="text-xs text-amber-800">
                VIP accounts (owners, executives, or other high-priority stakeholders) automatically have every ticket they submit set to
                <strong>Critical</strong> priority — no manual escalation needed.
            </p>
        </div>

        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                        <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Name</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Email</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Role</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">VIP Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition">
                            <td class="px-5 py-3.5 font-medium text-gray-800 border-r border-gray-100">{{ $user->name }}</td>
                            <td class="px-5 py-3.5 text-gray-600 border-r border-gray-100">{{ $user->email }}</td>
                            <td class="px-5 py-3.5 capitalize text-gray-600 border-r border-gray-100">{{ str_replace('_',' ',$user->role) }}</td>
                            <td class="px-5 py-3.5 border-r border-gray-100">
                                @if($user->is_vip)
                                    <x-vip-badge />
                                @else
                                    <span class="text-xs text-gray-400">Standard</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex gap-2 items-center justify-end">
                                    <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex gap-2 items-center">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="rounded-lg border-gray-300 text-xs focus:border-green-700 focus:ring-green-700">
                                            @foreach(['staff','it_support','admin'] as $role)
                                                <option value="{{ $role }}" @selected($user->role === $role)>{{ str_replace('_',' ', ucfirst($role)) }}</option>
                                            @endforeach
                                        </select>
                                        <button class="px-3 py-1.5 bg-gray-800 text-white rounded-lg text-xs font-medium hover:bg-gray-700">Update</button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.users.vip', $user) }}"
                                          @if($user->is_vip) onsubmit="return confirm('Remove VIP status from {{ $user->name }}? Their future tickets will no longer auto-escalate to Critical.');" @endif>
                                        @csrf
                                        @method('PATCH')
                                        @if($user->is_vip)
                                            <button class="px-3 py-1.5 rounded-lg text-xs font-medium border border-red-200 text-red-600 hover:bg-red-50 transition whitespace-nowrap">
                                                Revoke VIP
                                            </button>
                                        @else
                                            <button class="px-3 py-1.5 rounded-lg text-xs font-medium text-white transition whitespace-nowrap" style="background-color:#b45309;" onmouseover="this.style.backgroundColor='#92400e'" onmouseout="this.style.backgroundColor='#b45309'">
                                                Grant VIP
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $users->links() }}</div>
    </div>
</x-app-layout>
