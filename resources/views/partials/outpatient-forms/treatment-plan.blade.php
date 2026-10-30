{{-- The treatment plan inputs for $form ('ie' / 're' / 'dc'); the RE and DC show what they copy as a placeholder. --}}
@php($copies = ['ie' => null, 're' => 'ie', 'dc' => 're'][$form])

<flux:heading size="lg">{{ __('Treatment plan') }}</flux:heading>
@if ($copies)
    <flux:text>{{ $copies === 'ie' ? __('Leave a line blank to copy it from the Initial Evaluation.') : __('Leave a line blank to copy it from the Re-Evaluation.') }}</flux:text>
@endif
<div class="grid gap-4 sm:grid-cols-2">
    @foreach (\App\Models\OutpatientForm::treatmentPlanItems() as $key => $label)
        @if (isset(\App\Models\OutpatientForm::fixedTreatmentPlan()[$key]))
            <flux:input
                :value="\App\Models\OutpatientForm::fixedTreatmentPlan()[$key]"
                :label="__($label)"
                icon:trailing="lock-closed"
                readonly
                @class(['sm:col-span-2' => $key === 'home_program'])
            />
        @else
            <flux:input
                wire:model="data.{{ $form }}.plan.{{ $key }}"
                :label="__($label)"
                :placeholder="$copies ? $preview->planFor($copies, $key) : null"
                @class(['sm:col-span-2' => $key === 'home_program'])
            />
        @endif
    @endforeach
</div>
