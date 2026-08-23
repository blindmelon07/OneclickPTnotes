@props([
    'icon' => null,
    'label',
    'value',
    'color' => 'zinc',
    'href' => null,
])

@php
    $iconColorClass = match ($color) {
        'green' => 'text-green-500',
        'red' => 'text-red-500',
        'amber' => 'text-amber-500',
        'blue' => 'text-blue-500',
        'purple' => 'text-purple-500',
        default => 'text-zinc-400',
    };
@endphp

<div {{ $attributes->class(['relative overflow-hidden rounded-xl border border-zinc-200 p-4 dark:border-zinc-700']) }}>
    <div class="flex items-start justify-between gap-2">
        <div>
            <flux:text class="text-zinc-500">{{ $label }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $value }}</flux:heading>
        </div>

        @if ($icon)
            <flux:icon :name="$icon" class="size-8 shrink-0 {{ $iconColorClass }}" />
        @endif
    </div>

    @isset($href)
        <flux:link :href="$href" class="mt-3 inline-block text-sm" wire:navigate>{{ __('View') }} &rarr;</flux:link>
    @endisset
</div>
