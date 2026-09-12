@props(['name' => 'image', 'existingUrl' => null, 'removeFieldName' => 'remove_image'])

<div x-data="{
        fileName: null,
        previewUrl: '{{ $existingUrl ?? '' }}',
        hasExisting: {{ $existingUrl ? 'true' : 'false' }},
        removed: false,
        onFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.fileName = file.name;
            this.previewUrl = URL.createObjectURL(file);
            this.removed = false;
        },
        clear(input) {
            input.value = '';
            this.fileName = null;
            this.previewUrl = '';
            this.removed = true;
        }
    }">
    <label class="block text-sm font-medium text-gray-700 mb-1">Attach a screenshot <span class="text-gray-400 font-normal">(optional)</span></label>

    @if($removeFieldName)
        <input type="hidden" name="{{ $removeFieldName }}" :value="removed ? 1 : 0">
    @endif

    <div class="relative">
        <label
            class="flex flex-col items-center justify-center gap-2 border-2 border-dashed rounded-lg py-6 px-4 text-center cursor-pointer transition border-gray-300 hover:border-green-600 hover:bg-green-50/40"
            x-show="!previewUrl"
        >
            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16.5V18a2 2 0 002 2h12a2 2 0 002-2v-1.5M7 10l5-5m0 0l5 5m-5-5v12" />
            </svg>
            <span class="text-sm text-gray-500">Click to upload, or drag and drop</span>
            <span class="text-xs text-gray-400">PNG or JPG, up to 5MB</span>
            <input type="file" name="{{ $name }}" accept="image/*" class="hidden" @change="onFile($event)" x-ref="fileInput">
        </label>

        <div x-show="previewUrl" x-cloak class="relative rounded-lg overflow-hidden border border-gray-200">
            <img :src="previewUrl" class="max-h-56 w-full object-contain bg-gray-50">
            <button type="button"
                class="absolute top-2 right-2 bg-white/90 hover:bg-white text-gray-600 rounded-full p-1.5 shadow-sm"
                @click="clear($refs.fileInput)">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <p class="text-xs text-gray-500 px-3 py-2 bg-white border-t border-gray-100" x-text="fileName || 'Current attachment'"></p>
        </div>
    </div>
    @error($name) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>
