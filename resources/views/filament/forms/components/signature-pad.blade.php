@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="signaturePadState(@js($statePath), @js($getState() ?? ''))"
        x-init="init()"
        class="space-y-2"
    >
        <div
            class="relative border border-gray-300 rounded-lg overflow-hidden bg-white"
            style="width: 100%; max-width: 500px; height: 200px;"
        >
            <canvas
                x-ref="canvas"
                x-on:mousedown="startDraw"
                x-on:mousemove="draw"
                x-on:mouseup="endDraw"
                x-on:mouseleave="endDraw"
                x-on:touchstart.prevent="startDrawTouch"
                x-on:touchmove.prevent="drawTouch"
                x-on:touchend="endDraw"
                class="cursor-crosshair"
                style="width: 100%; height: 100%;"
            ></canvas>
        </div>

        <div class="flex gap-2">
            <button
                type="button"
                x-on:click="clearSignature()"
                class="filament-button filament-button-size-xs inline-flex items-center justify-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
                Limpiar firma
            </button>
        </div>

        <template x-if="signatureData">
            <p class="text-xs text-green-600">Firma capturada correctamente.</p>
        </template>
    </div>
</x-dynamic-component>

@push('scripts')
<script>
    function signaturePadState(statePath, initialData) {
        return {
            signatureData: initialData || null,
            isDrawing: false,
            ctx: null,

            init() {
                const canvas = this.$refs.canvas;
                if (!canvas) return;
                canvas.width = canvas.offsetWidth;
                canvas.height = canvas.offsetHeight;
                this.ctx = canvas.getContext('2d');
                this.ctx.strokeStyle = '#1f2937';
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';

                if (this.signatureData) {
                    const img = new Image();
                    img.onload = () => {
                        this.ctx.drawImage(img, 0, 0);
                    };
                    img.src = this.signatureData;
                }
            },

            getPos(e) {
                const canvas = this.$refs.canvas;
                const rect = canvas.getBoundingClientRect();
                return {
                    x: (e.clientX - rect.left) * (canvas.width / rect.width),
                    y: (e.clientY - rect.top) * (canvas.height / rect.height),
                };
            },

            startDraw(e) {
                this.isDrawing = true;
                const pos = this.getPos(e);
                this.ctx.beginPath();
                this.ctx.moveTo(pos.x, pos.y);
            },

            draw(e) {
                if (!this.isDrawing) return;
                const pos = this.getPos(e);
                this.ctx.lineTo(pos.x, pos.y);
                this.ctx.stroke();
            },

            endDraw() {
                if (!this.isDrawing) return;
                this.isDrawing = false;
                this.signatureData = this.$refs.canvas.toDataURL('image/png');
                this.$wire.set(statePath, this.signatureData);
            },

            startDrawTouch(e) {
                this.isDrawing = true;
                const touch = e.touches[0];
                const pos = this.getPos(touch);
                this.ctx.beginPath();
                this.ctx.moveTo(pos.x, pos.y);
            },

            drawTouch(e) {
                if (!this.isDrawing) return;
                const touch = e.touches[0];
                const pos = this.getPos(touch);
                this.ctx.lineTo(pos.x, pos.y);
                this.ctx.stroke();
            },

            clearSignature() {
                const canvas = this.$refs.canvas;
                if (!canvas) return;
                this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                this.signatureData = null;
                this.$wire.set(statePath, null);
            },
        };
    }
</script>
@endpush
