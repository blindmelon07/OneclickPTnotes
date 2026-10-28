{{-- The PT recommendation shared by the RE and DC, for $form ('re' / 'dc'). --}}
@php($model = \App\Models\OutpatientForm::class)

<flux:radio.group wire:model.live="data.{{ $form }}.recommendation" :label="__('PT recommendation')">
    @foreach ($model::recommendations() as $value => $label)
        <flux:radio :value="$value" :label="__($label)" />
    @endforeach
</flux:radio.group>

@switch($data[$form]['recommendation'])
    @case($model::RECOMMENDATION_CONTINUE)
        <flux:input wire:model="data.{{ $form }}.visits_left" type="number" min="0" :label="__('Visits left')" class="max-w-xs" />
        @break
    @case($model::RECOMMENDATION_DISCHARGE)
        <flux:input wire:model="data.{{ $form }}.discharge_reason" :label="__('Recommend discharge due to')" />
        @break
    @case($model::RECOMMENDATION_CONTINUED_THERAPY)
        <div class="grid max-w-md grid-cols-2 gap-4">
            <flux:input wire:model="data.{{ $form }}.continued_frequency" type="number" min="0" :label="__('Times / week')" />
            <flux:input wire:model="data.{{ $form }}.continued_duration" type="number" min="0" :label="__('For weeks')" />
        </div>
        @break
@endswitch
