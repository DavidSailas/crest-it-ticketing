<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Users</h2>
            <div class="flex gap-2" x-data="{ showExport: false }">
                <div class="relative">
                    <button @click="showExport = !showExport" @click.outside="showExport = false"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">
                        Export
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div x-show="showExport" x-cloak x-transition
                         class="absolute right-0 mt-1.5 w-44 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-20">
                        <a href="{{ route('admin.users.export.pdf', ['tab' => $tab]) }}" class="flex items-center gap-2 px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Export as PDF
                        </a>
                        <a href="{{ route('admin.users.export.excel', ['tab' => $tab]) }}" class="flex items-center gap-2 px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Export as Excel
                        </a>
                        <a href="{{ route('admin.users.export', ['tab' => $tab]) }}" class="flex items-center gap-2 px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Export as CSV
                        </a>
                    </div>
                </div>
                <label class="px-3.5 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50 cursor-pointer">
                    Import CSV/Excel
                    <form id="import-form" method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data" class="hidden">
                        @csrf
                        <input type="file" name="file" accept=".csv,.xlsx,.xls" onchange="document.getElementById('import-form').submit()">
                    </form>
                    <input type="file" accept=".csv,.xlsx,.xls" class="hidden" onchange="
                        const dt = new DataTransfer(); dt.items.add(this.files[0]);
                        document.querySelector('#import-form input[type=file]').files = dt.files;
                        document.getElementById('import-form').submit();">
                </label>
                <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm font-semibold shadow-sm" style="background-color:#1a6b3c;">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Add User
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8">
        @if(session('status'))
            <div class="mb-4 p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-50 text-red-800 text-sm rounded-lg border border-red-100">{{ session('error') }}</div>
        @endif

        {{-- Tabs --}}
        <div class="flex gap-1 mb-5 border-b border-gray-200">
            @foreach([
                ['key' => 'staff', 'label' => 'Staff', 'count' => $counts['staff']],
                ['key' => 'it_support', 'label' => 'IT Support', 'count' => $counts['it_support']],
                ['key' => 'vip', 'label' => 'VIP', 'count' => $counts['vip']],
            ] as $t)
                <a href="{{ route('admin.users.index', ['tab' => $t['key']]) }}"
                   class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition {{ $tab === $t['key'] ? 'border-green-700 text-green-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $t['label'] }}
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-xs {{ $tab === $t['key'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $t['count'] }}</span>
                </a>
            @endforeach
        </div>

        <p class="text-xs text-gray-400 mb-3">CSV or Excel (.xlsx) import expects columns: <span class="font-mono">Name, Email, Role, Branch, VIP</span> (Role: staff / it_support / admin. Branch: Cebu / Manila / Cagayan de Oro / Davao, or CEB / MNL / CDO / DVO). New accounts get a random temporary password.</p>

        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b-2 border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Email</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Role</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">Branch</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 border-r border-gray-200">VIP</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition">
                            <td class="px-4 py-3.5 font-medium text-gray-800 border-r border-gray-100">{{ $user->name }}</td>
                            <td class="px-4 py-3.5 text-gray-600 border-r border-gray-100">{{ $user->email }}</td>
                            <td class="px-4 py-3.5 capitalize text-gray-600 border-r border-gray-100">{{ str_replace('_',' ',$user->role) }}</td>
                            <td class="px-4 py-3.5 text-gray-600 border-r border-gray-100">{{ $user->branch_name ?? '—' }}</td>
                            <td class="px-4 py-3.5 border-r border-gray-100">
                                <form method="POST" action="{{ route('admin.users.vip', $user) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-xs px-2 py-1 rounded-full font-medium {{ $user->is_vip ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $user->is_vip ? 'VIP' : 'Standard' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-green-700 hover:underline font-medium text-xs mr-3">Edit</a>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
                                      onsubmit="return confirm('Delete {{ $user->name }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:underline font-medium text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center text-gray-400">No users in this group yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $users->links() }}</div>
    </div>
</x-app-layout>
