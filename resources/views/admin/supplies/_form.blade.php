{{--
    Shared by New Item and Edit Item.
    Expects: $supply (new Supply with defaults on create), $branches, $action, $method ('POST' | 'PUT'), $mode ('create' | 'edit')
--}}
@php
    $isCreate = $mode === 'create';
    $field = 'block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700';

    // Alpine state (see supplyForm() below): the brand picker depends on the category, and the
    // critical level follows half the reorder level until someone sets it by hand.
    // Examples shown in the Model box, so each category suggests things that make sense for it.
    $modelHints = [
        'mouse' => 'e.g. M185', 'keyboard' => 'e.g. K120', 'usb_storage' => 'e.g. DataTraveler 32GB',
        'telephone' => 'e.g. KX-TS500, T31P',
    ];
    $startReorder = (int) old('reorder_level', $supply->reorder_level);
    $startCritical = (int) old('critical_level', $isCreate ? intdiv($startReorder, 2) : $supply->critical_level);
    // On edit the saved value is respected; on create it follows the reorder level until typed over.
    $criticalTouched = $isCreate ? old('critical_level') !== null : true;

    $formConfig = [
        'category' => (string) old('category', $supply->category),
        'brand' => (string) old('brand', $supply->brand),
        'reorder' => $startReorder,
        'critical' => $startCritical,
        'criticalTouched' => $criticalTouched,
        'modelHints' => $modelHints,
        'brandOptions' => \App\Models\Supply::brandOptions(),
        'printerBrands' => \App\Models\Supply::PRINTER_BRANDS,
    ];
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

<script>
    // State for the whole supply form: category, stock-warning levels and the brand picker.
    //
    // Brand picker: a dropdown of known brands for the category plus "Other brand…", which reveals a
    // text box. The form submits ONE value (the hidden `brand` input); the server then tidies it
    // (spacing, capitalisation) so "logitech" and "Logitech" can't become two different brands.
    function supplyForm(cfg) {
        const OTHER = '__other__';
        const key = (text) => String(text || '').toLowerCase().replace(/[^\p{L}\p{N}]+/gu, '');

        return {
            OTHER,
            category: cfg.category,
            reorder: cfg.reorder,
            critical: cfg.critical,
            criticalTouched: cfg.criticalTouched,
            modelHints: cfg.modelHints,
            choice: '',      // what the dropdown shows: '' (none), a listed brand, or OTHER
            custom: '',      // the text typed under "Other brand…"

            init() {
                this.setBrand(cfg.brand);

                // Switching category swaps the list of brands. A brand the person typed is kept; one they
                // picked from the old list is cleared if the new category doesn't offer it.
                this.$watch('category', () => {
                    if (this.choice === OTHER) return;
                    const match = this.options.find(o => key(o) === key(this.choice));
                    this.choice = match || '';
                });
            },

            get isInk() { return this.category === 'ink_toner'; },
            get options() { return cfg.brandOptions[this.category] || []; },

            /** The one value that is submitted. */
            get brand() { return (this.choice === OTHER ? this.custom : this.choice).trim(); },

            /** Ink & toner for a brand other than the company's printers: needs a note saying which printer. */
            get offList() {
                return this.isInk && this.brand !== '' && !cfg.printerBrands.some(p => key(p) === key(this.brand));
            },

            setBrand(value) {
                value = (value || '').trim();
                const match = this.options.find(o => key(o) === key(value));

                if (value === '') { this.choice = ''; this.custom = ''; }
                else if (match) { this.choice = match; this.custom = ''; }
                else { this.choice = OTHER; this.custom = value; }
            },
        };
    }

    // Photo picker for the supply form. A photo can be chosen, dropped, or pasted (Ctrl+V / the Paste
    // button) — all three end up in use(). Phone photos are often 4–8 MB, so the picture is shrunk in the
    // browser (max 1200px, JPEG) before it is uploaded; the server resizes again and strips metadata.
    function supplyPhoto(existing) {
        return {
            preview: existing || null,
            removed: false,
            note: '',
            error: '',
            objectUrl: null,
            canPasteButton: !!(navigator.clipboard && navigator.clipboard.read),   // needs a secure (https) page

            async picked(event) {
                const file = event.target.files[0];
                if (file) await this.use(file);
            },
            async dropped(event) {
                const file = event.dataTransfer.files[0];
                if (file) await this.use(file);
            },
            /** Ctrl+V / Cmd+V anywhere on the page: use a copied picture or screenshot, if there is one. */
            async pasted(event) {
                const items = [...((event.clipboardData && event.clipboardData.items) || [])];
                const picture = items.find(i => i.kind === 'file' && i.type.startsWith('image/'));
                if (!picture) return;                                    // plain text etc.: paste as normal

                // Copying cells or text from Word/Excel puts a picture on the clipboard as well as the text.
                // Pasting that into a text box should paste the text, not swap the photo.
                const hasText = items.some(i => i.kind === 'string' && i.type === 'text/plain');
                if (hasText && event.target.closest('input, textarea, [contenteditable="true"]')) return;

                const file = picture.getAsFile();
                if (!file) return;
                event.preventDefault();
                await this.use(this.named(file));
            },
            /** The "Paste" button — for phones and anyone who prefers clicking. */
            async pasteFromClipboard() {
                this.error = '';
                try {
                    for (const entry of await navigator.clipboard.read()) {
                        const type = entry.types.find(t => t.startsWith('image/'));
                        if (type) { await this.use(this.named(await entry.getType(type))); return; }
                    }
                    this.error = 'There is no picture on the clipboard. Copy a picture first, then try again.';
                } catch (e) {
                    this.error = 'Your browser did not allow pasting from the clipboard. Click on the page and press Ctrl+V instead.';
                }
            },
            /** Pasted pictures have no real file name, so give them a sensible one. */
            named(blob) {
                const ext = { 'image/png': 'png', 'image/jpeg': 'jpg', 'image/webp': 'webp' }[blob.type] || 'png';
                return new File([blob], 'pasted-photo.' + ext, { type: blob.type });
            },
            async use(file) {
                this.error = '';
                this.note = '';

                if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
                    this.error = 'Please choose a JPG, PNG or WebP photo.';
                    this.$refs.file.value = '';
                    return;
                }

                let out = file;
                try { out = await this.shrink(file); } catch (e) { /* keep the original — the server copes */ }

                if (out.size > 8 * 1024 * 1024) {
                    this.error = 'That photo is over 8 MB. Please choose a smaller one.';
                    this.$refs.file.value = '';
                    return;
                }

                try {
                    const transfer = new DataTransfer();
                    transfer.items.add(out);
                    this.$refs.file.files = transfer.files;
                } catch (e) { /* very old browser: the originally chosen file is still attached */ }

                if (this.objectUrl) URL.revokeObjectURL(this.objectUrl);
                this.objectUrl = URL.createObjectURL(out);
                this.preview = this.objectUrl;
                this.removed = false;
                this.note = out === file ? file.name : file.name + ' — resized to ' + Math.max(1, Math.round(out.size / 1024)) + ' KB';
            },
            async shrink(file) {
                const max = 1200;
                let bitmap;
                try { bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' }); }
                catch (e) { bitmap = await createImageBitmap(file); }

                const scale = Math.min(1, max / Math.max(bitmap.width, bitmap.height));
                if (scale === 1 && file.type === 'image/jpeg' && file.size < 1.5 * 1024 * 1024) {
                    if (bitmap.close) bitmap.close();
                    return file;                                   // already small — leave it alone
                }

                const canvas = document.createElement('canvas');
                canvas.width = Math.round(bitmap.width * scale);
                canvas.height = Math.round(bitmap.height * scale);
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#fff';                            // transparent PNGs land on white
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
                if (bitmap.close) bitmap.close();

                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.86));
                if (!blob) return file;
                return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
            },
            clear() {
                this.$refs.file.value = '';
                if (this.objectUrl) URL.revokeObjectURL(this.objectUrl);
                this.objectUrl = null;
                this.preview = null;
                this.note = '';
                this.error = '';
                this.removed = !!existing;                         // tell the server to delete the saved photo
            },
        };
    }
</script>

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-8" novalidate
      x-data="supplyForm(@js($formConfig))">
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
                <label for="brand-choice" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Brand <span class="font-normal text-gray-400" x-show="!isInk">(optional)</span>
                </label>

                {{-- The one value that is saved; the controls below just fill it in. --}}
                <input type="hidden" name="brand" :value="brand" value="{{ old('brand', $supply->brand) }}">

                <select id="brand-choice" x-model="choice"
                        @change="if (choice === OTHER) $nextTick(() => $refs.customBrand.focus())"
                        class="{{ $field }}">
                    <option value="" x-text="isInk ? 'Choose a brand…' : 'No brand'">No brand</option>
                    <template x-for="option in options" :key="option">
                        <option :value="option" :selected="option === choice" x-text="option"></option>
                    </template>
                    <option :value="OTHER" :selected="choice === OTHER">Other brand…</option>
                </select>

                <div x-show="choice === OTHER" x-cloak class="mt-2">
                    <label for="brand-custom" class="sr-only">Brand name</label>
                    <input id="brand-custom" type="text" x-ref="customBrand" x-model="custom" maxlength="100" autocomplete="off"
                           placeholder="Type the brand name" class="{{ $field }}">
                </div>

                <p x-show="isInk && !offList" x-cloak class="text-xs text-gray-400 mt-1">
                    Brother and Epson are the company's printers. Using something else? Choose "Other brand…".
                </p>

                {{-- Not an error: another brand is fine, it just needs to say which printer it is for. --}}
                <div x-show="offList" x-cloak role="status"
                     class="mt-2 flex gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs leading-relaxed text-amber-800">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                    <p>
                        <span class="font-semibold" x-text="brand"></span> isn't one of the company's printer brands (Brother, Epson).
                        That's fine &mdash; just say which printer it's for in the <strong>Notes</strong> below.
                    </p>
                </div>

                <x-input-error :messages="$errors->get('brand')" class="mt-1" />
            </div>
            <div>
                <label for="model_no" class="block text-sm font-medium text-gray-700 mb-1.5">
                    <span x-text="isInk ? 'Cartridge / Model No.' : 'Model / Part No.'">Model / Part No.</span>
                    <span class="font-normal text-gray-400">(optional)</span>
                </label>
                <input id="model_no" type="text" name="model_no" value="{{ old('model_no', $supply->model_no) }}" maxlength="100"
                       :placeholder="isInk ? 'e.g. TN-2380, 003 / T00V100' : (modelHints[category] || 'e.g. model or part number')" class="{{ $field }}">
                <x-input-error :messages="$errors->get('model_no')" class="mt-1" />
            </div>
        </div>

        {{-- Photo: lets staff confirm they are requesting the right item --}}
        <div x-data="supplyPhoto(@js($supply->image_url))" @dragover.prevent @drop.prevent="dropped($event)" @paste.window="pasted($event)">
            <p class="block text-sm font-medium text-gray-700 mb-1.5">Photo <span class="font-normal text-gray-400">(optional)</span></p>

            <input type="hidden" name="remove_image" :value="removed ? 1 : 0">
            <input x-ref="file" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="-1" @change="picked($event)">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <button type="button" @click="$refs.file.click()" aria-label="Choose a photo"
                        class="group relative flex h-36 w-36 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 transition hover:border-green-700 hover:bg-green-50/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700">
                    <template x-if="preview">
                        <img :src="preview" alt="Photo preview" class="h-full w-full object-cover">
                    </template>
                    <template x-if="!preview">
                        <span class="flex flex-col items-center gap-1.5 px-2 text-center text-xs text-gray-400 group-hover:text-green-800">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg>
                            Tap, drop or paste a photo
                        </span>
                    </template>
                </button>

                <div class="min-w-0 space-y-2">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="$refs.file.click()"
                                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                            <span x-text="preview ? 'Change photo' : 'Add photo'">Add photo</span>
                        </button>
                        <button type="button" x-show="canPasteButton" x-cloak @click="pasteFromClipboard()"
                                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Paste from clipboard</button>
                        <button type="button" x-show="preview" x-cloak @click="clear()"
                                class="inline-flex items-center rounded-lg px-3.5 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Remove</button>
                    </div>
                    <p class="text-xs text-gray-400">A clear photo of the item helps staff pick the right one when they request it. JPG, PNG or WebP — large photos are shrunk automatically.</p>
                    <p class="text-xs text-gray-400">Tip: copy a picture or take a screenshot, then press <kbd class="rounded border border-gray-300 bg-gray-50 px-1 py-0.5 font-sans text-[11px] text-gray-600">Ctrl</kbd> + <kbd class="rounded border border-gray-300 bg-gray-50 px-1 py-0.5 font-sans text-[11px] text-gray-600">V</kbd> (<kbd class="rounded border border-gray-300 bg-gray-50 px-1 py-0.5 font-sans text-[11px] text-gray-600">&#8984;V</kbd> on a Mac) anywhere on this page.</p>
                    <p x-show="note" x-cloak x-text="note" class="text-xs text-gray-500"></p>
                    <p x-show="error" x-cloak x-text="error" class="text-xs font-medium text-rose-600" role="alert"></p>
                    <p x-show="removed && !preview" x-cloak class="text-xs text-amber-700">The current photo will be removed when you save.</p>
                </div>
            </div>
            <x-input-error :messages="$errors->get('image')" class="mt-1.5" />
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
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1.5">
                Notes
                <span x-show="!offList" class="font-normal text-gray-400">(optional)</span>
                <span x-show="offList" x-cloak class="font-medium text-amber-700">(required &mdash; which printer is this for?)</span>
            </label>
            <textarea id="notes" name="notes" rows="3" maxlength="1000" class="{{ $field }}"
                      :class="offList && 'border-amber-400 bg-amber-50/40'"
                      :placeholder="offList ? 'e.g. For the Accounting HP LaserJet Pro M404' : 'Where it\'s kept, compatible printers, supplier, etc.'"
                      placeholder="Where it's kept, compatible printers, supplier, etc.">{{ old('notes', $supply->notes) }}</textarea>
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
