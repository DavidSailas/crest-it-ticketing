<form method="GET" action="{{ route('reports.index') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-3.5 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Reporting period</label>
        <select name="range" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 min-w-[11rem]">
            @foreach($ranges as $value => $label)
                <option value="{{ $value }}" @selected($selectedRange === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <p class="text-sm text-gray-400 pb-2">{{ $rangeLabel }}</p>
</form>
