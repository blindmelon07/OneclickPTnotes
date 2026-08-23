@props(['model'])

<div
    wire:ignore
    x-data="{
        drawing: false,
        ctx: null,
        init() {
            const canvas = this.$refs.canvas;
            const ratio = window.devicePixelRatio || 1;
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            this.ctx = canvas.getContext('2d');
            this.ctx.scale(ratio, ratio);
            this.ctx.lineWidth = 2;
            this.ctx.lineCap = 'round';
            this.ctx.strokeStyle = '#111827';
        },
        point(e) {
            const canvas = this.$refs.canvas;
            const rect = canvas.getBoundingClientRect();
            const touch = e.touches ? e.touches[0] : e;
            return { x: touch.clientX - rect.left, y: touch.clientY - rect.top };
        },
        start(e) {
            this.drawing = true;
            const p = this.point(e);
            this.ctx.beginPath();
            this.ctx.moveTo(p.x, p.y);
        },
        move(e) {
            if (! this.drawing) return;
            e.preventDefault();
            const p = this.point(e);
            this.ctx.lineTo(p.x, p.y);
            this.ctx.stroke();
        },
        end() {
            if (! this.drawing) return;
            this.drawing = false;
            $wire.set('{{ $model }}', this.$refs.canvas.toDataURL('image/png'));
        },
        clear() {
            const canvas = this.$refs.canvas;
            this.ctx.clearRect(0, 0, canvas.width, canvas.height);
            $wire.set('{{ $model }}', null);
        },
    }"
    x-init="init()"
    {{ $attributes }}
>
    <canvas
        x-ref="canvas"
        class="h-40 w-full touch-none rounded-md border border-zinc-300 bg-white dark:border-zinc-600"
        x-on:mousedown="start($event)"
        x-on:mousemove="move($event)"
        x-on:mouseup="end()"
        x-on:mouseleave="end()"
        x-on:touchstart="start($event)"
        x-on:touchmove="move($event)"
        x-on:touchend="end()"
    ></canvas>

    <div class="mt-2 flex justify-end">
        <flux:button type="button" size="sm" variant="ghost" x-on:click="clear()">{{ __('Clear signature') }}</flux:button>
    </div>
</div>
