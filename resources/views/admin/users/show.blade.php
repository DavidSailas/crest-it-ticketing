<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap gap-3 justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $user->name }}</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                    Edit
                </a>
                @if($user->id !== auth()->id())
                    @if($user->isSuspended())
                        <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                            @csrf @method('PATCH')
                            <button class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium text-green-700 border border-green-200 bg-green-50 hover:bg-green-100">
                                Activate
                            </button>
                        </form>
                    @else
                        <x-confirm-action-modal
                            id="suspend-user-profile-{{ $user->id }}"
                            action="{{ route('admin.users.suspend', $user) }}"
                            method="PATCH"
                            tone="warning"
                            title="Suspend this account?"
                            message="{{ $user->name }} won't be able to sign in until an admin reactivates the account. Nothing is deleted, and this can be reversed at any time."
                            confirm-label="Suspend"
                            confirm-class="bg-amber-500 hover:bg-amber-600"
                            trigger-label="Suspend"
                            trigger-class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium text-amber-700 border border-amber-200 bg-amber-50 hover:bg-amber-100"
                        />
                    @endif
                    <x-confirm-action-modal
                        id="delete-user-profile-{{ $user->id }}"
                        action="{{ route('admin.users.destroy', $user) }}"
                        title="Delete this account?"
                        message="Delete {{ $user->name }}? This permanently removes the account and cannot be undone."
                        confirm-label="Delete Account"
                        trigger-label="Delete"
                        trigger-class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium text-red-600 border border-red-200 bg-red-50 hover:bg-red-100"
                    />
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-100">{{ session('error') }}</div>
        @endif

        {{-- Profile card --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-6 flex items-start gap-4">
                <x-avatar-viewer :user="$user" size="lg" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-lg font-semibold text-gray-800">{{ $user->name }}</p>
                        @if($user->is_vip)
                            <x-vip-badge size="compact" />
                        @endif
                        @if($user->isSuspended())
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-200">Suspended</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 ring-1 ring-inset ring-green-200">Active</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $user->username ?? '—' }} · {{ $user->email }}</p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mt-5 pt-5 border-t border-gray-100 text-sm">
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Role</p>
                            <p class="text-gray-800 font-medium capitalize">{{ str_replace('_', ' ', $user->role) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Department</p>
                            <p class="text-gray-800 font-medium truncate">{{ $user->department->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Position</p>
                            <p class="text-gray-800 font-medium truncate">{{ $user->position->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Branch</p>
                            <p class="text-gray-800 font-medium">{{ $user->branch_name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Account Created</p>
                            <p class="text-gray-800 font-medium">{{ $user->created_at?->format('M j, Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wide mb-1">Assets Issued</p>
                            <p class="text-gray-800 font-medium">{{ $assets->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tickets submitted --}}
        <div>
            <h3 class="text-base font-semibold text-gray-800 mb-3">Tickets Submitted</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                @foreach([
                    ['label' => 'Total', 'value' => $submittedCounts['total'], 'fg' => 'text-gray-800'],
                    ['label' => 'Open', 'value' => $submittedCounts['open'], 'fg' => 'text-amber-600'],
                    ['label' => 'In Progress', 'value' => $submittedCounts['in_progress'], 'fg' => 'text-blue-600'],
                    ['label' => 'Resolved', 'value' => $submittedCounts['resolved'], 'fg' => 'text-green-700'],
                ] as $stat)
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-2xl font-semibold {{ $stat['fg'] }} leading-none">{{ $stat['value'] }}</p>
                        <p class="text-xs text-gray-500 mt-1.5">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                @if($submittedTickets->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm text-gray-400">This user hasn't submitted any tickets yet.</p>
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-200">
                                <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Ticket</th>
                                <th class="hidden sm:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Priority</th>
                                <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="hidden md:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Submitted</th>
                                <th class="w-16 px-4 sm:px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($submittedTickets as $ticket)
                                <tr class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-4 sm:px-5 py-3.5 max-w-0 w-full">
                                        <p class="truncate"><span class="font-mono text-xs text-gray-400 mr-1">{{ $ticket->ticket_number }}</span><span class="font-medium text-gray-800">{{ $ticket->title }}</span></p>
                                        <div class="sm:hidden mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-gray-500">
                                            <x-priority-badge :priority="$ticket->priority" />
                                            <span class="md:hidden text-gray-300">·</span>
                                            <span class="md:hidden">{{ $ticket->created_at->diffForHumans() }}</span>
                                        </div>
                                    </td>
                                    <td class="hidden sm:table-cell px-5 py-3.5"><x-priority-badge :priority="$ticket->priority" /></td>
                                    <td class="px-4 sm:px-5 py-3.5"><x-status-badge :status="$ticket->status" /></td>
                                    <td class="hidden md:table-cell px-5 py-3.5 text-gray-500 whitespace-nowrap">{{ $ticket->created_at->diffForHumans() }}</td>
                                    <td class="px-4 sm:px-5 py-3.5 text-right">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="text-green-700 hover:underline font-medium text-xs">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Tickets handled — IT Support only --}}
        @if($handledTickets !== null)
            <div>
                <h3 class="text-base font-semibold text-gray-800 mb-3">Tickets Handled</h3>
                <div class="grid grid-cols-3 gap-4 mb-4">
                    @foreach([
                        ['label' => 'Total', 'value' => $handledCounts['total'], 'fg' => 'text-gray-800'],
                        ['label' => 'In Progress', 'value' => $handledCounts['in_progress'], 'fg' => 'text-blue-600'],
                        ['label' => 'Resolved', 'value' => $handledCounts['resolved'], 'fg' => 'text-green-700'],
                    ] as $stat)
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <p class="text-2xl font-semibold {{ $stat['fg'] }} leading-none">{{ $stat['value'] }}</p>
                            <p class="text-xs text-gray-500 mt-1.5">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                    @if($handledTickets->isEmpty())
                        <div class="px-6 py-12 text-center">
                            <p class="text-sm text-gray-400">Nothing assigned to this agent yet.</p>
                        </div>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50/80 border-b border-gray-200">
                                    <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Ticket</th>
                                    <th class="hidden sm:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Requester</th>
                                    <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th class="hidden md:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Updated</th>
                                    <th class="w-16 px-4 sm:px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($handledTickets as $ticket)
                                    <tr class="hover:bg-gray-50/60 transition-colors">
                                        <td class="px-4 sm:px-5 py-3.5 max-w-0 w-full">
                                            <p class="truncate"><span class="font-mono text-xs text-gray-400 mr-1">{{ $ticket->ticket_number }}</span><span class="font-medium text-gray-800">{{ $ticket->title }}</span></p>
                                        </td>
                                        <td class="hidden sm:table-cell px-5 py-3.5 text-gray-600">{{ $ticket->creator->name ?? '—' }}</td>
                                        <td class="px-4 sm:px-5 py-3.5"><x-status-badge :status="$ticket->status" /></td>
                                        <td class="hidden md:table-cell px-5 py-3.5 text-gray-500 whitespace-nowrap">{{ $ticket->updated_at->diffForHumans() }}</td>
                                        <td class="px-4 sm:px-5 py-3.5 text-right">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="text-green-700 hover:underline font-medium text-xs">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @endif

        {{-- Assigned assets --}}
        <div>
            <h3 class="text-base font-semibold text-gray-800 mb-3">Assigned Assets</h3>
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                @if($assets->isEmpty())
                    <div class="flex flex-col items-center justify-center text-center px-6 py-12">
                        <svg class="w-9 h-9 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg>
                        <p class="text-sm text-gray-400">No assets issued to this user.</p>
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-200">
                                <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Asset Tag</th>
                                <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Device</th>
                                <th class="hidden sm:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Type</th>
                                <th class="hidden md:table-cell px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Serial</th>
                                <th class="px-4 sm:px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($assets as $asset)
                                <tr class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-4 sm:px-5 py-3.5 font-mono text-xs font-semibold text-gray-700 whitespace-nowrap">{{ $asset->asset_tag }}</td>
                                    <td class="px-4 sm:px-5 py-3.5 max-w-0 w-full">
                                        <p class="text-gray-800 font-medium truncate">{{ $asset->device_name }}</p>
                                        <div class="sm:hidden mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-gray-500">
                                            <span>{{ \App\Models\Asset::TYPES[$asset->type] ?? $asset->type }}</span>
                                            <span class="md:hidden text-gray-300">·</span>
                                            <span class="md:hidden">{{ $asset->serial_number ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td class="hidden sm:table-cell px-5 py-3.5 text-gray-600">{{ \App\Models\Asset::TYPES[$asset->type] ?? $asset->type }}</td>
                                    <td class="hidden md:table-cell px-5 py-3.5 text-gray-500">{{ $asset->serial_number ?? '—' }}</td>
                                    <td class="px-4 sm:px-5 py-3.5"><x-asset-status-badge :status="$asset->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
