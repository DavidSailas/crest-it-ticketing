{{--
    Crop window for the profile photo.

    Lives inside the avatar <form> (see profile/edit.blade.php), whose x-data is avatarCropper().
    Choosing or pasting a photo opens this window instead of uploading straight away: the person
    drags / zooms / rotates the picture under a circular guide (exactly how the avatar is shown),
    and "Save photo" uploads the cropped 512 × 512 JPEG. No library needed — it is a canvas.
--}}
<script>
    function avatarCropper() {
        const OUT = 512;          // the saved picture is OUT x OUT pixels
        const MAX_ZOOM = 4;
        const TYPES = /^image\/(jpeg|png|webp)$/;

        // Kept out of the reactive state on purpose: Alpine would wrap them in a Proxy,
        // and the browser's canvas refuses to draw a proxied ImageBitmap.
        let bmp = null;
        const pointers = new Map();
        let pinchStart = 0;

        const clamp = (n, lo, hi) => Math.min(hi, Math.max(lo, n));

        return {
            open: false,
            ready: false,
            saving: false,
            error: '',
            rot: 0,           // quarter turns, clockwise
            zoom: 1,          // 1 = the picture just covers the square
            cx: 0, cy: 0,     // how far the picture centre is from the square's centre (CSS px)
            size: 300,        // the on-screen square's side (CSS px)

            /* ---------- getting a picture in ---------- */

            async picked(event) {
                await this.begin(event.target.files[0]);
            },

            /** Ctrl+V / Cmd+V: a copied picture or screenshot opens the same crop window. */
            async pasted(event) {
                const items = [...((event.clipboardData && event.clipboardData.items) || [])];
                const picture = items.find(i => i.kind === 'file' && i.type.startsWith('image/'));
                if (!picture) return;                                   // plain text etc.: paste as normal

                // Copied text/cells often carry a picture too; in a text box, paste the text.
                const hasText = items.some(i => i.kind === 'string' && i.type === 'text/plain');
                if (hasText && event.target.closest('input, textarea, select, [contenteditable="true"]')) return;

                const file = picture.getAsFile();
                if (!file) return;
                event.preventDefault();
                await this.begin(file);
            },

            async begin(file) {
                this.error = '';
                if (!file) return;

                if (!TYPES.test(file.type)) {
                    this.error = 'Please choose a JPG, PNG or WebP photo.';
                    this.$refs.input.value = '';
                    return;
                }

                // Very old browser: no cropping possible, upload the photo as it is.
                if (typeof createImageBitmap !== 'function') {
                    if (this.$refs.input.files[0] !== file) {
                        try { const t = new DataTransfer(); t.items.add(file); this.$refs.input.files = t.files; } catch (e) { return; }
                    }
                    this.$refs.form.submit();
                    return;
                }

                try {
                    try { bmp = await createImageBitmap(file, { imageOrientation: 'from-image' }); }
                    catch (e) { bmp = await createImageBitmap(file); }
                } catch (e) {
                    this.error = "That file couldn't be read as a picture. Please try a JPG, PNG or WebP photo.";
                    this.$refs.input.value = '';
                    return;
                }

                this.rot = 0; this.zoom = 1; this.cx = 0; this.cy = 0;
                this.ready = true;
                this.open = true;
                await this.$nextTick();
                this.measure();
                this.$refs.stage.focus();
            },

            cancel() {
                this.open = false;
                this.ready = false;
                this.saving = false;
                if (bmp && bmp.close) bmp.close();
                bmp = null;
                pointers.clear();
                this.$refs.input.value = '';          // so choosing the same file again still works
            },

            /* ---------- geometry ---------- */

            /** Width and height of the picture as it is shown (swapped after a quarter turn). */
            dims() { return this.rot % 2 ? [bmp.height, bmp.width] : [bmp.width, bmp.height]; },

            /** Scale at which the picture just covers the square — it never shows an empty gap. */
            scale() { const [w, h] = this.dims(); return (this.size / Math.min(w, h)) * this.zoom; },

            /** Keep the picture covering the whole square. */
            fit() {
                const [w, h] = this.dims();
                const s = this.scale();
                const mx = Math.max(0, (w * s - this.size) / 2);
                const my = Math.max(0, (h * s - this.size) / 2);
                this.cx = clamp(this.cx, -mx, mx);
                this.cy = clamp(this.cy, -my, my);
            },

            /** Paint the current crop onto a canvas context of `target` x `target` pixels. */
            draw(ctx, target) {
                const k = target / this.size;
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.fillStyle = '#fff';                                   // transparent PNGs land on white
                ctx.fillRect(0, 0, target, target);
                ctx.translate(target / 2 + this.cx * k, target / 2 + this.cy * k);
                ctx.rotate(this.rot * Math.PI / 2);
                const s = this.scale() * k;
                ctx.scale(s, s);
                ctx.drawImage(bmp, -bmp.width / 2, -bmp.height / 2);
            },

            render() {
                if (!bmp || !this.$refs.canvas) return;
                const ctx = this.$refs.canvas.getContext('2d');
                ctx.imageSmoothingQuality = 'high';
                this.draw(ctx, this.$refs.canvas.width);
            },

            /** Match the canvas to the square's on-screen size (also on window resize / phone rotation). */
            measure() {
                if (!bmp) return;
                const before = this.size;
                const width = Math.round(this.$refs.stage.clientWidth);
                if (width > 0) this.size = width;          // 0 = not measurable right now: keep the last good size
                const dpr = Math.min(window.devicePixelRatio || 1, 2);
                this.$refs.canvas.width = this.$refs.canvas.height = Math.round(this.size * dpr);
                const r = this.size / before;
                this.cx *= r; this.cy *= r;
                this.fit();
                this.render();
            },

            /* ---------- the controls ---------- */

            setZoom(value) {
                if (!bmp) return;
                const z = clamp(Number(value) || 1, 1, MAX_ZOOM);
                const r = z / this.zoom;
                this.zoom = z;
                this.cx *= r; this.cy *= r;                // zoom around the middle of the square
                this.fit();
                this.render();
            },

            rotate(direction) {
                if (!bmp) return;
                this.rot = (this.rot + direction + 4) % 4;
                this.cx = 0; this.cy = 0;
                this.fit();
                this.render();
            },

            reset() {
                if (!bmp) return;
                this.rot = 0; this.zoom = 1; this.cx = 0; this.cy = 0;
                this.render();
            },

            wheel(event) { this.setZoom(this.zoom * Math.exp(-event.deltaY * 0.0015)); },

            down(event) {
                if (event.currentTarget.setPointerCapture) event.currentTarget.setPointerCapture(event.pointerId);
                pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
                if (pointers.size === 2) pinchStart = this.pinchDistance();
            },

            move(event) {
                const p = pointers.get(event.pointerId);
                if (!p || !bmp) return;
                const dx = event.clientX - p.x, dy = event.clientY - p.y;
                p.x = event.clientX; p.y = event.clientY;

                if (pointers.size === 1) {                                  // one finger / mouse: move the picture
                    this.cx += dx; this.cy += dy;
                    this.fit();
                    this.render();
                } else if (pointers.size === 2) {                           // two fingers: pinch to zoom
                    const now = this.pinchDistance();
                    if (pinchStart > 0) this.setZoom(this.zoom * now / pinchStart);
                    pinchStart = now;
                }
            },

            up(event) { pointers.delete(event.pointerId); pinchStart = 0; },

            pinchDistance() {
                const [a, b] = [...pointers.values()];
                return a && b ? Math.hypot(a.x - b.x, a.y - b.y) : 0;
            },

            /** Keyboard: arrows move the picture, + / − zoom. */
            key(event) {
                const step = 12;
                const moves = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] };
                if (moves[event.key]) {
                    this.cx += moves[event.key][0]; this.cy += moves[event.key][1];
                    this.fit(); this.render();
                } else if (event.key === '+' || event.key === '=') this.setZoom(this.zoom * 1.1);
                else if (event.key === '-' || event.key === '_') this.setZoom(this.zoom / 1.1);
                else return;
                event.preventDefault();
            },

            /* ---------- saving ---------- */

            async save() {
                if (!bmp || this.saving) return;
                this.saving = true;

                const canvas = document.createElement('canvas');
                canvas.width = canvas.height = OUT;
                const ctx = canvas.getContext('2d');
                ctx.imageSmoothingQuality = 'high';
                this.draw(ctx, OUT);

                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.9));
                if (!blob) { this.fail("The photo couldn't be prepared. Please try again."); return; }

                try {
                    const transfer = new DataTransfer();
                    transfer.items.add(new File([blob], 'avatar.jpg', { type: 'image/jpeg' }));
                    this.$refs.input.files = transfer.files;
                } catch (e) {
                    this.fail("Your browser couldn't attach the cropped photo. Please try another browser.");
                    return;
                }

                this.$refs.form.submit();
            },

            fail(message) {
                this.cancel();
                this.error = message;
            },
        };
    }
</script>

{{-- Stops the page behind from scrolling while the window is open. --}}
<span hidden x-effect="document.body.classList.toggle('overflow-hidden', open)"></span>

{{-- Alpine only runs x-teleport inside an x-data scope — this lives inside the avatar form, which has one. --}}
<template x-teleport="body">
    <div x-show="open" x-cloak style="display: none;"
         x-transition.opacity.duration.150ms
         class="fixed inset-0 z-[100] flex items-end justify-center bg-gray-900/60 sm:items-center sm:p-4"
         role="dialog" aria-modal="true" aria-labelledby="avatar-crop-title"
         @keydown.escape.window="open && cancel()" @resize.window="open && measure()">

        <div class="w-full max-w-md overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
            <div class="px-5 pt-5 sm:px-6">
                <h3 id="avatar-crop-title" class="text-base font-semibold text-gray-900">Adjust your photo</h3>
                <p class="mt-0.5 text-sm text-gray-500">Drag to move, and zoom until it looks right. The circle is what people will see.</p>
            </div>

            {{-- The picture, with a circular guide: everything outside the circle is dimmed. --}}
            <div class="px-5 pt-4 sm:px-6">
                <div x-ref="stage" tabindex="0" role="application"
                     aria-label="Photo position. Drag to move it, or use the arrow keys. Plus and minus zoom."
                     class="relative mx-auto aspect-square w-full max-w-[320px] cursor-grab touch-none select-none overflow-hidden rounded-xl bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700 active:cursor-grabbing"
                     @pointerdown="down($event)" @pointermove="move($event)" @pointerup="up($event)" @pointercancel="up($event)"
                     @wheel.prevent="wheel($event)" @keydown="key($event)">
                    <canvas x-ref="canvas" class="absolute inset-0 h-full w-full"></canvas>
                    <div class="pointer-events-none absolute inset-0 rounded-full"
                         style="box-shadow: 0 0 0 999px rgba(17, 24, 39, .62); border: 2px solid rgba(255, 255, 255, .95);"></div>
                </div>
            </div>

            <div class="space-y-3 px-5 pt-4 sm:px-6">
                {{-- Zoom --}}
                <div class="flex items-center gap-3">
                    <button type="button" @click="setZoom(zoom / 1.15)" aria-label="Zoom out"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-800">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                    </button>
                    <input type="range" min="1" max="4" step="0.01" :value="zoom" @input="setZoom($event.target.value)"
                           aria-label="Zoom" class="h-2 w-full flex-1 cursor-pointer accent-green-700">
                    <button type="button" @click="setZoom(zoom * 1.15)" aria-label="Zoom in"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-800">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    </button>
                </div>

                {{-- Rotate / reset --}}
                <div class="flex items-center justify-center gap-1.5">
                    <button type="button" @click="rotate(-1)"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100">
                        <svg class="h-4 w-4 -scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        Rotate left
                    </button>
                    <button type="button" @click="rotate(1)"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        Rotate right
                    </button>
                    <button type="button" @click="reset()"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-100">Reset</button>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-end gap-2 border-t border-gray-100 bg-gray-50/60 px-5 py-3.5 sm:px-6">
                <button type="button" @click="cancel()" :disabled="saving"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50">Cancel</button>
                <button type="button" @click="save()" :disabled="saving || !ready"
                        class="rounded-lg bg-green-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-700 focus-visible:ring-offset-2 disabled:opacity-60"
                        x-text="saving ? 'Saving…' : 'Save photo'">Save photo</button>
            </div>
        </div>
    </div>
</template>
