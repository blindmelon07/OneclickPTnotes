<div class="space-y-4">
    <flux:heading size="lg">{{ __('Communication Note') }}</flux:heading>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:input wire:model="communication.date" type="date" :label="__('Date')" required />
        <flux:input wire:model="communication.agency_name" :label="__('Agency name')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:input wire:model="communication.patient_name" :label="__('Patient name')" />
        <flux:input wire:model="communication.mr_number" :label="__('MR #')" />
    </div>

    <flux:textarea
        wire:model="communication.message"
        rows="8"
        required
        :label="__('Message')"
        :description="__('What the agency needs to know. \'Please call me if you have any questions. Thank you.\' is printed under this on the PDF.')"
    />

    <flux:separator variant="subtle" />

    <flux:text>{{ __('Verbal orders received, verified and read back by:') }}</flux:text>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:input wire:model="communication.verbal_orders_name" :label="__('Name')" />
        <flux:input wire:model="communication.verbal_orders_date" type="date" :label="__('Date')" />
    </div>

    <flux:input
        wire:model="communication.physician_signature_date"
        type="date"
        :label="__('Physician signature date')"
        :description="__('The physician signature line is left blank on the PDF for wet signing.')"
    />

    @include('partials.visit-forms.letterhead', [
        'signatureModel' => 'communicationStaffSignature',
        'signature' => $communicationStaffSignature,
        'signedDate' => $communication['date'] ?? null,
    ])
</div>
