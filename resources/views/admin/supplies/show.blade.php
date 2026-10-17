@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $canManage = $isAdmin || $user->isItSupport();

    $status = $supply->stock_status;
    $qtyColor = ['ok' => 'text-gray-900', 'low' => 'text-amber-600', 'critical' => 'text-orange-600', 'out' => 'text-rose-600'][$status];
    $barColor = ['ok' => '#1a6b3c', 'low' => '#d97706', 'critical' => '#ea580c', 'out' => '#e11d48'][$status];
    $hintColor = ['ok' => 'text-gray-500', 'low' => 'text-amber-700', 'critical' => 'text-orange-700 font-medium', 'out' => 'text-rose-700 font-medium'][$status];

    $reserved = $supply->reserved_quantity;
    $available = $supply->available_quantity;
    $mine = ! $canManage;
    $sameBranch = $supply->location === $user->location;

    // Level meter: the halfway mark is the reorder level, so "low" is visibly in the left half.
    $barPct = $supply->reorder_level > 0
        ? min(100, (int) round($supply->quantity / ($supply->reorder_level * 2) * 100))
        : ($supply->quantity > 0 ? 100 : 0);

    // ?adjust=in|out|count opens the Adjust Stock dialog straight away (used by the Edit page link).
    $requested = request('adjust');
    $openAdjust = $canManage && ($errors->adjust->any() || in_array($requested, ['in', 'out', 'count'], true));
    $initialType = old('type', in_array($requested, ['in', 'out', 'count'], true) ? $requested : 'in');

    // Staff: ?request=1 (from the list's Request button) opens the request dialog straight away.
    $openRequest = $mine && ! $myPending && $sameBranch && $available > 0
        && ($errors->request->any() || request()->boolean('request'));

    $moveStyle = [
        'initial' => 'bg-slate-100 text-slate-600',
        'in' => 'bg-emerald-50 text-emerald-700',
        'out' => 'bg-rose-50 text-rose-700',
        'count' => 'bg-sky-50 text-sky-700',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3">
            <div class="min-w-0">
                <a href="{{ session('list_url.supplies', route('supplies.index')) }}" class="inline-flex items-center gap-1 text-xs font-medium text-gray-400 hover:text-gray-700">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    All supplies
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate mt-0.5">{{ $supply->name }}</h2>
            </div>
            @if($canManage)
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('supplies.edit', $supply) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Edit</a>
                    @if($isAdmin)
                        <x-confirm-action-modal
                            id="delete-supply"
                            action="{{ route('admin.supplies.destroy', $supply) }}"
                            title="Delete this item?"
                            message="Remove {{ $supply->name }} ({{ $supply->code }}) and its stock history? This can't be undone."
                            confirm-label="Delete Item"
                            trigger-label="Delete"
                            trigger-class="inline-flex items-center rounded-lg bg-rose-50 px-3.5 py-2 text-sm font-medium text-rose-600 hover:bg-rose-100"
                        />
                    @endif
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('status'))
            <div class="p-3 bg-green-50 text-green-800 text-sm rounded-lg border border-green-100">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-100">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Stock level --}}
            <div class="lg:col-span-1 lg:order-2 bg-white shadow-sm rounded-2xl border border-gray-100 p-5 sm:p-6 self-start">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Stock on hand</p>
                    <x-supply-status-badge :status="$status" />
                </div>

                <p class="mt-3 text-5xl font-bold tabular-nums {{ $qtyColor }}">{{ number_format($supply->quantity) }}
                    <span class="text-base font-medium text-gray-400">{{ $supply->unit }}</span>
                </p>

                <div class="mt-4">
                    <div class="relative h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ $barPct }}%; background-color: {{ $barColor }};"></div>
                    </div>
                    <p class="mt-2 text-xs {{ $hintColor }}">{{ $supply->stock_hint }}.</p>
                    @if($canManage)
                        <p class="mt-1 text-[11px] text-gray-400">
                            Low at {{ number_format($supply->reorder_level) }} or less
                            @if($supply->critical_level > 0) · Critical at {{ number_format($supply->critical_level) }} or less @endif
                        </p>
                    @endif
                    @if($reserved > 0)
                        <p class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-sky-50 px-2 py-1 text-xs font-medium text-sky-700">
                            {{ number_format($reserved) }} reserved for pending requests · {{ number_format($available) }} available
                        </p>
                    @endif
                </div>

                @if($canManage)
                    <div class="mt-5 grid grid-cols-2 gap-2.5">
                        <button type="button" x-data
                                @click="$dispatch('set-adjust-type', 'in'); $dispatch('open-modal', 'adjust-stock')"
                                class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-2.5 text-sm font-semibold text-white shadow-sm" style="background-color:#1a6b3c;">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Stock In
                        </button>
                        <button type="button" x-data
                                @click="$dispatch('set-adjust-type', 'out'); $dispatch('open-modal', 'adjust-stock')"
                                class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" /></svg>
                            Stock Out
                        </button>
                    </div>
                    <button type="button" x-data
                            @click="$dispatch('set-adjust-type', 'count'); $dispatch('open-modal', 'adjust-stock')"
                            class="mt-2.5 w-full text-center text-xs font-medium text-gray-500 hover:text-green-800 hover:underline">
                        Fix the count after a stock-take
                    </button>
                @else
                    <div class="mt-5">
                        @if($myPending)
                            <div class="rounded-lg border border-sky-200 bg-sky-50 px-3.5 py-3 text-sm text-sky-900">
                                <p class="font-semibold">You've requested {{ number_format($myPending->quantity) }} {{ $supply->unit }}</p>
                                <p class="text-xs text-sky-800/80 mt-0.5">Ticket {{ $myPending->ticket->ticket_number }} is waiting for IT Support.</p>
                                <a href="{{ route('tickets.show', $myPending->ticket) }}" class="mt-2 inline-block text-xs font-semibold text-sky-700 hover:underline">Follow your request →</a>
                            </div>
                        @elseif(! $sameBranch)
                            <div class="rounded-lg bg-gray-50 border border-gray-100 px-3.5 py-3 text-xs text-gray-500">
                                This item is kept at <span class="font-medium text-gray-700">{{ $supply->branch_name }}</span>. You can request items stocked at your own branch.
                            </div>
                        @elseif($available <= 0)
                            <div class="rounded-lg bg-gray-50 border border-gray-100 px-3.5 py-3 text-xs text-gray-500">
                                {{ $supply->quantity > 0 ? 'All of the stock is already reserved for other requests.' : 'Out of stock right now.' }}
                                IT Support is notified when stock is low — or
                                <a href="{{ route('tickets.create') }}" class="font-medium text-green-700 hover:underline">open a ticket</a> if it's urgent.
                            </div>
                        @else
                            <button type="button" x-data @click="$dispatch('open-modal', 'request-supply')"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold text-white shadow-sm" style="background-color:#1a6b3c;">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                                Request this item
                            </button>
                            <p class="mt-2.5 text-center text-[11px] text-gray-400">It becomes a ticket for IT Support — you can follow it there.</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Details --}}
            <div class="lg:col-span-2 lg:order-1 bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden self-start">
                <div class="flex items-center gap-3 px-5 sm:px-6 py-4 border-b border-gray-100">
                    <span class="flex items-center justify-center w-10 h-10 rounded-xl shrink-0" style="background:linear-gradient(135deg,#dff2e6,#c4e6d0);color:#1a6b3c;">
                        <x-supply-icon :category="$supply->category" class="w-5 h-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $supply->name }}</p>
                        <p class="text-xs text-gray-400"><span class="font-mono">{{ $supply->code }}</span> · {{ $supply->category_label }}</p>
                    </div>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 p-5 sm:p-6 text-sm">
                    @foreach([
                        'Brand' => $supply->brand,
                        'Model / Part No.' => $supply->model_no,
                        'Branch' => $supply->branch_name,
                        'Unit' => $supply->unit,
                        'Added' => $supply->created_at?->format('M j, Y'),
                        'Last updated' => $supply->updated_at?->diffForHumans(),
                    ] as $label => $value)
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $label }}</dt>
                            <dd class="mt-0.5 text-gray-800">{{ filled($value) ? $value : '—' }}</dd>
                        </div>
                    @endforeach
                    @if(filled($supply->notes))
                        <div class="sm:col-span-2">
                            <dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Notes</dt>
                            <dd class="mt-0.5 text-gray-700 whitespace-pre-line">{{ $supply->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Stock history --}}
        <div class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                    <span class="w-1.5 h-4 rounded-full" style="background-color:#1a6b3c;"></span>
                    Stock history
                </h3>
                <p class="text-xs text-gray-400">{{ number_format($movements->total()) }} {{ $movements->total() === 1 ? 'entry' : 'entries' }}</p>
            </div>

            @if($movements->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-gray-700 font-medium">No stock movements yet</p>
                    <p class="text-gray-400 text-sm mt-1">Every time stock goes in or out, it shows up here.</p>
                </div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50/80">
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">When</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">What happened</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Change</th>
                            <th class="px-4 sm:px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($movements as $m)
                            <tr>
                                <td class="px-4 sm:px-6 py-3.5 align-top whitespace-nowrap">
                                    <p class="text-gray-800">{{ $m->created_at->format('M j, Y') }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $m->created_at->format('g:i A') }}</p>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $moveStyle[$m->type] ?? 'bg-slate-100 text-slate-600' }}">{{ $m->type_label }}</span>
                                    <span class="text-xs text-gray-400 ml-1">by {{ $m->user?->name ?? 'a removed user' }}</span>
                                    @if(filled($m->note))
                                        <p class="text-xs text-gray-500 mt-1">{{ $m->note }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 align-top text-right whitespace-nowrap font-semibold tabular-nums {{ $m->change > 0 ? 'text-emerald-600' : ($m->change < 0 ? 'text-rose-600' : 'text-gray-500') }}">
                                    {{ $m->change > 0 ? '+' : '' }}{{ number_format($m->change) }}
                                </td>
                                <td class="px-4 sm:px-6 py-3.5 align-top text-right whitespace-nowrap tabular-nums text-gray-700">{{ number_format($m->balance_after) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($movements->hasPages())
                    <div class="border-t border-gray-100 px-4 py-3.5">{{ $movements->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    {{-- Adjust stock dialog (IT Support + Admin only) --}}
    @if($canManage)
        <x-modal name="adjust-stock" :show="$openAdjust" maxWidth="md" focusable>
            <form method="POST" action="{{ route('supplies.adjust', $supply) }}"
                  x-data="{
                      type: @js($initialType),
                      qty: @js((string) old('quantity', '')),
                      current: {{ (int) $supply->quantity }},
                      get n() { return Number.parseInt(this.qty, 10); },
                      get result() {
                          if (Number.isNaN(this.n)) return null;
                          return this.type === 'in' ? this.current + this.n : (this.type === 'out' ? this.current - this.n : this.n);
                      },
                  }"
                  @set-adjust-type.window="type = $event.detail"
                  novalidate>
                @csrf

                <div class="px-6 pt-6 pb-2">
                    <h2 class="text-lg font-semibold text-gray-900">Adjust stock</h2>
                    <p class="text-sm text-gray-500 mt-0.5 truncate">{{ $supply->name }} · {{ number_format($supply->quantity) }} {{ $supply->unit }} on hand</p>
                </div>

                <div class="px-6 py-4 space-y-5">
                    @if($errors->adjust->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-3.5 py-3" role="alert">
                            <ul class="text-sm text-red-700 space-y-0.5">
                                @foreach($errors->adjust->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- What happened --}}
                    <div class="grid grid-cols-3 gap-2" role="radiogroup" aria-label="What happened">
                        @foreach(['in' => 'Stock in', 'out' => 'Stock out', 'count' => 'Set count'] as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="{{ $value }}" x-model="type" class="peer sr-only">
                                <span class="flex items-center justify-center rounded-lg border border-gray-200 bg-white px-2 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50
                                             peer-checked:border-green-700 peer-checked:bg-green-50 peer-checked:text-green-800 peer-checked:ring-2 peer-checked:ring-green-700/20
                                             peer-focus-visible:ring-2 peer-focus-visible:ring-green-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div>
                        <label for="adjust-qty" class="block text-sm font-medium text-gray-700 mb-1.5">
                            <span x-show="type === 'in'">How many did you receive?</span>
                            <span x-show="type === 'out'" x-cloak>How many are you taking out?</span>
                            <span x-show="type === 'count'" x-cloak>What is the real count now?</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input id="adjust-qty" type="number" name="quantity" x-model="qty" inputmode="numeric" min="0" max="1000000" step="1" required
                                   class="block w-32 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                            <p class="text-sm text-gray-500" x-show="result !== null" x-cloak>
                                New balance:
                                <span class="font-semibold tabular-nums" :class="result < 0 ? 'text-rose-600' : 'text-gray-800'" x-text="result"></span>
                                {{ $supply->unit }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label for="adjust-note" class="block text-sm font-medium text-gray-700 mb-1.5">
                            <span x-show="type !== 'count'">Note <span class="font-normal text-gray-400">(optional)</span></span>
                            <span x-show="type === 'count'" x-cloak>Reason for the correction</span>
                        </label>
                        <input id="adjust-note" type="text" name="note" value="{{ old('note') }}" maxlength="255"
                               :placeholder="type === 'in' ? 'e.g. Delivery from supplier, PO #1234' : (type === 'out' ? 'e.g. Issued to Accounting printer, Ticket #482' : 'e.g. Monthly stock-take')"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end sm:gap-3">
                    <button type="button" x-on:click="$dispatch('close')"
                            class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:py-2">Cancel</button>
                    <button type="submit" :disabled="result !== null && result < 0"
                            class="inline-flex justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-50 sm:py-2" style="background-color:#1a6b3c;">
                        Save
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- Request dialog (staff) --}}
    @if($mine && ! $myPending && $sameBranch && $available > 0)
        <x-modal name="request-supply" :show="$openRequest" maxWidth="md" focusable>
            <form method="POST" action="{{ route('supplies.request', $supply) }}" novalidate>
                @csrf

                <div class="px-6 pt-6 pb-2">
                    <h2 class="text-lg font-semibold text-gray-900">Request this item</h2>
                    <p class="text-sm text-gray-500 mt-0.5 truncate">{{ $supply->name }} · {{ number_format($available) }} {{ $supply->unit }} available</p>
                </div>

                <div class="px-6 py-4 space-y-5">
                    @if($errors->request->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-3.5 py-3" role="alert">
                            <ul class="text-sm text-red-700 space-y-0.5">
                                @foreach($errors->request->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label for="request-qty" class="block text-sm font-medium text-gray-700 mb-1.5">How many do you need?</label>
                        <input id="request-qty" type="number" name="quantity" value="{{ old('quantity', 1) }}" inputmode="numeric" min="1" max="{{ $available }}" step="1" required
                               class="block w-32 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                    </div>

                    <div>
                        <label for="request-purpose" class="block text-sm font-medium text-gray-700 mb-1.5">What is it for?</label>
                        <input id="request-purpose" type="text" name="purpose" value="{{ old('purpose') }}" maxlength="255" required
                               placeholder="e.g. Accounting printer — Brother HL-L2370DW"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700">
                    </div>

                    <div>
                        <p class="block text-sm font-medium text-gray-700 mb-1.5">How urgent is it?</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(['normal' => ['Normal', 'Within the next day or two'], 'urgent' => ['Urgent', "I can't work without it"]] as $value => [$label, $hint])
                                <label class="cursor-pointer">
                                    <input type="radio" name="urgency" value="{{ $value }}" class="peer sr-only" @checked(old('urgency', 'normal') === $value)>
                                    <span class="flex h-full flex-col rounded-lg border border-gray-200 bg-white px-3 py-2.5 transition hover:bg-gray-50
                                                 peer-checked:border-green-700 peer-checked:bg-green-50 peer-checked:ring-2 peer-checked:ring-green-700/20 peer-focus-visible:ring-2 peer-focus-visible:ring-green-700">
                                        <span class="text-sm font-medium text-gray-800">{{ $label }}</span>
                                        <span class="text-[11px] text-gray-500 mt-0.5">{{ $hint }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end sm:gap-3">
                    <button type="button" x-on:click="$dispatch('close')"
                            class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:py-2">Cancel</button>
                    <button type="submit" class="inline-flex justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm sm:py-2" style="background-color:#1a6b3c;">
                        Send request
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
</x-app-layout>
