<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-medium text-gray-500 hover:text-gray-800">← Manage Users</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Email addresses</h2>
            </div>
        </div>
    </x-slot>

    @php
        $tabs = ['missing' => 'No email', 'pending' => 'Waiting for confirmation', 'confirmed' => 'Confirmed', 'all' => 'Everyone'];
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-5">

            <p class="px-4 sm:px-0 text-sm text-gray-600 max-w-3xl">
                Ticket updates are emailed to the address saved on each account. Add the addresses here: the suggestion is the username plus
                <span class="font-medium">&#64;{{ config('company.email_domain') }}</span> — please check each one before saving.
                Addresses you save are trusted and start working immediately, so a wrong address would send someone's tickets to the wrong person. Only addresses people add or change themselves need a confirmation link.
            </p>

            @if(session('status'))
                <div class="mx-4 sm:mx-0 p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
            @endif
            @if(session('error'))
                <div class="mx-4 sm:mx-0 p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-100" role="alert">{{ session('error') }}</div>
            @endif
            @if(session('email_problems'))
                <div class="mx-4 sm:mx-0 p-3 bg-amber-50 text-amber-900 text-sm rounded-lg border border-amber-100" role="alert">
                    <p class="font-semibold mb-1">These were not saved:</p>
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach(session('email_problems') as $problem)
                            <li>{{ $problem }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-xl border border-gray-100">
                {{-- Tabs + search --}}
                <div class="flex flex-col gap-3 border-b border-gray-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <nav class="flex flex-wrap gap-1.5" aria-label="Filter">
                        @foreach($tabs as $key => $label)
                            <a href="{{ route('admin.users.emails', ['show' => $key]) }}"
                               class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium {{ $filter === $key ? 'bg-green-50 text-green-800 ring-1 ring-inset ring-green-200' : 'text-gray-600 hover:bg-gray-50' }}">
                                {{ $label }}
                                <span class="text-xs {{ $filter === $key ? 'text-green-700' : ($key === 'missing' && $counts[$key] > 0 ? 'font-semibold text-amber-600' : 'text-gray-400') }}">{{ $counts[$key] }}</span>
                            </a>
                        @endforeach
                    </nav>
                    <form method="GET" action="{{ route('admin.users.emails') }}" class="flex gap-2">
                        <input type="hidden" name="show" value="{{ $filter }}">
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, username or email" class="block w-full sm:w-64 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                    </form>
                </div>

                @if($users->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <p class="text-gray-700 font-medium">{{ $filter === 'missing' ? 'Everyone has an email address.' : 'Nobody here.' }}</p>
                        <p class="text-gray-400 text-sm mt-1">{{ $filter === 'missing' ? 'Check "Waiting for confirmation" to see who still has to click their link.' : 'Try another tab or clear the search.' }}</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.users.emails.update') }}">
                        @csrf

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                                        <th class="w-10 px-4 py-3">
                                            <input type="checkbox" aria-label="Select all" class="rounded border-gray-300 text-green-700 focus:ring-green-700"
                                                   onclick="this.closest('table').querySelectorAll('tbody input[type=checkbox]').forEach(c => c.checked = this.checked)">
                                        </th>
                                        <th class="px-3 py-3">Person</th>
                                        <th class="px-3 py-3">Branch · Department</th>
                                        <th class="px-3 py-3 min-w-[16rem]">Email address</th>
                                        <th class="px-3 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($users as $u)
                                        @php
                                            $has = filled($u->email);
                                            $suggest = $has ? null : $u->suggestedEmail();
                                            $value = old("emails.{$u->id}", $has ? $u->email : ($suggest ?? ''));
                                        @endphp
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="px-4 py-3 align-top">
                                                <input type="checkbox" name="save[{{ $u->id }}]" value="1" @checked(! $has && $suggest) aria-label="Save {{ $u->name }}"
                                                       class="mt-2 rounded border-gray-300 text-green-700 focus:ring-green-700">
                                            </td>
                                            <td class="px-3 py-3 align-top">
                                                <p class="font-medium text-gray-900">{{ $u->name }}</p>
                                                <p class="text-xs text-gray-400">{{ $u->username }}</p>
                                            </td>
                                            <td class="px-3 py-3 align-top text-gray-600">
                                                {{ $u->branch_name ?? '—' }}<br><span class="text-xs text-gray-400">{{ $u->department?->name ?? 'No department' }}</span>
                                            </td>
                                            <td class="px-3 py-3 align-top">
                                                <input type="email" name="emails[{{ $u->id }}]" value="{{ $value }}" placeholder="name@company.com" autocomplete="off"
                                                       oninput="this.closest('tr').querySelector('input[type=checkbox]').checked = true"
                                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                                                @if($suggest)
                                                    <p class="mt-1 text-[11px] text-gray-400">Suggested from the username — check it.</p>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 align-top whitespace-nowrap">
                                                @if(! $has)
                                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">No email</span>
                                                @elseif($u->hasVerifiedEmail())
                                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-200">Confirmed</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200">Waiting</span>
                                                    <button type="submit" form="resend-{{ $u->id }}" class="ml-2 text-xs font-medium text-green-700 hover:underline">Resend link</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50/60 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-gray-500">Only ticked rows are saved. A row marked “Waiting” (added by the person, not confirmed yet) is confirmed for them when you save it. Clearing a box and saving removes the address.</p>
                            <button type="submit" class="inline-flex justify-center rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm" style="background-color:#1a6b3c;">Save ticked addresses</button>
                        </div>
                    </form>

                    {{-- Resend forms live outside the main form (forms can't nest) --}}
                    @foreach($users as $u)
                        @if(filled($u->email) && ! $u->hasVerifiedEmail())
                            <form id="resend-{{ $u->id }}" method="POST" action="{{ route('admin.users.emails.resend', $u) }}" class="hidden">@csrf</form>
                        @endif
                    @endforeach

                    @if($users->hasPages())
                        <div class="border-t border-gray-100 px-4 py-3">{{ $users->links() }}</div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
