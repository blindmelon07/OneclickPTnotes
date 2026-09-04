<div class="space-y-4">
    <flux:heading size="lg">{{ __('PT Route Sheet') }}</flux:heading>
    <flux:text>{{ __('Discipline: Physical Therapist Assistant') }}</flux:text>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:input wire:model="routeSheet.week_of" type="date" :label="__('Week of')" required />
        <flux:input wire:model="routeSheet.week_thru" type="date" :label="__('Thru')" required />
    </div>

    <flux:input wire:model.blur="routeSheet.agency_name" :label="__('Agency name')" :description="__('Carried onto the other forms.')" required />

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:input wire:model="routeSheet.patient_name" :label="__('Patient name')" />
        <flux:input wire:model.blur="routeSheet.mr_number" :label="__('MR #')" :description="__('Carried onto the other forms.')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <flux:input wire:model="routeSheet.visit_date" type="date" :label="__('Visit date')" required />
        <flux:input wire:model="routeSheet.time_in" type="time" :label="__('Time in')" />
        <flux:input wire:model="routeSheet.time_out" type="time" :label="__('Time out')" />
    </div>

    <flux:input wire:model="routeSheet.comments" :label="__('Comments')" />

    <div>
        <flux:label>{{ __('Patient signature') }}</flux:label>
        <flux:text class="text-sm">{{ __('Hand the device to the patient to sign below.') }}</flux:text>
        <x-signature-pad model="routeSheetSignature" class="mt-2" />
        <flux:error name="routeSheetSignature" />
    </div>

    <div class="space-y-2">
        <flux:label>{{ __('Visit code (check one)') }}</flux:label>
        <flux:radio.group wire:model="routeSheet.visit_code">
            @foreach (\App\Models\Note::visitCodes() as $code => $description)
                <flux:radio :value="$code" :label="$code.' — '.$description" />
            @endforeach
        </flux:radio.group>
    </div>

    @include('partials.visit-forms.letterhead', [
        'signatureModel' => 'routeSheetStaffSignature',
        'signature' => $routeSheetStaffSignature,
        'signedDate' => $routeSheet['visit_date'] ?? null,
    ])
</div>
