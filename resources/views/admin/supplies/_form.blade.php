{{--
    Shared by New Item and Edit Item.
    Expects: $supply (new Supply with defaults on create), $branches, $action, $method ('POST' | 'PUT'), $mode ('create' | 'edit')
--}}
@php
    $isCreate = $mode === 'create';
    $field = 'block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700';

    // Alpine state: the brand control depends on the category, and the critical level
    // follows half the reorder level until someone sets it by hand.
    $startReorder = (int) old('reorder_level', $supply->reorder_level);
    $startCritical = (int) old('critical_level', $isCreate ? intdiv($startReorder, 2) : $supply->critical_level);
    // On edit the saved value is respected; on create it follows the reorder level until typed over.
    $criticalTouched = $isCreate ? old('critical_level') !== null : true;
@endphp

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3.5" role="alert">
        <p class="text-sm font-semibold text-red-800 mb-1">Please fix the following:</p>
        <ul class="text-sm text-red-700 list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="space-y-8" novalidate
      x-data="{
          category: @js((string) old('category', $supply->category)),
          reorder: {{ $startReorder }},
          critical: {{ $startCritical }},
          criticalTouched: {{ $criticalTouched ? 'true' : 'false' }},
          get isInk() { return this.category === 'ink_toner'; },
      }">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    {{-- Item --}}
    <section class="space-y-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Item</p>

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Item name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $supply->name) }}" maxlength="150" required autofocus
                   placeholder="e.g. HP 305A Black Toner, AA Batteries, USB Flash Drive 32GB" class="{{ $field }}">
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <p class="block text-sm font-medium text-gray-700 mb-1.5">Category</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                @foreach(\App\Models\Supply::CATEGORIES as $code => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="category" value="{{ $code }}" x-model="category" class="peer sr-only" @checked(old('category', $supply->category) === $code)>
                        <span class="flex h-full flex-col items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2 py-3 text-center text-xs font-medium text-gray-600 transition
                                     hover:bg-gray-50 peer-checked:border-green-700 peer-checked:bg-green-50 peer-checked:text-green-800 peer-checked:ring-2 peer-checked:ring-green-700/20
                                     peer-focus-visible:ring-2 peer-focus-visible:ring-green-700">
                            <x-supply-icon :category="$code" class="w-5 h-5" />
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('category')" class="mt-1" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label for="brand" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Brand <span class="font-normal text-gray-400" x-show="!isInk">(optional)</span>
                </label>

                {{-- Ink & toner: only the company's printer brands. Everything else: free text. --}}
                <div x-show="isInk" x-cloak>
                    <select id="brand" name="brand" :disabled="!isInk" class="{{ $field }}">
                        <option value="">Choose Brother or Epson…</option>
                        @foreach(\App\Models\Supply::PRINTER_BRANDS as $brand)
                            <option value="{{ $brand }}" @selected(old('brand', $supply->brand) === $brand)>{{ $brand }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Ink &amp; toner is stocked for Brother and Epson printers only.</p>
                </div>
                <div x-show="!isInk">
                    <input id="brand-text" type="text" name="brand" value="{{ old('brand', $supply->brand) }}" maxlength="100" :disabled="isInk"
                           placeholder="e.g. Logitech, Kingston" class="{{ $field }}">
                </div>
                <x-input-error :messages="$errors->get('brand')" class="mt-1" />
            </div>
            <div>
                <label for="model_no" class="block text-sm font-medium text-gray-700 mb-1.5">
                    <span x-text="isInk ? 'Cartridge / Model No.' : 'Model / Part No.'">Model / Part No.</span>
                    <span class="font-normal text-gray-400">(optional)</span>
                </label>
                <input id="model_no" type="text" name="model_no" value="{{ old('model_no', $supply->model_no) }}" maxlength="100"
                       :placeholder="isInk ? 'e.g. TN-2380, 003 / T00V100' : 'e.g. M185, CF230A'" class="{{ $field }}">
                <x-input-error :messages="$errors->get('model_no')" class="mt-1" />
            </div>
        </div>
    </section>

    {{-- Stock --}}
    <section class="space-y-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Stock</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                @if($isCreate)
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1.5">Quantity on hand</label>
                    <input id="quantity" type="number" name="quantity" inputmode="numeric" min="0" max="1000000" step="1" value="{{ old('quantity', $supply->quantity) }}" required class="{{ $field }}">
                    <p class="text-xs text-gray-400 mt-1">Opening stock — recorded in the item's history.</p>
                    <x-input-error :messages="$errors->get('quantity')" class="mt-1" />
                @else
                    <p class="block text-sm font-medium text-gray-700 mb-1.5">Quantity on hand</p>
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                        <span class="font-semibold tabular-nums text-gray-800">{{ number_format($supply->quantity) }} <span class="font-normal text-gray-400">{{ $supply->unit }}</span></span>
                        <a href="{{ route('supplies.show', [$supply, 'adjust' => 'in']) }}" class="text-xs font-medium text-green-700 hover:underline">Adjust stock</a>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Changed with Stock In / Out so it's tracked.</p>
                @endif
            </div>
            <div>
                <label for="unit" class="block text-sm font-medium text-gray-700 mb-1.5">Unit</label>
                <select id="unit" name="unit" class="{{ $field }}">
                    @foreach(\App\Models\Supply::UNITS as $unit)
                        <option value="{{ $unit }}" @selected(old('unit', $supply->unit) === $unit)>{{ $unit }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('unit')" class="mt-1" />
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 sm:p-5 space-y-4">
            <div>
                <p class="text-sm font-semibold text-gray-800">Stock warnings</p>
                <p class="text-xs text-gray-500 mt-0.5">Each item can warn at its own levels — a box of staples and a printer toner don't run low at the same number.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="reorder_level" class="block text-sm font-medium text-gray-700 mb-1.5">Low stock at</label>
                    <input id="reorder_level" type="number" name="reorder_level" inputmode="numeric" min="0" max="1000000" step="1" required
                           x-model.number="reorder" @input="if (!criticalTouched) critical = Math.floor((reorder || 0) / 2)" class="{{ $field }}">
                    <p class="text-xs text-gray-400 mt-1">Time to plan a reorder.</p>
                    <x-input-error :messages="$errors->get('reorder_level')" class="mt-1" />
                </div>
                <div>
                    <label for="critical_level" class="block text-sm font-medium text-gray-700 mb-1.5">Critical at</label>
                    <input id="critical_level" type="number" name="critical_level" inputmode="numeric" min="0" max="1000000" step="1" required
                           x-model.number="critical" @input="criticalTouched = true" class="{{ $field }}">
                    <p class="text-xs text-gray-400 mt-1">Reorder now. Use 0 to skip this warning.</p>
                    <x-input-error :messages="$errors->get('critical_level')" class="mt-1" />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>In stock above <span x-text="reorder || 0"></span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 font-medium text-amber-700 ring-1 ring-inset ring-amber-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Low at <span x-text="reorder || 0"></span> or less
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-orange-50 px-2.5 py-1 font-semibold text-orange-700 ring-1 ring-inset ring-orange-300" x-show="critical > 0">
                    <span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>Critical at <span x-text="critical"></span> or less
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 font-medium text-rose-700 ring-1 ring-inset ring-rose-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>Out at 0
                </span>
            </div>
        </div>
    </section>

    {{-- Where --}}
    <section class="space-y-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Location &amp; notes</p>

        <div>
            <label for="location" class="block text-sm font-medium text-gray-700 mb-1.5">Branch</label>
            <select id="location" name="location" required class="{{ $field }} sm:max-w-xs">
                <option value="">Choose a branch…</option>
                @foreach($branches as $code => $name)
                    <option value="{{ $code }}" @selected(old('location', $supply->location) === $code)>{{ $name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('location')" class="mt-1" />
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1.5">Notes <span class="font-normal text-gray-400">(optional)</span></label>
            <textarea id="notes" name="notes" rows="3" maxlength="1000" placeholder="Where it's kept, compatible printers, supplier, etc." class="{{ $field }}">{{ old('notes', $supply->notes) }}</textarea>
            <x-input-error :messages="$errors->get('notes')" class="mt-1" />
        </div>
    </section>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-6 border-t border-gray-100">
        <a href="{{ $isCreate ? route('supplies.index') : route('supplies.show', $supply) }}"
           class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
        <button type="submit" class="inline-flex justify-center rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm" style="background-color:#1a6b3c;">
            {{ $isCreate ? 'Add Item' : 'Save Changes' }}
        </button>
    </div>
</form>
