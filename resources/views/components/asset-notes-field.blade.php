@props(['value' => '', 'max' => 1000])

@php $startValue = (string) $value; @endphp

{{-- Notes box for asset forms: roomy, resizable, with a live character counter.
     The counter counts a line break as 2 characters because that is how the
     browser submits it, so it matches the server-side 1000-character limit. --}}
<div x-data='{
        text: @json($startValue, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
        max: {{ (int) $max }},
        get used() { return this.text.replace(/\r?\n/g, "\r\n").length; },
        get over() { return this.used > this.max; },
     }'>
    <div class="flex items-baseline justify-between gap-3 mb-1.5">
        <label for="asset-notes" class="text-sm font-medium text-gray-700">
            Notes <span class="font-normal text-gray-400">(optional)</span>
        </label>
        <span class="text-xs tabular-nums transition-colors"
              :class="over ? 'text-red-600 font-semibold' : (used >= max * 0.9 ? 'text-amber-600' : 'text-gray-400')"
              x-text="over ? ('Over by ' + (used - max)) : (used + ' / ' + max)"></span>
    </div>

    <textarea id="asset-notes" name="notes" rows="4" x-model="text"
              placeholder="e.g. Minor scratch on the lid, comes with charger and carry case, warranty until Dec 2027..."
              class="block w-full rounded-lg border-gray-300 bg-gray-50/60 text-sm leading-relaxed placeholder-gray-400 resize-y focus:bg-white focus:border-green-700 focus:ring-green-700"
              :class="over ? '!border-red-400 focus:!border-red-500 focus:!ring-red-500' : ''"
              style="min-height:6.5rem;">{{ $startValue }}</textarea>

    <p class="text-xs mt-1.5" :class="over ? 'text-red-600' : 'text-gray-400'"
       x-text="over ? 'Please shorten your notes — the limit is ' + max + ' characters.' : 'Condition, accessories, warranty, repairs — anything the next person should know. Drag the corner to resize.'"></p>
</div>
