{{-- The measures compared against the IE for $form ('re' / 'dc'), with $statuses and the $currentLabel column heading. --}}
@php($model = \App\Models\OutpatientForm::class)

<flux:heading size="lg">{{ __('Progress against the IE') }}</flux:heading>
<flux:text>{{ __('The baseline copies the Initial Evaluation unless you type a different one. A row is ticked on the form once it has a value or status.') }}</flux:text>

<div class="space-y-3">
    @foreach ($model::progressRows() as $row => $label)
        <div class="grid gap-3 border-b border-zinc-200 pb-3 sm:grid-cols-[8rem_1fr_1fr_auto] sm:items-end dark:border-zinc-700" wire:key="{{ $form }}-progress-{{ $row }}">
            @if ($row === 'others')
                <flux:input wire:model="data.{{ $form }}.others_label" :label="__('Others')" :placeholder="__('Measure')" size="sm" />
            @else
                <flux:label class="sm:pb-2">{{ __($label) }}</flux:label>
            @endif

            <flux:input
                wire:model="data.{{ $form }}.progress.{{ $row }}.baseline"
                :label="__('Baseline (IE)')"
                :placeholder="$preview->initialValue($row)"
                size="sm"
            />

            <div class="space-y-2">
                <flux:input wire:model="data.{{ $form }}.progress.{{ $row }}.value" :label="__($currentLabel)" size="sm" />
                @if ($row === 'mmt')
                    <div class="grid grid-cols-2 gap-2">
                        <flux:input wire:model="data.{{ $form }}.grip.right" :label="__('Grip R (lbs)')" size="sm" />
                        <flux:input wire:model="data.{{ $form }}.grip.left" :label="__('Grip L (lbs)')" size="sm" />
                    </div>
                @endif
            </div>

            <flux:select wire:model="data.{{ $form }}.progress.{{ $row }}.status" :label="__('Status')" size="sm" class="sm:w-36">
                <flux:select.option value="">{{ __('—') }}</flux:select.option>
                @foreach ($statuses as $status)
                    <flux:select.option :value="$status">{{ $status }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    @endforeach
</div>
